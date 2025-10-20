<?php
// processa_baixa_estoque.php
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E USUÁRIO "INDÚSTRIA"
if ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Industria') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: baixa_estoque.php?status=error&msg=" . urlencode("Método de requisição inválido."));
    exit();
}

$produto_id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
$quantidade_baixa = filter_input(INPUT_POST, 'quantidade_baixa', FILTER_VALIDATE_FLOAT);
$data_saida = trim($_POST['data_saida'] ?? '');
$observacao_baixa = trim($_POST['observacao_baixa'] ?? 'Baixa manual de estoque.');
$lotes_json = $_POST['lotes_para_baixa_json'] ?? '[]';

$lotes_para_baixa = json_decode($lotes_json, true);

if (!$produto_id || $quantidade_baixa <= 0 || empty($data_saida) || empty($lotes_para_baixa)) {
    header("Location: baixa_estoque.php?status=error&msg=" . urlencode("Dados de baixa incompletos ou inválidos."));
    exit();
}

$conn->begin_transaction();
$usuario_id = $usuario_logado['id'];
$total_baixado = 0;

// 1. Tabela de Histórico de Movimentação (LOG)
// 💡 Você precisa ter esta tabela criada no seu DB para registrar a baixa
// Exemplo de estrutura (se não tiver):
/*
CREATE TABLE movimentacao_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lote_id INT,
    produto_id INT,
    tipo ENUM('ENTRADA', 'SAIDA') NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    data_movimentacao DATE NOT NULL,
    usuario_id INT,
    observacao TEXT
);
*/
$sql_historico = "INSERT INTO movimentacao_estoque (lote_id, produto_id, tipo, quantidade, data_movimentacao, usuario_id, observacao) 
                  VALUES (?, ?, 'SAIDA', ?, ?, ?, ?)";
$stmt_historico = $conn->prepare($sql_historico);

if (!$stmt_historico) {
    $conn->rollback();
    header("Location: baixa_estoque.php?status=error&msg=" . urlencode("Erro na preparação do histórico: " . $conn->error));
    exit();
}


try {
    // 2. Processar a Baixa em Cada Lote
    $sql_update_lote = "UPDATE lotes_estoque SET saldo_atual = saldo_atual - ? WHERE id = ?";
    $stmt_update_lote = $conn->prepare($sql_update_lote);

    if (!$stmt_update_lote) {
        throw new Exception("Erro na preparação do UPDATE de lote: " . $conn->error);
    }

    foreach ($lotes_para_baixa as $item) {
        $lote_id = $item['lote_id'];
        $qtd_lote = $item['quantidade_baixa'];
        
        // A. Atualiza o saldo_atual do lote
        $stmt_update_lote->bind_param("di", $qtd_lote, $lote_id);
        if (!$stmt_update_lote->execute()) {
             throw new Exception("Erro ao baixar o lote ID {$lote_id}: " . $stmt_update_lote->error);
        }

        // B. Registra no histórico de movimentação
        $stmt_historico->bind_param("iidiss", $lote_id, $produto_id, $qtd_lote, $data_saida, $usuario_id, $observacao_baixa);
        if (!$stmt_historico->execute()) {
             throw new Exception("Erro ao registrar histórico do lote ID {$lote_id}: " . $stmt_historico->error);
        }

        $total_baixado += $qtd_lote;
    }

    $stmt_update_lote->close();
    $stmt_historico->close();
    
    // 3. Commit da Transação
    $conn->commit();
    
    $msg_sucesso = "Baixa registrada com sucesso. Total de saída: " . number_format($total_baixado, 2, ',', '.');
    header("Location: baixa_estoque.php?status=success&msg=" . urlencode($msg_sucesso));
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: baixa_estoque.php?status=error&msg=" . urlencode("Transação falhou: " . $e->getMessage()));
} finally {
    if (isset($conn)) $conn->close();
    exit();
}
?>