<?php
// relatorio_estoque_baixo.php - Lista de produtos com estoque abaixo do limite (Atualizado para Lotes e Status)
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E USUÁRIO "INDÚSTRIA"
if ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Industria') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$produtos_baixo_estoque = [];
$todos_produtos = []; // Usado para a lista de Alerta e contagem de itens
$estoque_por_status = []; // NOVO: Array para agrupar o estoque por status de lote

// Função para formatar a quantidade
function formatar_quantidade($quantidade, $unidade_medida) {
    // Lista de unidades que tipicamente usam decimais
    $unidades_decimais = ['kg', 'l', 'm', 'm2', 'm3'];
    
    // Usar 2 casas decimais para unidades de peso/volume e zero para unidades de contagem.
    if (in_array(strtolower($unidade_medida), $unidades_decimais)) {
        return number_format($quantidade, 2, ',', '.'); // 2 casas decimais
    } else {
        return number_format($quantidade, 0, ',', '.'); // Sem casas decimais
    }
}

// --- 1. Busca TODOS os Produtos e CALCULA o estoque atual TOTAL (Para Alerta) ---
$sql_total = "
    SELECT 
        p.id, 
        p.sku, 
        p.nome, 
        p.unidade_medida,
        p.limite_alerta, 
        COALESCE(SUM(l.quantidade), 0) AS estoque_atual
    FROM 
        produtos p
    LEFT JOIN 
        lotes_estoque l ON p.id = l.produto_id
    GROUP BY 
        p.id, p.sku, p.nome, p.unidade_medida, p.limite_alerta 
    ORDER BY 
        p.nome ASC
";
$result_total = $conn->query($sql_total);

if ($result_total === false) {
    error_log("Erro ao carregar totais de produtos (Lotes): " . $conn->error);
} else {
    while ($row = $result_total->fetch_assoc()) {
        $row['estoque_atual'] = (float) $row['estoque_atual'];
        $row['limite_alerta'] = (float) $row['limite_alerta'];
        
        $todos_produtos[] = $row;
        
        // --- LÓGICA DE ALERTA NO PHP ---
        if ( ($row['estoque_atual'] <= $row['limite_alerta']) ) {
             $produtos_baixo_estoque[] = $row;
        }
    }
}


// --- 2. NOVA BUSCA: Detalhe do Estoque por Produto e Status do Lote ---
// CORRIGIDO: O campo l.status foi substituído por l.status_lote
$sql_status = "
    SELECT 
        p.id AS produto_id,
        p.sku,
        p.nome,
        p.unidade_medida,
        l.status_lote, -- CAMPO CORRIGIDO
        COALESCE(SUM(l.quantidade), 0) AS quantidade_por_status
    FROM 
        produtos p
    INNER JOIN 
        lotes_estoque l ON p.id = l.produto_id
    GROUP BY 
        p.id, p.sku, p.nome, p.unidade_medida, l.status_lote -- CAMPO CORRIGIDO
    HAVING
        SUM(l.quantidade) > 0
    ORDER BY 
        p.nome ASC, l.status_lote ASC -- CAMPO CORRIGIDO
";
$result_status = $conn->query($sql_status);

if ($result_status === false) {
    error_log("Erro ao carregar estoque por status (Lotes): " . $conn->error);
} else {
    while ($row = $result_status->fetch_assoc()) {
        $produto_id = $row['produto_id'];
        
        // Inicializa o array para o produto se for a primeira vez
        if (!isset($estoque_por_status[$produto_id])) {
            $estoque_por_status[$produto_id] = [
                'id' => $produto_id,
                'sku' => $row['sku'],
                'nome' => $row['nome'],
                'unidade_medida' => $row['unidade_medida'],
                'detalhes' => []
            ];
        }
        
        // Adiciona o detalhe por status
        // A chave do array agora é 'status' para manter o código HTML limpo, mas usa o valor de 'status_lote'
        $estoque_por_status[$produto_id]['detalhes'][] = [
            'status' => htmlspecialchars($row['status_lote']),
            'quantidade' => (float) $row['quantidade_por_status']
        ];
    }
}

$conn->close(); 

