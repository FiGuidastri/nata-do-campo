<?php
// get_precos_por_cliente.php - Retorna preços unitários para um determinado tipo de cliente
header('Content-Type: application/json');
require_once 'conexao.php'; 
require_once 'verifica_sessao.php'; // Adicione a verificação de sessão

$tipo_cliente_id = isset($_GET['tipo_cliente_id']) ? (int)$_GET['tipo_cliente_id'] : 0;
$precos = [];

if ($tipo_cliente_id > 0) {
    // Busca todos os preços para aquele tipo de cliente
    $sql = "
        SELECT 
            produto_id, 
            preco_unitario 
        FROM 
            precos
        WHERE 
            tipo_cliente_id = ? 
    ";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao preparar a consulta: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $tipo_cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Formata o resultado como um array associativo: { produto_id: preco }
        $precos[$row['produto_id']] = (float)$row['preco_unitario'];
    }
    
    $stmt->close();
}

$conn->close();

echo json_encode($precos);
?>