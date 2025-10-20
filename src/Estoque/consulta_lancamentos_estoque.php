<?php
// Linhas para exibir erros internos do servidor (Erro 500) - REMOVA DEPOIS DO TESTE FINAL
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// consulta_lancamentos_estoque.php - Listagem de Lançamentos de Estoque (Rastreabilidade)
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E USUÁRIO "INDÚSTRIA"
if ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Industria') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

// --------------------------------------------------------------------------------
// 1. Definição de Variáveis e Filtros (Inputs do Usuário)
// --------------------------------------------------------------------------------

$lancamentos = [];
$produtos_lista = [];
$fornecedores_lista = [];

// Captura dos parâmetros de filtro (usamos o GET para manter o filtro na URL)
$filtro_produto_id = isset($_GET['produto_id']) ? $_GET['produto_id'] : '';
$filtro_fornecedor_id = isset($_GET['fornecedor_id']) ? $_GET['fornecedor_id'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Captura dos parâmetros de ordenação
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : '';
// Se a direção for diferente de DESC, assume ASC (para segurança e padrão)
$sort_direction = isset($_GET['dir']) && strtoupper($_GET['dir']) === 'DESC' ? 'DESC' : 'ASC';

// --------------------------------------------------------------------------------
// 2. Busca Dados para os Dropdowns de Filtro
// --------------------------------------------------------------------------------

// Buscar todos os Produtos
$sql_produtos = "SELECT id, nome, sku FROM produtos ORDER BY nome ASC";
$result_produtos = $conn->query($sql_produtos);
while ($row = $result_produtos->fetch_assoc()) {
    $produtos_lista[] = $row;
}

// Buscar todos os Fornecedores
$sql_fornecedores = "SELECT id, nome_fantasia FROM fornecedores ORDER BY nome_fantasia ASC";
$result_fornecedores = $conn->query($sql_fornecedores);
while ($row = $result_fornecedores->fetch_assoc()) {
    $fornecedores_lista[] = $row;
}

// --------------------------------------------------------------------------------
// 3. Montagem da Consulta Principal (Aplicando os Filtros e Ordenação)
// --------------------------------------------------------------------------------

$sql_base = "
    SELECT
        l.id,
        l.num_lote,
        l.data_entrada,
        l.data_vencimento,
        l.quantidade,
        l.saldo_atual,
        l.status_lote,
        l.observacao,
        p.nome AS nome_produto,
        p.unidade_medida,
        f.nome_fantasia AS nome_fornecedor,
        u.nome AS nome_usuario  /* <<-- CORREÇÃO APLICADA AQUI: u.nome no lugar de u.nome_completo */
    FROM lotes_estoque l
    JOIN produtos p ON l.produto_id = p.id
    JOIN fornecedores f ON l.fornecedor_id = f.id
    JOIN usuarios u ON l.usuario_id = u.id /* <<-- CORREÇÃO ANTERIOR MANTIDA AQUI: l.usuario_id no lugar de l.usuario_entrada */
    WHERE 1=1
";

$params = [];
$types = "";

// Aplicação dos Filtros
if (!empty($filtro_produto_id)) {
    $sql_base .= " AND l.produto_id = ?";
    $types .= "i";
    $params[] = $filtro_produto_id;
}

if (!empty($filtro_fornecedor_id)) {
    $sql_base .= " AND l.fornecedor_id = ?";
    $types .= "i";
    $params[] = $filtro_fornecedor_id;
}

if (!empty($filtro_data_inicio)) {
    $sql_base .= " AND l.data_entrada >= ?";
    $types .= "s";
    $params[] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $sql_base .= " AND l.data_entrada <= ?";
    $types .= "s";
    // Adiciona o final do dia para incluir o dia final completo
    $params[] = $filtro_data_fim . ' 23:59:59'; 
}

// Lógica de Ordenação
$order_by = "l.data_entrada DESC, l.id DESC"; // Padrão: mais recente primeiro

if ($sort_column === 'data_vencimento') {
    // Se a ordenação for por vencimento, usamos o parâmetro de direção.
    $order_by = "l.data_vencimento {$sort_direction}, l.data_entrada DESC"; 
}

$sql_base .= " ORDER BY {$order_by}";

// --------------------------------------------------------------------------------
// 4. Execução da Consulta Principal
// --------------------------------------------------------------------------------

$stmt_lancamentos = $conn->prepare($sql_base);

if ($stmt_lancamentos === false) {
    die("Erro ao preparar a consulta de lançamentos: " . $conn->error);
}

if (!empty($params)) {
    // Passa os parâmetros de forma dinâmica
    $stmt_lancamentos->bind_param($types, ...$params);
}

$stmt_lancamentos->execute();
$result_lancamentos = $stmt_lancamentos->get_result();

while ($row = $result_lancamentos->fetch_assoc()) {
    $lancamentos[] = $row;
}

$stmt_lancamentos->close();
$conn->close();

// Função auxiliar para formatar datas (opcional)
function formatar_data($data) {
    return $data ? date('d/m/Y', strtotime($data)) : 'N/A';
}

// Função auxiliar para montar o link de ordenação
function get_sort_link_url($column, $current_sort_col, $current_sort_dir) {
    $params = $_GET;
    $params['sort'] = $column;
    
    // Se a coluna já estiver ativa, inverte a direção
    if ($current_sort_col === $column) {
        $params['dir'] = $current_sort_dir === 'ASC' ? 'DESC' : 'ASC';
    } else {
        // Direção padrão para vencimento deve ser ASC (menor data -> vence primeiro)
        $params['dir'] = 'ASC';
    }
    return 'consulta_lancamentos_estoque.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Lançamentos de Estoque | Rastreabilidade</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* (Estilos existentes do formulário de filtro...) */
        .filtro-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow-small);
        }
        .filtro-form .form-group {
            display: flex;
            flex-direction: column;
        }
        .filtro-form label {
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-color-primary);
        }
        .filtro-form select, .filtro-form input[type="date"] {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: #fff;
        }
        .filtro-actions {
            grid-column: 1 / -1; 
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .table-lotes .status-liberado { color: var(--success-color); font-weight: bold; }
        .table-lotes .status-bloqueado { color: var(--danger-color); font-weight: bold; }
        .table-lotes td { vertical-align: middle; }
        
        /* Estilo para links de ordenação */
        .data-table th a {
            color: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .data-table th i.fa-sort, .data-table th i.fa-sort-up, .data-table th i.fa-sort-down {
            font-size: 0.8em; /* Ícone menor */
        }
    </style>
</head>
<body>
    <?php include 'top-header.php'; ?>

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-search"></i> Consulta de Lançamentos (Rastreabilidade)</h1>
                <p>Use os filtros abaixo para buscar lançamentos de estoque específicos por período, produto ou fornecedor.</p>
                
                <form method="GET" action="consulta_lancamentos_estoque.php" class="filtro-form">
                    
                    <div class="form-group">
                        <label for="produto_id">Produto:</label>
                        <select name="produto_id" id="produto_id">
                            <option value="">-- Todos os Produtos --</option>
                            <?php foreach ($produtos_lista as $p): ?>
                                <option 
                                    value="<?= $p['id'] ?>"
                                    <?= ($filtro_produto_id == $p['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['sku']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fornecedor_id">Fornecedor:</label>
                        <select name="fornecedor_id" id="fornecedor_id">
                            <option value="">-- Todos os Fornecedores --</option>
                            <?php foreach ($fornecedores_lista as $f): ?>
                                <option 
                                    value="<?= $f['id'] ?>"
                                    <?= ($filtro_fornecedor_id == $f['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($f['nome_fantasia']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="data_inicio">Data de Entrada (Início):</label>
                        <input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($filtro_data_inicio) ?>">
                    </div>

                    <div class="form-group">
                        <label for="data_fim">Data de Entrada (Fim):</label>
                        <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($filtro_data_fim) ?>">
                    </div>
                    
                    <div class="filtro-actions">
                        <?php if (!empty($filtro_produto_id) || !empty($filtro_fornecedor_id) || !empty($filtro_data_inicio) || !empty($filtro_data_fim)): ?>
                            <a href="consulta_lancamentos_estoque.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar Filtros</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                    </div>

                </form>
                
                <h2>Resultados da Busca (<?= count($lancamentos) ?> Lançamentos)</h2>

                <?php if (empty($lancamentos)): ?>
                    <p class="feedback-message status-warning"><i class="fas fa-exclamation-circle"></i> Nenhum lançamento de estoque encontrado com os filtros aplicados.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table table-lotes">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Lote</th>
                                    <th>Produto (SKU)</th>
                                    <th style="text-align: center;">Entrada</th>
                                    <th style="text-align: center;">
                                        <?php 
                                            $vencimento_link = get_sort_link_url('data_vencimento', $sort_column, $sort_direction);
                                            $icon = '<i class="fas fa-sort"></i>'; // Padrão
                                            if ($sort_column === 'data_vencimento') {
                                                $icon = $sort_direction === 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
                                            }
                                        ?>
                                        <a href="<?= $vencimento_link ?>">
                                            Vencimento
                                            <?= $icon ?>
                                        </a>
                                    </th>
                                    <th style="text-align: center;">Quantidade</th>
                                    <th>Status</th>
                                    <th>Usuário</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lancamentos as $l): 
                                    $status_class = strtolower($l['status_lote']) == 'bloqueado' ? 'status-bloqueado' : 'status-liberado';
                                ?>
                                <tr>
                                    <td>#<?= $l['id'] ?></td>
                                    <td><?= htmlspecialchars($l['num_lote']) ?></td>
                                    <td><?= htmlspecialchars($l['nome_produto']) ?></td>
                                    <td style="text-align: center;"><?= formatar_data($l['data_entrada']) ?></td>
                                    <td style="text-align: center;"><?= formatar_data($l['data_vencimento']) ?></td>
                                    <td style="text-align: center;">
                                        <?= number_format($l['saldo_atual'], 0, ',', '.') ?> <?= htmlspecialchars($l['unidade_medida']) ?>
                                    </td>
                                    <td class="<?= $status_class ?>"><?= htmlspecialchars($l['status_lote']) ?></td>
                                    <td><?= htmlspecialchars($l['nome_usuario']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('menu-toggle')?.addEventListener('click', function() {
            document.body.classList.toggle('menu-open');
        });
    </script>
</body>
</html>