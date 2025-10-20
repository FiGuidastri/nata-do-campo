<?php
// clube_nata.php - Gerenciamento e visualização de membros e pontuações do Clube Nata
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E GESTOR
if ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Gestor') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$feedback = '';

// --- Carrega Tipos de Dados ---
$clientes_nao_membros = [];
$clientes_membros = []; // Membros existentes (para Indicação e Resgate)
$recompensas_ativas = []; // Recompensas disponíveis

try {
    // 1. Clientes que NÃO estão no clube (para o cadastro)
    $sql_nao_membros = "SELECT id, nome_cliente FROM clientes 
                        WHERE id NOT IN (SELECT cliente_id FROM clube_nata_membros)
                        ORDER BY nome_cliente ASC";
    $result_nao_membros = $conn->query($sql_nao_membros);
    while ($row = $result_nao_membros->fetch_assoc()) {
        $clientes_nao_membros[] = $row;
    }

    // 2. Clientes que JÁ SÃO membros (para Indicação e Resgate)
    $sql_membros = "SELECT c.id, c.nome_cliente, cnm.pontuacao FROM clientes c 
                     JOIN clube_nata_membros cnm ON c.id = cnm.cliente_id 
                     ORDER BY c.nome_cliente ASC";
    $result_membros = $conn->query($sql_membros);
    while ($row = $result_membros->fetch_assoc()) {
        $clientes_membros[] = $row;
    }
    
    // 3. Recompensas Ativas (para o resgate)
    $sql_recompensas = "SELECT id, nome, custo_pontos FROM clube_nata_recompensas WHERE status='Ativo' ORDER BY custo_pontos ASC";
    $result_recompensas = $conn->query($sql_recompensas);
    while ($row = $result_recompensas->fetch_assoc()) {
        $recompensas_ativas[] = $row;
    }
    
} catch (Exception $e) {
    $feedback = '<p class="feedback-message status-error">Erro ao carregar dados do banco: ' . $e->getMessage() . '</p>';
}


