<?php
// api_clube_nata_vendedor.php - Retorna os clientes com pontuação para o painel lateral
require_once 'verifica_sessao.php';
require_once 'conexao.php';

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json');

$response = ['success' => false, 'membros' => [], 'message' => ''];

try {
    // A consulta assume que a pontuação está na tabela 'clientes' e só mostra quem tem > 0.
    $sql = "
        SELECT 
            id, 
            nome_cliente, 
            COALESCE(pontuacao, 0) AS pontuacao 
        FROM 
            clientes 
        WHERE 
            pontuacao > 0 
        ORDER BY 
            pontuacao DESC 
        LIMIT 10
    "; // Exibe o top 10

    $result = $conn->query($sql);

    if ($result) {
        $membros = [];
        while ($row = $result->fetch_assoc()) {
            $membros[] = [
                'id' => $row['id'],
                'nome_cliente' => $row['nome_cliente'],
                'pontuacao' => floatval($row['pontuacao'])
            ];
        }
        $response['success'] = true;
        $response['membros'] = $membros;
    } else {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Erro em api_clube_nata_vendedor.php: " . $e->getMessage());
    $response['message'] = 'Falha ao carregar dados do clube.';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>