<?php
/**
 * api_precos.php - API de preços
 * 
 * Endpoints:
 * GET /api/precos.php?produto_id=X&tipo_cliente_id=Y - Retorna o preço de um produto para um tipo de cliente
 * GET /api/precos.php?produto_id=X - Retorna todos os preços de um produto por tipo de cliente
 */
require_once __DIR__ . '/../includes/verifica_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

// Inicializa a resposta
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    // Validação de parâmetros
    $produto_id = filter_input(INPUT_GET, 'produto_id', FILTER_VALIDATE_INT);
    $tipo_cliente_id = filter_input(INPUT_GET, 'tipo_cliente_id', FILTER_VALIDATE_INT);

    if (!$produto_id) {
        throw new Exception('ID do produto é obrigatório e deve ser um número inteiro.');
    }

    // Se tipo_cliente_id não for fornecido, retorna todos os preços do produto
    if (!$tipo_cliente_id) {
        $sql = "SELECT p.preco, tc.nome as tipo_cliente 
                FROM precos p 
                JOIN tipos_cliente tc ON p.tipo_cliente_id = tc.id 
                WHERE p.produto_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $produto_id);
    } else {
        $sql = "SELECT p.preco, tc.nome as tipo_cliente 
                FROM precos p 
                JOIN tipos_cliente tc ON p.tipo_cliente_id = tc.id 
                WHERE p.produto_id = ? AND p.tipo_cliente_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $produto_id, $tipo_cliente_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['data'] = $tipo_cliente_id ? $result->fetch_assoc() : $result->fetch_all(MYSQLI_ASSOC);
        $response['success'] = true;
        $response['message'] = 'Preços encontrados com sucesso.';
    } else {
        $response['message'] = 'Nenhum preço encontrado para os parâmetros fornecidos.';
    }

} catch (Exception $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
    http_response_code(400);
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