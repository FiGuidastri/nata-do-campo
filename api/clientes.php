<?php
/**
 * api_clientes.php - API de clientes
 * 
 * Endpoints:
 * GET /api/clientes.php?term=X - Busca clientes para autocomplete
 * GET /api/clientes.php?id=X - Retorna detalhes de um cliente específico
 * GET /api/clientes.php - Lista todos os clientes (com paginação)
 */

require_once __DIR__ . '/../includes/verifica_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

// Inicializa a resposta
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    // Parâmetros
    $term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 15;
    $offset = ($page - 1) * $limit;

    // Query base
    $sql_base = "
        SELECT 
            c.id,
            c.nome_cliente,
            c.cnpj,
            c.codigo_cliente,
            c.tipo_cliente_id,
            tc.nome AS tipo_cliente_nome,
            COALESCE(cnm.pontos_acumulados, 0) as pontos_clube,
            (SELECT COUNT(*) FROM vendas v WHERE v.cliente_id = c.id) as total_pedidos
        FROM 
            clientes c
            LEFT JOIN tipos_cliente tc ON c.tipo_cliente_id = tc.id
            LEFT JOIN clube_nata_membros cnm ON c.id = cnm.cliente_id
    ";

    // Se for busca por termo (autocomplete)
    if ($term && strlen($term) >= 2) {
        $searchTerm = "%" . strtolower($term) . "%";
        $sql = $sql_base . "
            WHERE LOWER(c.nome_cliente) LIKE ? 
            OR LOWER(c.cnpj) LIKE ? 
            OR LOWER(c.codigo_cliente) LIKE ? 
            ORDER BY c.nome_cliente ASC 
            LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $limit);
    }
    // Se for busca por ID
    else if ($id) {
        $sql = $sql_base . "WHERE c.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    }
    // Lista paginada
    else {
        // Conta total de registros para paginação
        $sql_count = "SELECT COUNT(*) as total FROM clientes";
        $result_count = $conn->query($sql_count);
        $total_records = $result_count->fetch_assoc()['total'];
        
        $sql = $sql_base . "ORDER BY c.nome_cliente ASC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $cliente = [
                'id' => (int)$row['id'],
                'nome' => $row['nome_cliente'],
                'cnpj' => $row['cnpj'],
                'codigo' => $row['codigo_cliente'],
                'tipo_cliente' => [
                    'id' => (int)$row['tipo_cliente_id'],
                    'nome' => $row['tipo_cliente_nome']
                ],
                'pontos_clube' => floatval($row['pontos_clube']),
                'total_pedidos' => (int)$row['total_pedidos']
            ];
            $clientes[] = $cliente;
        }

        $response['data'] = $id ? $clientes[0] : $clientes;
        
        // Adiciona metadata de paginação se não for busca específica
        if (!$id && !$term) {
            $response['meta'] = [
                'page' => $page,
                'limit' => $limit,
                'total' => $total_records,
                'total_pages' => ceil($total_records / $limit)
            ];
        }
        
        $response['success'] = true;
        $response['message'] = 'Clientes encontrados com sucesso.';
    } else {
        $response['message'] = $id 
            ? 'Cliente não encontrado.' 
            : 'Nenhum cliente encontrado.';
        http_response_code(404);
    }

} catch (Exception $e) {
    error_log("Erro em api_clientes.php: " . $e->getMessage());
    $response['message'] = 'Erro ao buscar clientes.';
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