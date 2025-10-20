<?php
// get_lotes_disponiveis.php
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

header('Content-Type: application/json');

if (!isset($_GET['produto_id']) || !is_numeric($_GET['produto_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do produto inválido.']);
    exit();
}

$produto_id = intval($_GET['produto_id']);
$lotes = [];

try {
    // Busca lotes com saldo_atual > 0 e status Liberado.
    // ORDENAÇÃO (FEFO/FIFO):
    // 1. data_vencimento ASC (FEFO: Mais próximo vence primeiro)
    // 2. data_entrada ASC (FIFO: Se o vencimento for igual, o que entrou primeiro sai primeiro)
    $sql = "
        SELECT 
            id, 
            num_lote, 
            data_entrada, 
            data_vencimento, 
            saldo_atual, 
            status_lote,
            fornecedor_id 
        FROM 
            lotes_estoque
        WHERE 
            produto_id = ? 
            AND saldo_atual > 0
            AND status_lote IN ('Liberado Todos', 'Liberado VDI') 
        ORDER BY 
            data_vencimento ASC, 
            data_entrada ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Converte saldo_atual para float no PHP antes de enviar ao JS
        $row['saldo_atual'] = (float)$row['saldo_atual']; 
        $lotes[] = $row;
    }

    echo json_encode($lotes);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao buscar lotes: ' . $e->getMessage()]);
    error_log("Erro em get_lotes_disponiveis: " . $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>