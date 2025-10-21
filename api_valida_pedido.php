<?php
// api_valida_pedido.php - Processa a mudança de status do pedido.
require_once 'conexao.php';
require_once 'verifica_sessao.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['venda_id'], $_POST['status'])) {
    $response['message'] = 'Requisição inválida.';
    echo json_encode($response);
    exit;
}

$venda_id = (int)$_POST['venda_id'];
$novo_status = $_POST['status'];
$usuario_validador_id = $usuario_logado['id']; 

$conn->begin_transaction(); 

try {
    // A baixa de estoque só acontece se o novo status for 'Faturado'.
    if ($novo_status === 'Faturado') {
        require_once 'estoque_util.php'; 
        
        if (!function_exists('baixaEstoqueFIFO')) {
             throw new Exception("Erro interno: Função de baixa de estoque ausente.");
        }

        $baixa_result = baixaEstoqueFIFO($conn, $venda_id);
        
        if (!$baixa_result['success']) {
            // Se a baixa falhou, lançamos uma exceção para o catch,
            // mas mantemos a mensagem limpa da função para facilitar o tratamento.
            throw new Exception("ERRO_ESTOQUE_FIFO: " . $baixa_result['message']);
        }
    }
    
    // ATUALIZA O STATUS DA VENDA
    // Usando 'usuario_validador' conforme a sua tabela
    $stmt = $conn->prepare("UPDATE vendas SET status = ?, usuario_validador = ?, data_validacao = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $novo_status, $usuario_validador_id, $venda_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar status da venda: " . $stmt->error);
    }
    
    // COMITE A TRANSAÇÃO
    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Pedido #$venda_id atualizado para '$novo_status' com sucesso." . 
                           ($novo_status === 'Faturado' ? " Baixa de estoque FIFO realizada." : "");

} catch (Exception $e) {
    // ROLLBACK 
    $conn->rollback();
    
    $errorMessage = $e->getMessage();
    
    // Tenta identificar o erro específico de estoque
    if (strpos($errorMessage, "ERRO_ESTOQUE_FIFO:") === 0) {
        // Remove nosso marcador e formata a mensagem de forma profissional
        $estoque_detail = trim(substr($errorMessage, strlen("ERRO_ESTOQUE_FIFO:")));
        
        // Exemplo: "Estoque zero para o Produto ID 5."
        $response['message'] = "Faturamento NÃO REALIZADO: O pedido não pôde ser faturado por falta de estoque. Detalhe: " . $estoque_detail;
        
    } else {
        // Para todos os outros erros (SQL, conexao, etc.)
        error_log("Erro crítico no processamento do pedido #$venda_id: " . $errorMessage);
        $response['message'] = "Falha crítica no processamento. Ação desfeita. Por favor, tente novamente ou contate o suporte. Detalhe: " . $errorMessage;
    }
    
    $response['error_details'] = $errorMessage; // Mantém o detalhe completo no JSON para debug
} finally {
    if (isset($stmt)) $stmt->close();
    // A conexão é fechada automaticamente no final do script.
}

echo json_encode($response);
?>