// Variável para exibir o maior limite usado no alerta, só para informação
$maior_limite_alerta = 0;
foreach ($produtos_baixo_estoque as $p) {
    if ($p['limite_alerta'] > $maior_limite_alerta) {
        $maior_limite_alerta = $p['limite_alerta'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Estoque Baixo | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        /* Estilos Existentes (Estoque Baixo) */
        .data-table .estoque-critico { background-color: var(--danger-color-light, #fde4e4); }
        .data-table .estoque-critico td { font-weight: bold; color: var(--danger-color); }
        /* NOVO ESTILO: limite-info agora é azul/info, pois o limite varia por produto */
        .limit-info { background: var(--primary-color); color: #fff; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-weight: 600; }
        
        /* ... Estilos dos Cards (Mantidos) ... */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Aumentado para melhor visualização dos detalhes */
            gap: var(--spacing-md);
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
        }
        .estoque-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            padding-top: calc(var(--spacing-md) + 25px);
            box-shadow: var(--shadow-small);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between; 
        }
        .estoque-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }
        .estoque-card h4 {
            font-size: 1.1rem;
            margin-top: 0;
            margin-bottom: var(--spacing-xs);
            color: var(--primary-color-dark);
            line-height: 1.3;
            min-height: 2.6rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .estoque-card .sku-tag {
              position: absolute;
              top: var(--spacing-xs);
              right: var(--spacing-md);
              background-color: var(--primary-color-light);
              color: var(--primary-color-dark);
              padding: 3px 8px;
              border-radius: 5px;
              font-size: 0.8rem;
              font-weight: 600;
              z-index: 1;
        }
    </style>
</head>
<body>
    <?php include 'top-header.php'; ?>

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-box"></i> Situação do Estoque</h1>
                
                <div class="card-large">
                    <h2><i class="fas fa-exclamation-triangle"></i> Alerta de Estoque Baixo</h2>
                    <p>Lista de produtos cuja quantidade em estoque (total) está igual ou abaixo do seu limite de alerta específico.</p>
                    
                    <div class="limit-info">
                        <i class="fas fa-info-circle"></i> O limite de alerta é dinâmico, variando por produto.
                    </div>

                    <?php if (empty($produtos_baixo_estoque)): ?>
                        <p class="feedback-message status-success"><i class="fas fa-thumbs-up"></i> Parabéns! Todos os produtos estão com estoque acima do limite de alerta individual.</p>
                    <?php else: ?>
                        <p class="feedback-message status-warning"><i class="fas fa-bell"></i> Encontrados <?= count($produtos_baixo_estoque) ?> produtos que precisam de reabastecimento imediato.</p>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Nome do Produto</th>
                                        <th style="width: 150px; text-align: center;">Estoque Atual (Total)</th>
                                        <th style="width: 150px; text-align: center;">Limite de Alerta</th>
                                        <th style="width: 100px;">Ação Rápida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produtos_baixo_estoque as $produto): ?>
                                        <?php 
                                            // Destaca o produto se estiver em zero ou abaixo do limite
                                            $class_linha = ($produto['estoque_atual'] <= 0) ? 'estoque-critico' : ''; 
                                        ?>
                                        <tr class="<?= $class_linha ?>">
                                            <td><?= htmlspecialchars($produto['sku']) ?></td>
                                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                                            <td style="text-align: center;">
                                                <?= formatar_quantidade($produto['estoque_atual'], $produto['unidade_medida']) ?> <?= htmlspecialchars($produto['unidade_medida']) ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?= formatar_quantidade($produto['limite_alerta'], $produto['unidade_medida']) ?>
                                            </td>
                                            <td>
                                                <a href="cadastro_estoque.php?produto_id=<?= $produto['id'] ?>" onclick="iniciarNovoLancamento(<?= $produto['id'] ?>)" class="btn btn-primary btn-sm" title="Lançar Estoque">
                                                     <i class="fas fa-plus"></i> Lançar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <h2 style="margin-top: 40px;"><i class="fas fa-warehouse"></i> Visão Detalhada do Estoque por Status (<?= count($estoque_por_status) ?> Produtos com Lotes)</h2>
                
                <?php if (empty($estoque_por_status)): ?>
                    <p class="feedback-message status-info"><i class="fas fa-box-open"></i> Não há estoque disponível com status de lote definido para exibir.</p>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($estoque_por_status as $produto_id => $produto): 
                            $unidade = htmlspecialchars($produto['unidade_medida']);
                        ?>
                        <div class="estoque-card">
                            <span class="sku-tag">SKU: <?= htmlspecialchars($produto['sku']) ?></span> 
                            <h4 style="margin-bottom: 5px;"><?= htmlspecialchars($produto['nome']) ?></h4>
                            <p style="font-size: 0.85rem; color: var(--text-color-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">
                                Total Detalhado por Status:
                            </p>

                            <?php foreach ($produto['detalhes'] as $detalhe): 
                                $status = $detalhe['status'];
                                $quantidade = $detalhe['quantidade'];

                                // Define a cor para o status
                                $cor_status = 'var(--success-color-dark)';
                                if (strpos($status, 'Bloqueado') !== false) {
                                    $cor_status = 'var(--danger-color)';
                                } elseif (strpos($status, 'VDI') !== false || strpos($status, 'Aguardando') !== false) {
                                    $cor_status = 'var(--warning-color)';
                                }
                            ?>
                                <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                                    <span style="font-weight: 600; color: <?= $cor_status ?>;"><?= $status ?>:</span>
                                    <span style="font-size: 1.1rem; font-weight: 700;">
                                        <?= formatar_quantidade($quantidade, $unidade) ?> <small style="font-weight: 400;"><?= $unidade ?></small>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                </div>
        </div>
    </div>
    
    <script>
        /**
         * Função que armazena o ID do produto para pré-selecionar
         * no formulário de lançamento de estoque (cadastro_estoque.php).
         */
        function iniciarNovoLancamento(produtoId) {
            // Remove dados de formulário anteriores
            localStorage.removeItem('estoqueFormData');
            
            // Cria um objeto de dados com o produto ID
            const newFormData = {
                produto_id: produtoId.toString(),
                fornecedor_id: '',
                data_entrada: '',
                observacao: '',
                lotes: []
            };
            
            // Salva o ID do produto para o 'cadastro_estoque.php' carregar
            localStorage.setItem('estoqueFormData', JSON.stringify(newFormData));
            
            // Retorna true para permitir que a navegação do link aconteça
            return true; 
        }
        
        document.getElementById('menu-toggle')?.addEventListener('click', function() {
            document.body.classList.toggle('menu-open');
        });
    </script>
</body>
</html>