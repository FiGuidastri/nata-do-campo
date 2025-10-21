<?php
// buscar_cliente.php - Script para buscar clientes via AJAX (Autocomplete)
require_once 'conexao.php'; 

// Verifica se o termo de busca foi enviado
if (!isset($_GET['term']) || empty(trim($_GET['term']))) {
    echo json_encode([]);
    exit();
}

$termo_busca = trim($_GET['term']);
$resultados = [];

// Usando prepared statement para segurança
$termo_seguro = "%" . $termo_busca . "%";

try {
    // Adicione 'tipo_cliente_id' ao SELECT!
    $sql = "SELECT id, nome_cliente, codigo_cliente, cnpj, tipo_cliente_id 
            FROM clientes 
            WHERE nome_cliente LIKE ? OR codigo_cliente LIKE ? OR cnpj LIKE ?
            LIMIT 10"; 
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $termo_seguro, $termo_seguro, $termo_seguro);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $label_display = htmlspecialchars($row['nome_cliente']) . " (Cód: " . htmlspecialchars($row['codigo_cliente']) . ")";
        
        $resultados[] = [
            'label' => $label_display, 
            'value' => htmlspecialchars($row['nome_cliente']), // Nome para preencher o campo
            'id' => $row['id'], // ID real do cliente
            'tipo_cliente_id' => $row['tipo_cliente_id'] // ESSENCIAL para a lógica de preço
        ];
    }

    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode($resultados);

} catch (Exception $e) {
    // Em caso de erro, retorna array vazio e loga o erro no servidor
    error_log("Erro no buscar_cliente.php: " . $e->getMessage());
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>