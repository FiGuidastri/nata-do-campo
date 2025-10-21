<?php
/**
 * api_clube_nata_vendedor.php - API do Clube Nata (sistema de pontos)
 * 
 * Endpoints:
 * GET /api/clube_nata_vendedor.php - Retorna top 10 clientes com mais pontos
 * GET /api/clube_nata_vendedor.php?cliente_id=X - Retorna pontuação de um cliente específico
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
    // Verifica se foi solicitado um cliente específico
    $cliente_id = filter_input(INPUT_GET, 'cliente_id', FILTER_VALIDATE_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;

    // Query base
    $sql_base = "
        SELECT 
            c.id,
            c.nome_cliente,
            c.codigo_cliente,
            COALESCE(cnm.pontos_acumulados, 0) as pontuacao,
            COALESCE(cnm.data_ultima_pontuacao, NULL) as ultima_pontuacao,
            COUNT(DISTINCT cnr.id) as total_resgates,
            SUM(CASE WHEN YEAR(cnr.data_resgate) = YEAR(CURRENT_DATE) THEN 1 ELSE 0 END) as resgates_ano_atual
        FROM 
            clientes c
            LEFT JOIN clube_nata_membros cnm ON c.id = cnm.cliente_id
            LEFT JOIN clube_nata_resgates cnr ON c.id = cnr.cliente_id
    ";

    if ($cliente_id) {
        // Busca detalhes de um cliente específico
        $sql = $sql_base . " WHERE c.id = ? GROUP BY c.id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cliente_id);
    } else {
        // Busca top N clientes com mais pontos
        $sql = $sql_base . " 
            WHERE cnm.pontos_acumulados > 0 
            GROUP BY c.id 
            ORDER BY cnm.pontos_acumulados DESC 
            LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $membros = [];
        while ($row = $result->fetch_assoc()) {
            // Formata os dados
            $membro = [
                'id' => (int)$row['id'],
                'nome_cliente' => $row['nome_cliente'],
                'codigo_cliente' => $row['codigo_cliente'],
                'pontuacao' => floatval($row['pontuacao']),
                'ultima_pontuacao' => $row['ultima_pontuacao'],
                'estatisticas' => [
                    'total_resgates' => (int)$row['total_resgates'],
                    'resgates_ano_atual' => (int)$row['resgates_ano_atual']
                ]
            ];
            $membros[] = $membro;
        }

        $response['data'] = $cliente_id ? $membros[0] : $membros;
        $response['success'] = true;
        $response['message'] = 'Dados do clube carregados com sucesso.';
    } else {
        $response['message'] = $cliente_id 
            ? 'Cliente não encontrado ou sem pontuação.' 
            : 'Nenhum membro com pontuação encontrado.';
        http_response_code(404);
    }

} catch (Exception $e) {
    error_log("Erro em api_clube_nata_vendedor.php: " . $e->getMessage());
    $response['message'] = 'Erro ao carregar dados do clube.';
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