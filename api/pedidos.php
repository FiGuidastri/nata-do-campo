<?php
/**
 * api_pedidos.php - API de pedidos/vendas
 * 
 * Endpoints:
 * GET /api/pedidos.php - Lista os últimos pedidos (com suporte a filtros)
 * GET /api/pedidos.php?id=X - Retorna detalhes de um pedido específico
 */

require_once __DIR__ . '/../includes/verifica_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Methods: GET');

// Inicializa a resposta
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    // Parâmetros de filtro
    $pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
    $cliente = filter_input(INPUT_GET, 'cliente', FILTER_SANITIZE_STRING);
    $data_inicio = filter_input(INPUT_GET, 'data_inicio', FILTER_SANITIZE_STRING);
    $data_fim = filter_input(INPUT_GET, 'data_fim', FILTER_SANITIZE_STRING);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 100;

    // Query base
    $sql = "SELECT 
                v.id AS venda_id,
                v.valor_total,
                v.status,
                v.data_venda,
                v.data_entrega,
                DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_venda_formatada,
                DATE_FORMAT(v.data_entrega, '%d/%m/%Y') AS data_entrega_formatada,
                c.nome_cliente,
                c.codigo_cliente,
                c.cnpj,
                tc.nome AS tipo_cliente_nome,
                u.nome AS nome_vendedor
            FROM vendas v
            JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN tipos_cliente tc ON c.tipo_cliente_id = tc.id
            LEFT JOIN usuarios u ON v.usuario_vendedor = u.id";

    $where = [];
    $params = [];
    $types = '';

    // Construção dinâmica da query baseada nos filtros
    if ($pedido_id) {
        $where[] = "v.id = ?";
        $params[] = $pedido_id;
        $types .= 'i';
    }
    if ($status) {
        $where[] = "v.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    if ($cliente) {
        $where[] = "(c.nome_cliente LIKE ? OR c.codigo_cliente LIKE ? OR c.cnpj LIKE ?)";
        $params[] = "%$cliente%";
        $params[] = "%$cliente%";
        $params[] = "%$cliente%";
        $types .= 'sss';
    }
    if ($data_inicio) {
        $where[] = "DATE(v.data_venda) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }
    if ($data_fim) {
        $where[] = "DATE(v.data_venda) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    // Adiciona as condições WHERE se houver
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    // Ordenação e limite
    $sql .= " ORDER BY v.data_venda DESC LIMIT ?";
    $params[] = $limit;
    $types .= 'i';

    // Prepara e executa a query
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pedidos = [];
        while ($row = $result->fetch_assoc()) {
            // Formatação dos dados
            $row['valor_total'] = floatval($row['valor_total']);
            $pedidos[] = $row;
        }
        
        $response['data'] = $pedido_id ? $pedidos[0] : $pedidos;
        $response['success'] = true;
        $response['message'] = 'Pedidos encontrados com sucesso.';
    } else {
        $response['message'] = 'Nenhum pedido encontrado.';
        http_response_code(404);
    }

} catch (Exception $e) {
    error_log("Erro em api_pedidos.php: " . $e->getMessage());
    $response['message'] = 'Erro ao buscar pedidos: ' . $e->getMessage();
    http_response_code(500);
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