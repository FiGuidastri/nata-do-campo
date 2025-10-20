<?php
// api_pedidos.php - Retorna a lista dos últimos pedidos em formato JSON com dados completos.
// Versão revisada para garantir o fluxo de dados e debug.

// Importa a conexão ANTES da verificação de sessão (prática mais segura)
require_once 'conexao.php'; 
require_once 'verifica_sessao.php'; 

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate'); // Ajuda a evitar cache de dados antigos

$response = ['pedidos' => [], 'error' => null];

// 1. VERIFICAÇÃO CRÍTICA DE CONEXÃO
if (!isset($conn) || $conn->connect_error) {
    $response['error'] = 'Falha Crítica (PHP): Conexão com o Banco de Dados não estabelecida.';
    error_log("Erro de Conexão em api_pedidos.php: " . ($conn->connect_error ?? 'Variavel $conn não existe'));
    echo json_encode($response);
    exit;
}

$COLUNA_VENDEDOR_ID = 'usuario_vendedor'; 

try {
    $sql = "
        SELECT 
            v.id AS venda_id, 
            v.valor_total, 
            v.status,
            DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_venda_formatada,
            DATE_FORMAT(v.data_entrega, '%d/%m/%Y') AS data_entrega_formatada,
            
            c.nome_cliente, 
            c.codigo_cliente, 
            c.cnpj, 
            
            tc.nome AS tipo_cliente_nome, 
            u.nome AS nome_vendedor 
        FROM vendas v
        JOIN clientes c ON v.cliente_id = c.id
        -- USANDO 'tipos_cliente' conforme seu código anterior. Se falhar, use 'tipo_cliente'.
        LEFT JOIN tipos_cliente tc ON c.tipo_cliente_id = tc.id 
        LEFT JOIN usuarios u ON v.{$COLUNA_VENDEDOR_ID} = u.id 
        ORDER BY v.data_venda DESC 
        LIMIT 100
    ";

    $result = $conn->query($sql);

    if ($result === FALSE) {
        // Se a consulta SQL falhar (ex: nome de coluna/tabela incorreto)
        throw new Exception("Erro na consulta SQL. Detalhe: " . $conn->error);
    }

    while ($row = $result->fetch_assoc()) { 
        // Conversão de valor para float para garantir o formato correto no JSON
        $row['valor_total'] = floatval($row['valor_total']);
        $response['pedidos'][] = $row; 
    }

} catch (Exception $e) {
    // Captura erros de lógica ou SQL
    error_log("Erro em api_pedidos.php: " . $e->getMessage());
    $response['error'] = 'Falha no servidor ao carregar pedidos. ' . $e->getMessage();
    $response['pedidos'] = [];
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// Se o script chegou aqui, a saída deve ser um JSON válido.
echo json_encode($response);
?>