<?php
// api_relatorio_vendas.php - Retorna dados agregados e TOTAIS gerais com filtros.
require_once 'conexao.php';
require_once 'verifica_sessao.php'; 
header('Content-Type: application/json');

// 1. Recebe e sanitiza os filtros
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : null;
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : null;
$produto_sku = isset($_GET['produto']) ? $conn->real_escape_string(trim($_GET['produto'])) : null;

$params = [];
$types = '';

// --- Função para construir a cláusula WHERE ---
function build_where_clause($conn, $mes, $ano, $produto_sku, &$params, &$types) {
    $where = " WHERE 1=1 "; 
    
    if (!empty($produto_sku)) {
        $where .= " AND p.sku LIKE ?";
        $params[] = "%" . $produto_sku . "%";
        $types .= 's';
    }

    if (!empty($ano) && $ano > 0) {
        $where .= " AND YEAR(v.data_venda) = ?";
        $params[] = $ano;
        $types .= 'i';
    }

    if (!empty($mes) && $mes > 0) {
        $where .= " AND MONTH(v.data_venda) = ?";
        $params[] = $mes;
        $types .= 'i';
    }
    return $where;
}

// 2. Consulta de DADOS DETALHADOS (para a Tabela)
$where_detail = build_where_clause($conn, $mes, $ano, $produto_sku, $params, $types);

$sql_detail = "
    SELECT 
        p.sku,
        p.nome AS nome_produto,
        p.unidade_medida,
        SUM(iv.quantidade) AS total_vendido_unidades,
        SUM(iv.quantidade * iv.preco_unitario) AS total_valor_bruto,
        DATE_FORMAT(v.data_venda, '%Y-%m') AS ano_mes_venda
    FROM itens_venda iv
    JOIN vendas v ON iv.venda_id = v.id
    JOIN produtos p ON iv.produto_id = p.id
    {$where_detail}
    GROUP BY p.sku, p.nome, p.unidade_medida, ano_mes_venda 
    ORDER BY ano_mes_venda DESC, total_valor_bruto DESC
";

// 3. Consulta de TOTAIS GERAIS (para os Cards)
// Reseta os parâmetros e tipos para evitar conflitos na segunda consulta
$params_total = [];
$types_total = '';
$where_total = build_where_clause($conn, $mes, $ano, $produto_sku, $params_total, $types_total);

$sql_total = "
    SELECT 
        SUM(iv.quantidade) AS total_vendido_geral,
        SUM(iv.quantidade * iv.preco_unitario) AS valor_total_geral
    FROM itens_venda iv
    JOIN vendas v ON iv.venda_id = v.id
    JOIN produtos p ON iv.produto_id = p.id
    {$where_total}
";

// --- Execução das Duas Consultas ---

// Execução da consulta DETALHE
$stmt_detail = $conn->prepare($sql_detail);
if ($types) {
    $bind_params = array_merge([$types], $params);
    $stmt_detail->bind_param(...$bind_params);
}
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

$relatorio = [];
while ($row = $result_detail->fetch_assoc()) { 
    $relatorio[] = $row; 
}
$stmt_detail->close();


// Execução da consulta TOTAL
$stmt_total = $conn->prepare($sql_total);
if ($types_total) {
    $bind_params_total = array_merge([$types_total], $params_total);
    $stmt_total->bind_param(...$bind_params_total);
}
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$totais = $result_total->fetch_assoc();
$stmt_total->close();


// Retorna ambos os conjuntos de dados
echo json_encode([
    'relatorio' => $relatorio,
    'totais' => $totais
]);

$conn->close();
?>