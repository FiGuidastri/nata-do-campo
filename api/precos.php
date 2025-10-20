<?php
// api_precos.php - Retorna o preço de venda de um produto para um tipo de cliente.
require_once 'conexao.php';
header('Content-Type: application/json');

$produto_id = isset($_GET['produto_id']) ? intval($_GET['produto_id']) : 0;
$tipo_cliente_id = isset($_GET['tipo_cliente_id']) ? intval($_GET['tipo_cliente_id']) : 0;

if ($produto_id <= 0 || $tipo_cliente_id <= 0) {
    echo json_encode(['preco' => null, 'message' => 'IDs inválidos.']);
    exit;
}

// Consulta a tabela 'precos'
$sql = "SELECT preco FROM precos WHERE produto_id = ? AND tipo_cliente_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $produto_id, $tipo_cliente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['preco' => $row['preco']]);
} else {
    echo json_encode(['preco' => null, 'message' => 'Preço não encontrado.']);
}

$stmt->close();
$conn->close();
?>