// --- Lógica de Processamento: Adicionar Novo Membro ao Clube ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'adicionar_membro') {
    $cliente_id = intval($_POST['cliente_id'] ?? 0);
    $indicado_por_id = intval($_POST['indicado_por_id'] ?? 0);
    $conn->begin_transaction();

    try {
        if ($cliente_id <= 0) {
            throw new Exception("Selecione um cliente válido.");
        }
        
        $indicado_por_valido = ($indicado_por_id > 0) ? $indicado_por_id : null;
        
        // A. Adiciona o cliente à tabela de membros
        $sql_insert = "INSERT INTO clube_nata_membros (cliente_id, indicado_por_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("ii", $cliente_id, $indicado_por_valido);
        $stmt->execute();
        $stmt->close();
        
        // B. Dá 0.5 ponto ao INDICADOR, se houver
        if ($indicado_por_id > 0) {
            $sql_update_indicador = "UPDATE clube_nata_membros 
                                     SET pontuacao = pontuacao + 0.5 
                                     WHERE cliente_id = ?";
            $stmt_update = $conn->prepare($sql_update_indicador);
            $stmt_update->bind_param("i", $indicado_por_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        $conn->commit();
        // REDIRECIONAMENTO COM MENSAGEM DE SUCESSO PARA SWEETALERT
        $msg_sucesso = "Cliente adicionado ao Clube Nata. Indicador recebeu o bônus de 0.5 ponto, se aplicável.";
        header("Location: clube_nata.php?status=success&msg=" . urlencode($msg_sucesso));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $feedback = '<p class="feedback-message status-error">Erro ao adicionar membro: ' . $e->getMessage() . '</p>';
    }
}


// --- Lógica de Processamento: Resgate de Recompensa ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'resgatar_recompensa') {
    $cliente_id = intval($_POST['resgate_cliente_id'] ?? 0);
    $recompensa_id = intval($_POST['recompensa_id'] ?? 0);
    $conn->begin_transaction();
    
    try {
        if ($cliente_id <= 0 || $recompensa_id <= 0) {
            throw new Exception("Selecione o Cliente e a Recompensa.");
        }

        // 1. Busca o custo da recompensa e a pontuação atual do cliente
        $sql_info = "SELECT r.custo_pontos, cnm.pontuacao, r.nome AS recompensa_nome FROM clube_nata_recompensas r
                     JOIN clube_nata_membros cnm ON cnm.cliente_id = ? 
                     WHERE r.id = ? AND r.status='Ativo'";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("ii", $cliente_id, $recompensa_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $info = $result_info->fetch_assoc();
        $stmt_info->close();

        if (!$info) {
            throw new Exception("Recompensa inativa ou cliente não é membro.");
        }
        
        $custo = intval($info['custo_pontos']);
        $saldo_atual = floatval($info['pontuacao']);
        $recompensa_nome = $info['recompensa_nome'];

        if ($saldo_atual < $custo) {
            throw new Exception("O cliente não possui pontos suficientes para o resgate. Saldo: " . number_format($saldo_atual, 2) . " pontos.");
        }
        
        // 2. Registra o Resgate
        $sql_insert_resgate = "INSERT INTO clube_nata_resgates (cliente_id, recompensa_id, pontos_utilizados) VALUES (?, ?, ?)";
        $stmt_resgate = $conn->prepare($sql_insert_resgate);
        $stmt_resgate->bind_param("iii", $cliente_id, $recompensa_id, $custo);
        $stmt_resgate->execute();
        $stmt_resgate->close();
        
        // 3. Debita os pontos do cliente (uso negativo do custo)
        $sql_debito = "UPDATE clube_nata_membros SET pontuacao = pontuacao - ? WHERE cliente_id = ?";
        $stmt_debito = $conn->prepare($sql_debito);
        $stmt_debito->bind_param("di", $custo, $cliente_id);
        $stmt_debito->execute();
        $stmt_debito->close();

        $conn->commit();
        // REDIRECIONAMENTO COM MENSAGEM DE SUCESSO PARA SWEETALERT
        $msg_sucesso = "Resgate de **{$recompensa_nome}** realizado com sucesso! Pontos debitados: {$custo}.";
        header("Location: clube_nata.php?status=success&msg=" . urlencode($msg_sucesso));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $feedback = '<p class="feedback-message status-error">Erro no Resgate: ' . $e->getMessage() . '</p>';
    }
}


// --- Carrega Lista de Membros e Pontuação Atual (Para exibição após todas as operações) ---
$membros_clube = [];
$sql_membros_lista = "SELECT 
                         c.id,
                         c.nome_cliente, 
                         c.cnpj,
                         cnm.pontuacao,
                         cnm.data_adesao,
                         COALESCE(i.nome_cliente, 'N/A') AS indicado_por_nome
                       FROM clube_nata_membros cnm
                       JOIN clientes c ON cnm.cliente_id = c.id
                       LEFT JOIN clientes i ON cnm.indicado_por_id = i.id
                       ORDER BY cnm.pontuacao DESC, c.nome_cliente ASC";

