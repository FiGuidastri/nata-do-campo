<?php
// historico_cliente.php - Visão 360 graus do Cliente (Dados + Histórico de Compras).
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E GESTOR
if ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Gestor') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$cliente_selecionado = null;
$historico_vendas = [];
$metricas = [
    'valor_total_gasto' => 0.0,
    'total_pedidos' => 0,
    'ticket_medio' => 0.0,
    'primeira_compra' => 'N/A',
];

// O cliente_id é obtido da URL, após a seleção no Autocomplete (via JavaScript)
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

if ($cliente_id > 0) {
    
    // --- 1. Busca Dados Cadastrais do Cliente ---
    $sql_dados = "
        SELECT 
            c.*, 
            tc.nome AS tipo_cliente_nome 
        FROM clientes c
        JOIN tipos_cliente tc ON c.tipo_cliente_id = tc.id
        WHERE c.id = ?
    ";
    $stmt_dados = $conn->prepare($sql_dados);
    $stmt_dados->bind_param("i", $cliente_id);
    $stmt_dados->execute();
    $result_dados = $stmt_dados->get_result();
    $cliente_selecionado = $result_dados->fetch_assoc();
    $stmt_dados->close();

    if ($cliente_selecionado) {
        
        // --- 2. Busca Histórico de Vendas (Últimos 12 meses) ---
        $data_limite = date('Y-m-d', strtotime('-12 months')); // 1 ano de histórico

        $sql_historico = "
            SELECT 
                v.id AS venda_id,
                v.valor_total,
                v.forma_pagamento,
                v.status,
                DATE_FORMAT(v.data_venda, '%d/%m/%Y') AS data_formatada,
                u.nome AS nome_vendedor
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_validador = u.id 
            WHERE v.cliente_id = ? AND v.data_venda >= ?
            ORDER BY v.data_venda DESC
        ";
        $stmt_historico = $conn->prepare($sql_historico);
        $stmt_historico->bind_param("is", $cliente_id, $data_limite);
        $stmt_historico->execute();
        $result_historico = $stmt_historico->get_result();
        
        while ($row = $result_historico->fetch_assoc()) {
            $historico_vendas[] = $row;
            
            // Calcula Métricas
            $metricas['valor_total_gasto'] += $row['valor_total'];
        }
        $stmt_historico->close();
        
        // --- 3. Calcula Métricas Agregadas ---
        $metricas['total_pedidos'] = count($historico_vendas);

        if ($metricas['total_pedidos'] > 0) {
            $metricas['ticket_medio'] = $metricas['valor_total_gasto'] / $metricas['total_pedidos'];
            
            // Busca a data da primeira compra (historicamente)
            $sql_primeira = "SELECT data_venda FROM vendas WHERE cliente_id = ? ORDER BY data_venda ASC LIMIT 1";
            $stmt_primeira = $conn->prepare($sql_primeira);
            $stmt_primeira->bind_param("i", $cliente_id);
            $stmt_primeira->execute();
            $result_primeira = $stmt_primeira->get_result();
            if ($row_primeira = $result_primeira->fetch_assoc()) {
                $metricas['primeira_compra'] = date('d/m/Y', strtotime($row_primeira['data_venda']));
            }
            $stmt_primeira->close();

        }

    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico 360º | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
    
    <style>
        /* Estilos do Autocomplete */
        .ui-autocomplete {
            z-index: 1050; 
            background: var(--card-bg); 
            border: 1px solid var(--border-color);
            list-style: none;
            padding: 5px 0;
            margin: 0;
            box-shadow: var(--shadow);
            max-height: 300px;
            overflow-y: auto;
        }
        .ui-menu-item-wrapper {
            padding: 8px 10px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .ui-state-active, .ui-state-focus {
            background-color: var(--primary-light) !important;
            color: var(--text-color);
            border: none;
        }

        /* Estilos de Métricas */
        .resumo-cards { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .card { flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; }
        .card h3 { font-size: 1rem; color: #666; margin-top: 0; }
        .card p { font-size: 1.8rem; font-weight: 700; color: var(--primary-color); margin: 5px 0 0; }
        
        /* NOVO ESTILO PARA DADOS CADASTRAIS (4 COLUNAS) */
        .dados-cadastrais { 
            display: grid; 
            /* Define 4 colunas para telas grandes */
            grid-template-columns: repeat(4, 1fr); 
            gap: 15px 30px; 
            background: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: var(--shadow); 
            margin-bottom: 30px; 
        }
        .dados-cadastrais p { margin: 0; font-size: 1rem; }
        .dados-cadastrais strong { color: var(--text-color); display: block; margin-bottom: 3px; font-weight: 600; font-size: 0.9rem; }
        
        /* Responsividade para telas menores que não comportam 4 colunas */
        @media (max-width: 1200px) {
            .dados-cadastrais {
                /* Volta para 2 colunas */
                grid-template-columns: repeat(2, 1fr); 
            }
        }
        @media (max-width: 768px) {
            .dados-cadastrais {
                /* 1 coluna para mobile */
                grid-template-columns: 1fr; 
            }
        }

    </style>
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
                <h1><i class="fas fa-user-tag"></i> Histórico 360º do Cliente</h1>
                <p>Use a busca abaixo para encontrar um cliente e visualizar seu histórico completo de compras e dados cadastrais.</p>
                
                <div style="margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: var(--shadow);">
                    <div class="form-group full-width" style="display: flex; gap: 10px; align-items: center;">
                        <label for="search_cliente" style="width: 100px; min-width: 100px; font-weight: bold;">Cliente:</label>
                        <input type="text" id="search_cliente" class="form-control" 
                               placeholder="Buscar por Nome, Código ou CNPJ..." 
                               style="flex-grow: 1;"
                               value="<?= $cliente_selecionado ? htmlspecialchars($cliente_selecionado['nome_cliente']) : '' ?>">
                    </div>
                </div>
                <?php if ($cliente_selecionado): ?>
                    <h2><i class="fas fa-id-card"></i> Dados Cadastrais</h2>
                    <div class="dados-cadastrais">
                        <p><strong>Código Interno:</strong> <?= htmlspecialchars($cliente_selecionado['codigo_cliente']) ?></p>
                        <p><strong>Nome/Razão Social:</strong> <?= htmlspecialchars($cliente_selecionado['nome_cliente']) ?></p>
                        <p><strong>CNPJ/CPF:</strong> <?= htmlspecialchars($cliente_selecionado['cnpj']) ?></p>
                        <p><strong>Tipo de Cliente:</strong> <span class="status-badge status-secondary"><?= htmlspecialchars($cliente_selecionado['tipo_cliente_nome']) ?></span></p>

                        <p><strong>Telefone:</strong> <?= htmlspecialchars($cliente_selecionado['telefone_contato']) ?></p>
                        <p><strong>E-mail:</strong> <?= htmlspecialchars($cliente_selecionado['email']) ?></p>
                        <p><strong>Cidade/Estado:</strong> <?= htmlspecialchars($cliente_selecionado['cidade']) ?>/<?= htmlspecialchars($cliente_selecionado['estado']) ?></p>
                        <p><strong>Endereço de Entrega:</strong> <?= htmlspecialchars($cliente_selecionado['endereco_entrega']) ?></p>
                    </div>
                    
                    <h2><i class="fas fa-chart-line"></i> Métricas de Vendas (Últimos 12 Meses)</h2>
                    <div class="resumo-cards">
                        <div class="card">
                            <h3>Valor Total Gasto</h3>
                            <p>R$ <?= number_format($metricas['valor_total_gasto'], 2, ',', '.') ?></p>
                        </div>
                        <div class="card">
                            <h3>Total de Pedidos</h3>
                            <p><?= $metricas['total_pedidos'] ?></p>
                        </div>
                        <div class="card">
                            <h3>Ticket Médio</h3>
                            <p>R$ <?= number_format($metricas['ticket_medio'], 2, ',', '.') ?></p>
                        </div>
                        <div class="card">
                            <h3>1ª Compra Registrada</h3>
                            <p><?= $metricas['primeira_compra'] ?></p>
                        </div>
                    </div>

                    <h2><i class="fas fa-history"></i> Histórico Detalhado (12 Meses)</h2>
                    
                    <?php if (empty($historico_vendas)): ?>
                        <p class="feedback-message status-info">Nenhuma venda encontrada para este cliente nos últimos 12 meses.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Venda</th>
                                        <th>Data</th>
                                        <th>Valor Total</th>
                                        <th>Forma Pagamento</th>
                                        <th>Status</th>
                                        <th>Vendedor</th>
                                        <th>Detalhes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historico_vendas as $venda): ?>
                                        <tr>
                                            <td><?= $venda['venda_id'] ?></td>
                                            <td><?= $venda['data_formatada'] ?></td>
                                            <td>R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($venda['forma_pagamento']) ?></td>
                                            <td><span class="status-badge status-<?= $venda['status'] ?>"><?= $venda['status'] ?></span></td>
                                            <td><?= htmlspecialchars($venda['nome_vendedor'] ?? 'N/A') ?></td>
                                            <td><a href="detalhe_venda.php?venda_id=<?= $venda['venda_id'] ?>" class="btn btn-info btn-sm">Ver Itens</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php elseif ($cliente_id > 0): ?>
                    <p class="feedback-message status-error"><i class="fas fa-exclamation-triangle"></i> Cliente com ID <?= $cliente_id ?> não foi encontrado no sistema.</p>
                <?php else: ?>
                    <p class="feedback-message status-info"><i class="fas fa-search"></i> Use o campo de busca acima para encontrar um cliente e visualizar seu histórico completo.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            
            // LÓGICA DO AUTCOMPLETE PARA BUSCA DE CLIENTES (Nome, Código, CNPJ)
            $("#search_cliente").autocomplete({
                source: "buscar_cliente.php", 
                minLength: 3, 
                select: function(event, ui) {
                    // Ao selecionar, redireciona a página para carregar o histórico completo usando o ID
                    window.location.href = `historico_cliente.php?cliente_id=${ui.item.id}`;
                    
                    return false; 
                },
                focus: function(event, ui) {
                    return false; 
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                // Personaliza a aparência de cada item no dropdown do Autocomplete
                return $("<li>")
                    .append("<div>" + item.value + "</div>") 
                    .appendTo(ul);
            };

            // Adiciona um listener para limpar a busca e o histórico
            $('#search_cliente').on('input', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const currentId = urlParams.get('cliente_id');

                // Se o campo de texto for limpo manualmente e havia um ID na URL, remove o ID e recarrega
                if ($(this).val() === '' && currentId) {
                     window.location.href = 'historico_cliente.php';
                }
            });
        });
    </script>
</body>
</html>