<?php
/**
 * api_valida_pedido.php - API para validação e mudança de status de pedidos
 * 
 * Endpoint:
 * POST /api/valida_pedido.php
 * Body: venda_id (int), status (string)
 */

require_once __DIR__ . '/../includes/verifica_sessao.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../src/Utils/estoque_util.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

// Inicializa a resposta
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

// Verifica privilégios
if (!in_array($usuario_logado['privilegio'], ['Admin', 'Gestor'])) {
    http_response_code(403);
    $response['message'] = 'Acesso negado. Apenas Administradores e Gestores podem validar pedidos.';
    echo json_encode($response);
    exit;
}

// Validação do método e parâmetros
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Método não permitido. Use POST.';
    echo json_encode($response);
    exit;
}

// Validação dos dados recebidos
$venda_id = filter_input(INPUT_POST, 'venda_id', FILTER_VALIDATE_INT);
$novo_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

if (!$venda_id || !$novo_status) {
    http_response_code(400);
    $response['message'] = 'Dados inválidos. Forneça venda_id e status válidos.';
    echo json_encode($response);
    exit;
}

// Status permitidos
$status_permitidos = ['Liberado', 'Entrega', 'Faturado', 'Rejeitado', 'Cancelado'];
if (!in_array($novo_status, $status_permitidos)) {
    http_response_code(400);
    $response['message'] = 'Status inválido. Status permitidos: ' . implode(', ', $status_permitidos);
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Verifica se o pedido existe e seu status atual
    $stmt = $conn->prepare("SELECT status FROM vendas WHERE id = ?");
    $stmt->bind_param("i", $venda_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Pedido #$venda_id não encontrado.");
    }
    
    $status_atual = $result->fetch_assoc()['status'];
    
    // Valida a transição de status
    $transicoes_permitidas = [
        'Pendente' => ['Liberado', 'Rejeitado'],
        'Liberado' => ['Entrega', 'Faturado', 'Cancelado'],
        'Entrega' => ['Faturado', 'Cancelado'],
        'Faturado' => [],
        'Rejeitado' => [],
        'Cancelado' => []
    ];
    
    if (!isset($transicoes_permitidas[$status_atual]) || 
        !in_array($novo_status, $transicoes_permitidas[$status_atual])) {
        throw new Exception("Transição de status inválida: de '$status_atual' para '$novo_status'");
    }

    // Se vai faturar, processa a baixa de estoque
    if ($novo_status === 'Faturado') {
        $baixa_result = baixaEstoqueFIFO($conn, $venda_id);
        if (!$baixa_result['success']) {
            throw new Exception("ERRO_ESTOQUE: " . $baixa_result['message']);
        }
    }
    
    // Atualiza o status do pedido
    $stmt = $conn->prepare("
        UPDATE vendas 
        SET status = ?, 
            usuario_validador = ?, 
            data_validacao = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("sii", $novo_status, $usuario_logado['id'], $venda_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar status: " . $stmt->error);
    }
    
    // Se chegou até aqui, commit da transação
    $conn->commit();
    
    $response['success'] = true;
    $response['data'] = [
        'venda_id' => $venda_id,
        'status_anterior' => $status_atual,
        'novo_status' => $novo_status,
        'data_validacao' => date('Y-m-d H:i:s')
    ];
    $response['message'] = "Pedido #$venda_id atualizado para '$novo_status' com sucesso.";

} catch (Exception $e) {
    $conn->rollback();
    
    $error_message = $e->getMessage();
    $is_estoque_error = strpos($error_message, "ERRO_ESTOQUE:") === 0;
    
    http_response_code($is_estoque_error ? 409 : 500);
    $response['message'] = $is_estoque_error 
        ? "Falha no faturamento: " . substr($error_message, strlen("ERRO_ESTOQUE:"))
        : "Erro ao processar pedido: " . $error_message;
        
    error_log("Erro na validação do pedido #$venda_id: " . $error_message);

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>