$result_membros_lista = $conn->query($sql_membros_lista);
while ($row = $result_membros_lista->fetch_assoc()) {
    $membros_clube[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento do Clube Nata | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* CSS ESPECÍFICO PARA ORGANIZAR OS FORMULÁRIOS (Nova Estrutura Grid) */
        .club-management-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Duas colunas iguais */
            gap: var(--spacing-lg); /* Usa o espaçamento da sua folha de estilo */
            margin-top: var(--spacing-md);
        }
        @media (max-width: 900px) {
            .club-management-grid {
                grid-template-columns: 1fr; /* Empilha em telas menores */
            }
        }
        .form-section {
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg); /* Usa a cor de fundo de card */
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .form-section h2 {
            margin-top: 0;
            color: var(--secondary-color); /* Usa uma cor de destaque para o título da seção */
            border-bottom: 2px solid var(--border-color);
            padding-bottom: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .club-points, .saldo-membro { 
            font-weight: 700;
            color: var(--primary-color);
        }
        .saldo-membro {
            margin-top: 5px;
            display: block;
            font-size: 0.9rem;
        }
        .saldo-membro.custo {
            color: var(--danger-color);
        }
        .club-link-container {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
            text-align: center;
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
                <h1><i class="fas fa-trophy"></i> Gerenciamento do Clube Nata</h1>
                <p>Adesão, Resgate de Pontos e Visão de Ranking.</p>
                
                <?= $feedback ?> <div class="club-management-grid">
                    
                    <div class="form-section">
                        <h2><i class="fas fa-user-plus"></i> Adesão de Novo Membro</h2>
                        <form method="POST" action="clube_nata.php">
                            <input type="hidden" name="action" value="adicionar_membro">
                            
                            <div class="form-group">
                                <label for="cliente_id">Cliente a Adicionar:</label>
                                <select id="cliente_id" name="cliente_id" class="form-control" required>
                                    <option value="">Selecione o Cliente (Não Membro)</option>
                                    <?php foreach ($clientes_nao_membros as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome_cliente']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="indicado_por_id">Indicado Por (Opcional - Membro Existente):</label>
                                <select id="indicado_por_id" name="indicado_por_id" class="form-control">
                                    <option value="0">Ninguém (Adesão Direta)</option>
                                    <?php foreach ($clientes_membros as $membro): ?>
                                        <option value="<?= $membro['id'] ?>"><?= htmlspecialchars($membro['nome_cliente']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary full-width">
                                <i class="fas fa-check-circle"></i> Registrar Adesão
                            </button>
                        </form>
                    </div>

                    <div class="form-section">
                        <h2><i class="fas fa-gift"></i> Resgate de Recompensa</h2>
                        <form method="POST" action="clube_nata.php">
                            <input type="hidden" name="action" value="resgatar_recompensa">
                            
                            <div class="form-group">
                                <label for="resgate_cliente_id">Membro que irá Resgatar:</label>
                                <select id="resgate_cliente_id" name="resgate_cliente_id" class="form-control" required>
                                    <option value="">Selecione o Membro</option>
                                    <?php foreach ($clientes_membros as $membro): ?>
                                        <option 
                                            value="<?= $membro['id'] ?>" 
                                            data-pontos="<?= $membro['pontuacao'] ?>"
                                        >
                                            <?= htmlspecialchars($membro['nome_cliente']) ?> 
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span id="saldo_atual" class="saldo-membro" style="display: none;">Saldo: 0.00 pts</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="recompensa_id">Recompensa (Custo em Pontos):</label>
                                <select id="recompensa_id" name="recompensa_id" class="form-control" required>
                                    <option value="">Selecione a Recompensa</option>
                                    <?php foreach ($recompensas_ativas as $recompensa): ?>
                                        <option 
                                            value="<?= $recompensa['id'] ?>" 
                                            data-custo="<?= $recompensa['custo_pontos'] ?>"
                                        >
                                            <?= htmlspecialchars($recompensa['nome']) ?> (Custo: <?= number_format($recompensa['custo_pontos'], 0, ',', '.') ?> pts)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span id="custo_resgate" class="saldo-membro custo" style="display: none;">Custo: 0 pts</span>
                            </div>

                            <a href="cadastro_recompensa.php" class="btn btn-secondary btn-sm" style="margin-bottom: var(--spacing-sm);"><i class="fas fa-cog"></i> Gerenciar Catálogo de Recompensas</a>
                            <button type="submit" class="btn btn-success full-width" id="btn_resgate" disabled>
                                <i class="fas fa-star"></i> Efetuar Resgate
                            </button>
                        </form>
                    </div>
                </div>

                <h2 style="margin-top: 30px;"><i class="fas fa-chart-line"></i> Ranking e Pontuação dos Membros</h2>
                <div class="club-link-container">
                    <a href="relatorio_resgates.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-clipboard-list"></i> Ver Relatório Completo de Resgates
                    </a>
                </div>

                <div class="table-responsive" style="margin-top: 20px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>CNPJ/CPF</th>
                                <th>Indicado Por</th>
                                <th>Pontuação Atual</th>
                                <th>Data Adesão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($membros_clube)): ?>
                                <tr><td colspan="5" style="text-align: center;">Nenhum membro cadastrado no clube.</td></tr>
                            <?php else: ?>
                                <?php foreach ($membros_clube as $membro): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($membro['nome_cliente']) ?></td>
                                        <td><?= htmlspecialchars($membro['cnpj']) ?></td>
                                        <td><?= htmlspecialchars($membro['indicado_por_nome']) ?></td>
                                        <td class="club-points"><?= number_format($membro['pontuacao'], 2, ',', '.') ?> pts</td>
                                        <td><?= (new DateTime($membro['data_adesao']))->format('d/m/Y') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">2025 &copy; Comunicação & Marketing | Grupo AMB</footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const membroSelect = document.getElementById('resgate_cliente_id');
            const recompensaSelect = document.getElementById('recompensa_id');
            const saldoSpan = document.getElementById('saldo_atual');
            const custoSpan = document.getElementById('custo_resgate');
            const btnResgate = document.getElementById('btn_resgate');

            function checkResgate() {
                const selectedMembro = membroSelect.options[membroSelect.selectedIndex];
                const selectedRecompensa = recompensaSelect.options[recompensaSelect.selectedIndex];

                // Pega dados do atributo 'data-'
                const saldo = parseFloat(selectedMembro.dataset.pontos || 0);
                const custo = parseFloat(selectedRecompensa.dataset.custo || 0);

                // Formatação
                const saldoFormatado = saldo.toFixed(2).replace('.', ',');
                const custoFormatado = custo.toFixed(0).replace('.', ',');

                // Atualiza o saldo e o custo na interface
                if (saldo > 0 && selectedMembro.value !== "") {
                    saldoSpan.textContent = `Saldo: ${saldoFormatado} pts`;
                    saldoSpan.style.display = 'block';
                } else {
                    saldoSpan.style.display = 'none';
                }
                
                if (custo > 0 && selectedRecompensa.value !== "") {
                    custoSpan.textContent = `Custo: ${custoFormatado} pts`;
                    custoSpan.style.display = 'block';
                } else {
                    custoSpan.style.display = 'none';
                }

                // Habilita/Desabilita o botão
                const podeResgatar = saldo >= custo && saldo > 0 && custo > 0;
                btnResgate.disabled = !podeResgatar;
                
                btnResgate.classList.remove('btn-danger', 'btn-success');

                if (!podeResgatar && custo > 0 && saldo > 0 && saldo < custo) {
                    btnResgate.textContent = 'Pontos Insuficientes!';
                    btnResgate.classList.add('btn-danger');
                } else {
                    btnResgate.textContent = 'Efetuar Resgate';
                    // Adiciona o sucesso, mas se estiver desabilitado (por falta de seleção), a cor não importa
                    btnResgate.classList.add('btn-success');
                }
                
                if (membroSelect.value === "" || recompensaSelect.value === "") {
                     btnResgate.disabled = true;
                     btnResgate.textContent = 'Efetuar Resgate';
                     btnResgate.classList.add('btn-success');
                }
            }
            
            // Adiciona evento change para ambos os selects
            membroSelect.addEventListener('change', checkResgate);
            recompensaSelect.addEventListener('change', checkResgate);
            
            // Verifica o estado inicial
            checkResgate(); 

            // --- SWEETALERT PARA MENSAGENS DE SUCESSO (APÓS REDIRECIONAMENTO) ---
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') && urlParams.get('status') === 'success' && urlParams.has('msg')) {
                const msg = urlParams.get('msg');
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    // Permite HTML para exibir texto em negrito (markdown **texto**)
                    html: decodeURIComponent(msg), 
                    timer: 5000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                // Limpa os parâmetros GET para que o alerta não apareça em um recarregamento manual
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>