<?php
// detalhe_venda.php - Exibe os produtos vendidos de um pedido específico.
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

$venda_id = isset($_GET['venda_id']) ? intval($_GET['venda_id']) : 0;

if ($venda_id <= 0) {
    header("Location: painel_pedidos.php?status=error&msg=" . urlencode("ID de venda inválido."));
    exit;
}

// 1. Busca os Itens de Venda
$itens = [];
$sql_itens = "
    SELECT 
        iv.quantidade, 
        iv.preco_unitario,
        p.sku, 
        p.nome AS nome_produto,
        p.unidade_medida
    FROM itens_venda iv
    JOIN produtos p ON iv.produto_id = p.id
    WHERE iv.venda_id = ?
";
$stmt = $conn->prepare($sql_itens);
$stmt->bind_param("i", $venda_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { 
    $itens[] = $row; 
}
$stmt->close();

// 2. Busca Dados do Cabeçalho para título e contexto
$cabecalho = null;
$sql_cabecalho = "
    SELECT 
        v.valor_total, v.status, v.forma_pagamento, DATE_FORMAT(v.data_venda, '%d/%m/%Y') AS data_venda_formatada, DATE_FORMAT(v.data_entrega, '%d/%m/%Y') AS data_entrega_formatada,
        c.nome_cliente, c.codigo_cliente, tc.nome AS tipo_cliente
    FROM vendas v
    JOIN clientes c ON v.cliente_id = c.id
    JOIN tipos_cliente tc ON c.tipo_cliente_id = tc.id
    WHERE v.id = ?
";
$stmt_cab = $conn->prepare($sql_cabecalho);
$stmt_cab->bind_param("i", $venda_id);
$stmt_cab->execute();
$result_cab = $stmt_cab->get_result();
if ($result_cab->num_rows > 0) {
    $cabecalho = $result_cab->fetch_assoc();
}
$stmt_cab->close();
$conn->close();

$titulo = $cabecalho ? "Pedido #$venda_id | Cliente: " . htmlspecialchars($cabecalho['nome_cliente']) : "Detalhe do Pedido #$venda_id";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhe do Pedido | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="top-header">
        <div class="logo"><img src="logo.png" alt="Logo Nata do Campo"></div>
        <div class="user-info">
            <span class="user-name">Olá, <span><?= htmlspecialchars($usuario_logado['nome']) ?></span> (<?= $usuario_logado['privilegio'] ?>)</span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </header>

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-search"></i> Detalhe do <?= $titulo ?></h1>
                
                <?php if ($cabecalho): ?>
                    <fieldset>
                        <legend>Dados do Pedido</legend>
                        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                            <div>
                                <strong>Cliente:</strong> <?= htmlspecialchars($cabecalho['nome_cliente']) ?> (Cód: <?= htmlspecialchars($cabecalho['codigo_cliente']) ?>)
                            </div>
                            <div>
                                <strong>Tipo:</strong> <?= htmlspecialchars($cabecalho['tipo_cliente']) ?>
                            </div>
                            <div>
                                <strong>Status:</strong> <span class="status-badge status-<?= $cabecalho['status'] ?>"><?= $cabecalho['status'] ?></span>
                            </div>
                            <div>
                                <strong>Data da Venda:</strong> <?= $cabecalho['data_venda_formatada'] ?>
                            </div>
                            <div>
                                <strong>Entrega Prevista:</strong> <?= $cabecalho['data_entrega_formatada'] ?>
                            </div>
                            <div>
                                <strong>Forma de Pagamento:</strong> <?= htmlspecialchars($cabecalho['forma_pagamento']) ?>
                            </div>
                            <div class="full-width">
                                <strong>Valor Total:</strong> <span style="font-size: 1.2rem; color: var(--primary-color);">R$ <?= number_format($cabecalho['valor_total'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <h2 style="margin-top: 30px; font-size: 1.5rem;"><i class="fas fa-boxes"></i> Itens do Pedido</h2>

                <?php if (empty($itens)): ?>
                    <div class="feedback-message status-error">Nenhum item encontrado para esta venda.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Produto</th>
                                    <th>Unidade</th>
                                    <th>Preço Unitário</th>
                                    <th>Quantidade</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                                        <td><?= htmlspecialchars($item['unidade_medida']) ?></td>
                                        <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                                        <td><?= number_format($item['quantidade'], 0, ',', '.') ?></td>
                                        <td>R$ <?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <a href="painel_pedidos.php" class="btn btn-secondary" style="margin-top: 20px;"><i class="fas fa-arrow-circle-left"></i> Voltar ao Painel</a>
            </div>
        </div>
    </div>
</body>
</html>