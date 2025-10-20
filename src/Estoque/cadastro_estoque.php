<?php
// Exibir erros internos do servidor (OPCIONAL, apenas para debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// cadastro_estoque.php - PÁGINA FINAL DE CADASTRO DE ENTRADA DE ESTOQUE

// 1. Inclusão e Verificação de Sessão
require_once 'verifica_sessao.php';
require_once 'conexao.php';

// RESTRITO APENAS AO ADMIN E USUÁRIO "INDÚSTRIA"
if (!isset($usuario_logado) || ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Industria')) {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$feedback = '';

// --- 2. Carrega Dados para os Selects ---
$produtos = [];
$fornecedores = [];
$status_lote_opcoes = ['Liberado Todos', 'Liberado VDI', 'Bloqueado'];

try {
    // Carregar Produtos
    $sql_produtos = "SELECT id, sku, nome, unidade_medida FROM produtos ORDER BY nome ASC";
    $result_produtos = $conn->query($sql_produtos);
    if ($result_produtos) {
        while ($row = $result_produtos->fetch_assoc()) {
            $produtos[] = $row;
        }
    }

    // Carregar Fornecedores
    $sql_fornecedores = "SELECT id, nome_fantasia FROM fornecedores ORDER BY nome_fantasia ASC";
    $result_fornecedores = $conn->query($sql_fornecedores);
    if ($result_fornecedores) {
        while ($row = $result_fornecedores->fetch_assoc()) {
            $fornecedores[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Erro ao carregar dados: " . $e->getMessage());
}


// --- 3. Lógica de Processamento da Entrada de Estoque (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['produto_id'])) {
    
    $produto_id = intval($_POST['produto_id']);
    $fornecedor_id = intval($_POST['fornecedor_id']);
    $data_entrada = trim($_POST['data_entrada']);
    $observacao = trim($_POST['observacao'] ?? '');
    $lotes = $_POST['lotes'] ?? [];

    // Linha de DEBUG: Útil para ver se o array $lotes está chegando vazio (pode ser removida depois)
    error_log("Dados dos lotes recebidos (POST): " . print_r($lotes, true)); 

    if ($produto_id <= 0 || $fornecedor_id <= 0 || empty($data_entrada) || empty($lotes)) {
        $feedback = '<p class="feedback-message status-error">Preencha todos os campos obrigatórios (Produto, Fornecedor, Data) e adicione ao menos um lote válido.</p>';
    } else {
        
        $conn->begin_transaction();
        $total_movimentado = 0;
        
        try {
            // 1. PREPARAÇÃO DA INSERÇÃO DO LOTE
            $sql_lote = "INSERT INTO lotes_estoque (
                produto_id, fornecedor_id, data_entrada, num_lote, status_lote, 
                data_vencimento, quantidade, saldo_atual, usuario_id, observacao, data_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt_lote = $conn->prepare($sql_lote);
            
            if (!$stmt_lote) {
                    throw new Exception("Erro de preparação de SQL (Lote): " . $conn->error);
            }

            $usuario_id = $usuario_logado['id']; 

            // 2. PREPARAÇÃO DA INSERÇÃO DO HISTÓRICO
            $sql_historico = "INSERT INTO movimentacao_estoque 
                                    (lote_id, produto_id, tipo, quantidade, data_movimentacao, usuario_id, observacao) 
                                    VALUES (?, ?, 'ENTRADA', ?, ?, ?, ?)";
                                    
            $stmt_historico = $conn->prepare($sql_historico);
            if (!$stmt_historico) {
                throw new Exception("Erro na preparação do histórico: " . $conn->error);
            }


            foreach ($lotes as $lote) {
                $num_lote = trim($lote['num_lote'] ?? ''); // Garante que não é null
                $status_lote = trim($lote['status_lote'] ?? 'Liberado Todos');
                $data_vencimento = trim($lote['data_vencimento'] ?? '');
                
                // 🛑 CORREÇÃO DA VIRGULA: GARANTIR QUE O DECIMAL SEJA PONTO
                $quantidade_raw = trim($lote['quantidade'] ?? '0');
                // Substitui vírgula por ponto para o PHP entender o float
                $quantidade_formatada = str_replace(',', '.', $quantidade_raw);
                $quantidade = floatval($quantidade_formatada); 
                $saldo_atual_lote = $quantidade;
                
                // Trata a data de vencimento:
                $data_vencimento_db = null;
                if (!empty($data_vencimento)) {
                    // Verifica se a string está no formato YYYY-MM-DD
                    $date_obj = DateTime::createFromFormat('Y-m-d', $data_vencimento);
                    
                    if ($date_obj && $date_obj->format('Y-m-d') === $data_vencimento) {
                        $data_vencimento_db = $data_vencimento; // Data válida
                    } else {
                        // Data inválida. Força NULL para não quebrar o MySQL
                        $data_vencimento_db = null; 
                    }
                }
                
                // Condição que estava pulando a inserção
                if ($quantidade > 0 && !empty($num_lote)) {
                    
                    // Lotes: i, i, s, s, s, s, d, d, i, s (10 PARÂMETROS)
                    $tipos_param_lote = "iissssddis"; 
                    
                    $stmt_lote->bind_param($tipos_param_lote, 
                        $produto_id,
                        $fornecedor_id, 
                        $data_entrada,
                        $num_lote,
                        $status_lote,
                        $data_vencimento_db,
                        $quantidade,
                        $saldo_atual_lote,
                        $usuario_id,         
                        $observacao
                    );
                    
                    if (!$stmt_lote->execute()) {
                         throw new Exception("Erro ao inserir lote {$num_lote}: " . $stmt_lote->error);
                    }
                    $total_movimentado += $quantidade;

                    // 3. EXECUÇÃO DO BIND DO HISTÓRICO
                    $novo_lote_id = $conn->insert_id;
                    $data_movimentacao = $data_entrada; 
                    
// LINHA 150 (ou próxima)

// ✅ CORREÇÃO FINAL: 6 letras para 6 parâmetros
// i = lote_id (integer)
// i = produto_id (integer)
// d = quantidade (double/float)
// s = data_movimentacao (string)
// i = usuario_id (integer)
// s = observacao (string)
$tipos_param_historico = "iidsis";

// A chamada bind_param deve ficar assim:
$stmt_historico->bind_param($tipos_param_historico, 
    $novo_lote_id,      // i
    $produto_id,        // i
    $quantidade,        // d
    $data_movimentacao, // s
    $usuario_id,        // i
    $observacao         // s
);

                    if (!$stmt_historico->execute()) {
                        throw new Exception("Erro ao registrar histórico: " . $stmt_historico->error);
                    }
                } // Fim da verificação de lote válido
            } // Fim do foreach

            $stmt_lote->close();
            $stmt_historico->close();

            $conn->commit();
            
            $msg_sucesso = "Entrada de estoque e lote(s) registrados com sucesso. Total adicionado: {$total_movimentado}";
            // Limpa o cache e redireciona
            echo '<script>localStorage.removeItem("estoqueFormData"); window.location.href="cadastro_estoque.php?status=success&msg=' . urlencode($msg_sucesso) . '";</script>';
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Erro no processamento de cadastro_estoque.php: " . $e->getMessage()); 
            $feedback = '<p class="feedback-message status-error">Erro ao lançar estoque: ' . htmlspecialchars($e->getMessage()) . '</p>';
            // Fecha statements abertos em caso de erro
            if (isset($stmt_lote) && $stmt_lote->error) $stmt_lote->close();
            if (isset($stmt_historico) && $stmt_historico->error) $stmt_historico->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Estoque | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* CSS Básico para Formulário e Layout */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--spacing-md);}
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        
        #tabela_lotes th:nth-child(2) { width: 30%; } 
        #tabela_lotes th:nth-child(3) { width: 25%; } 
        #tabela_lotes th:nth-child(4) { width: 25%; } 
        #tabela_lotes th:nth-child(5) { width: 15%; } 

        .main-content { padding-bottom: 70px; }
        
        .venda-summary-fixed {
            position: fixed; bottom: 0; left: 0; width: 100%;
            display: flex; align-items: center; justify-content: space-between;
            padding: var(--spacing-sm) var(--spacing-lg); 
            background-color: var(--primary-color); color: white;
            box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.3); z-index: 1000; 
            transition: left 0.3s ease, width 0.3s ease;
        }
        .venda-summary-fixed .label { font-weight: 300; margin-right: var(--spacing-sm); font-size: 0.9rem; }
        .venda-summary-fixed .total-display { font-size: 1.2rem; font-weight: 700; margin-right: var(--spacing-lg); }
        @media (min-width: 992px) {
            .venda-summary-fixed { left: var(--sidebar-collapsed-width, 60px); width: calc(100% - var(--sidebar-collapsed-width, 60px)); }
            body.menu-open .venda-summary-fixed { left: var(--sidebar-width, 250px); width: calc(100% - var(--sidebar-width, 250px)); }
        }
        
        /* Estilos para responsividade da tabela */
        @media screen and (max-width: 768px) {
            .table-responsive { overflow-x: hidden; }
            .data-table { border: 0; }
            .data-table thead { border: none; clip: rect(0 0 0 0); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute; width: 1px; }
            .data-table tr { border-bottom: 3px solid var(--border-color); display: block; margin-bottom: var(--spacing-md); background-color: var(--card-bg); padding: var(--spacing-sm); border-radius: var(--border-radius); }
            .data-table td { border-bottom: 1px solid #ddd; display: block; text-align: right !important; padding-left: 50% !important; position: relative; font-size: 0.9em; padding-top: var(--spacing-sm) !important; padding-bottom: var(--spacing-sm) !important; }
            .data-table td::before { content: attr(data-label); position: absolute; left: var(--spacing-sm); width: 45%; padding-right: 10px; white-space: nowrap; text-align: left; font-weight: bold; }
            .data-table input.form-control, .data-table select.form-control { width: 100%; text-align: right; }
            .data-table td:last-child { border-bottom: 0; text-align: center !important; padding-left: 0 !important; }
            .data-table td:last-child .btn { width: 100%; margin-top: var(--spacing-sm); }
        }

    </style>
    
    <script>
        // O array de status é carregado do PHP
        const STATUS_LOTE_OPCOES = <?= json_encode($status_lote_opcoes); ?>; 
        const PRODUTOS_DATA = <?= json_encode($produtos); ?>;
    </script>
</head>
<body class="<?= isset($usuario_logado) && $usuario_logado['privilegio'] === 'Admin' ? 'menu-open' : ''; ?>">
    <?php include 'top-header.php'; ?> 

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-box-open"></i> Cadastro de Entrada de Estoque (Indústria)</h1>
                
                <?= $feedback ?>

                <form id="form_estoque" method="POST" action="cadastro_estoque.php">
                    
                    <fieldset>
                        <legend>Dados da Entrada</legend>
                        <div class="form-grid">
                            
                            <div class="form-group">
                                <label for="produto_id">Produto:</label>
                                <select id="produto_id" name="produto_id" class="form-control" required onchange="handleProdutoChange()">
                                    <option value="">Selecione o Produto</option>
                                    <?php foreach ($produtos as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-unidade="<?= $p['unidade_medida'] ?>"><?= $p['nome'] ?> (<?= $p['sku'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="fornecedor_id">Fornecedor:</label>
                                <select id="fornecedor_id" name="fornecedor_id" class="form-control" required onchange="saveFormData()">
                                    <option value="">Selecione o Fornecedor</option>
                                    <?php foreach ($fornecedores as $f): ?>
                                        <option value="<?= $f['id'] ?>"><?= $f['nome_fantasia'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="data_entrada">Data da Entrada:</label>
                                <input type="date" id="data_entrada" name="data_entrada" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                             <label for="observacao">Observação:</label>
                             <textarea id="observacao" name="observacao" class="form-control" rows="2"></textarea>
                        </div>
                    </fieldset>

                    <fieldset style="margin-top: var(--spacing-md);">
                        <legend>Detalhes do Lote (Rastreabilidade)</legend>
                        
                        <div style="margin-bottom: var(--spacing-sm); font-weight: bold;">
                            Unidade de Medida: <span id="unidade_medida_display" style="color: var(--primary-color);">N/A</span>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table" id="tabela_lotes">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;" data-label="#">#</th>
                                        <th style="width: 30%;" data-label="Nº do Lote">Nº do Lote</th>
                                        <th style="width: 25%;" data-label="Status Lote">Status Lote</th>
                                        <th style="width: 25%;" data-label="Vencimento">Vencimento</th>
                                        <th style="width: 15%;" data-label="Quantidade">Quantidade</th>
                                        <th style="width: 5%;" data-label="Ação"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-secondary" style="margin-top: var(--spacing-sm);" onclick="addLoteRow()"><i class="fas fa-tag"></i> Adicionar Lote</button>
                    </fieldset>

                    <div class="venda-summary-fixed" style="justify-content: space-between;">
                        <div class="total-display">
                            <div class="label" style="font-size: 0.9rem;">Total de Itens na Entrada:</div>
                            <strong id="quantidade_total_display" style="font-size: 1.2rem;">0,00</strong> <span id="unidade_total_label">UN</span>
                        </div>
                        
                        <button type="submit" form="form_estoque" class="btn btn-primary" style="background-color: var(--success-color); border-color: var(--success-color);">
                            <i class="fas fa-plus-circle"></i> Registrar Entrada
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    
    <script>
        let loteRowCount = 0;
        
        function requiresDecimal(unidade) {
            const decimalUnits = ['KG', 'L', 'M', 'MT', 'CX/KG', 'CXKG']; 
            return decimalUnits.includes(unidade.toUpperCase());
        }

        function handleProdutoChange() {
            const selectProduto = document.getElementById('produto_id');
            const selectedOption = selectProduto.options[selectProduto.selectedIndex];
            const unidadeMedida = selectedOption.getAttribute('data-unidade') || 'UN';
            
            document.getElementById('unidade_medida_display').textContent = unidadeMedida;
            document.getElementById('unidade_total_label').textContent = unidadeMedida;
            
            const isDecimal = requiresDecimal(unidadeMedida);
            for (let i = 1; i <= loteRowCount; i++) {
                const qtdElement = document.getElementById(`lote_quantidade_${i}`);
                if (qtdElement) {
                    qtdElement.step = isDecimal ? 'any' : '1';
                    qtdElement.min = isDecimal ? '0.01' : '1';
                    // Se não for decimal, garante que o valor é inteiro.
                    if (!isDecimal && qtdElement.value && parseFloat(qtdElement.value.replace(',', '.')) > 0) {
                        qtdElement.value = Math.floor(parseFloat(qtdElement.value.replace(',', '.')));
                    }
                }
            }
            calcularTotalLotes();
            saveFormData();
        }

        /**
         * Adiciona uma nova linha para a inserção de um Lote na tabela.
         */
        function addLoteRow(loteData = {}) {
            loteRowCount++;
            const tableBody = document.querySelector('#tabela_lotes tbody');
            const newRow = tableBody.insertRow();
            newRow.id = `lote_row_${loteRowCount}`;
            
            const selectProduto = document.getElementById('produto_id');
            const selectedOption = selectProduto.options[selectProduto.selectedIndex];
            const unidadeMedida = selectedOption.getAttribute('data-unidade') || 'UN';
            const isDecimal = requiresDecimal(unidadeMedida);

            // Coluna # (Índice visual)
            const cellIndex = newRow.insertCell();
            cellIndex.setAttribute('data-label', '#');
            cellIndex.textContent = loteRowCount;

            // Coluna Número do Lote
            const cellLote = newRow.insertCell();
            cellLote.setAttribute('data-label', 'Nº do Lote:');
            const inputLote = document.createElement('input');
            inputLote.type = 'text';
            inputLote.name = `lotes[${loteRowCount}][num_lote]`;
            inputLote.id = `lote_num_${loteRowCount}`;
            inputLote.className = 'form-control';
            inputLote.placeholder = 'Ex: Lote202501';
            inputLote.required = true;
            inputLote.value = loteData.num_lote || '';
            inputLote.oninput = saveFormData;
            cellLote.appendChild(inputLote);

            // Coluna Status do Lote
            const cellStatus = newRow.insertCell();
            cellStatus.setAttribute('data-label', 'Status Lote:');
            const selectStatus = document.createElement('select');
            selectStatus.name = `lotes[${loteRowCount}][status_lote]`;
            selectStatus.id = `lote_status_${loteRowCount}`;
            selectStatus.className = 'form-control';
            selectStatus.onchange = saveFormData;
            
            let statusOptions = '';
            STATUS_LOTE_OPCOES.forEach(status => {
                const selected = (loteData.status_lote || 'Liberado Todos') === status ? 'selected' : '';
                statusOptions += `<option value="${status}" ${selected}>${status}</option>`;
            });
            selectStatus.innerHTML = statusOptions;
            cellStatus.appendChild(selectStatus);

            // Coluna Vencimento
            const cellVencimento = newRow.insertCell();
            cellVencimento.setAttribute('data-label', 'Vencimento:');
            const inputVencimento = document.createElement('input');
            inputVencimento.type = 'date';
            inputVencimento.name = `lotes[${loteRowCount}][data_vencimento]`;
            inputVencimento.id = `lote_vencimento_${loteRowCount}`;
            inputVencimento.className = 'form-control';
            inputVencimento.value = loteData.data_vencimento || '';
            inputVencimento.oninput = saveFormData;
            cellVencimento.appendChild(inputVencimento);
            
            // Coluna Quantidade
            const cellQtd = newRow.insertCell();
            cellQtd.setAttribute('data-label', 'Quantidade:');
            const inputQtd = document.createElement('input');
            inputQtd.type = 'text'; // Alterado para 'text' para melhor controle de vírgulas/pontos no JS
            inputQtd.setAttribute('inputmode', 'decimal');
            inputQtd.name = `lotes[${loteRowCount}][quantidade]`;
            inputQtd.id = `lote_quantidade_${loteRowCount}`;
            inputQtd.className = 'form-control';
            // O passo é controlado pelo JS/HTML5, mas o PHP corrigirá o formato.
            // inputQtd.step = isDecimal ? 'any' : '1'; 
            // inputQtd.min = isDecimal ? '0.01' : '1'; 
            inputQtd.required = true;
            inputQtd.value = loteData.quantidade || '';
            inputQtd.oninput = (e) => {
                 // Permite vírgula e ponto na entrada
                e.target.value = e.target.value.replace(/[^0-9,.]/g, ''); 
                calcularTotalLotes(loteRowCount);
            };
            cellQtd.appendChild(inputQtd);

            // Coluna Ação (Remover)
            const cellAction = newRow.insertCell();
            cellAction.setAttribute('data-label', 'Ação:');
            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.className = 'btn btn-danger btn-sm';
            btnRemove.innerHTML = '<i class="fas fa-trash"></i>';
            btnRemove.onclick = () => removeLoteRow(loteRowCount);
            cellAction.appendChild(btnRemove);

            calcularTotalLotes(); 
        }

        /**
         * Remove uma linha de lote da tabela e recalcula o total.
         */
        function removeLoteRow(index) {
            const row = document.getElementById(`lote_row_${index}`);
            if (row) {
                row.remove(); 
                calcularTotalLotes();
                saveFormData(); 
            }
        }

        function calcularTotalLotes() {
            let totalGeral = 0;
            const tableBody = document.querySelector('#tabela_lotes tbody');
            
            Array.from(tableBody.rows).forEach(row => {
                const rowIndex = parseInt(row.id.replace('lote_row_', ''));
                
                const quantidadeElement = document.getElementById(`lote_quantidade_${rowIndex}`);

                if (quantidadeElement) {
                    // Limpa a string de quantidade para cálculo (troca vírgula por ponto)
                    const rawValue = quantidadeElement.value.replace(',', '.');
                    const quantidade = parseFloat(rawValue || 0);
                    totalGeral += quantidade;
                }
            });

            // Exibe com vírgula para o usuário
            document.getElementById('quantidade_total_display').textContent = totalGeral.toFixed(2).replace('.', ',');
            saveFormData();
        }

        /**
         * Salva todos os dados do formulário no Local Storage.
         */
        function saveFormData() {
            const formData = {
                produto_id: document.getElementById('produto_id').value,
                fornecedor_id: document.getElementById('fornecedor_id').value,
                data_entrada: document.getElementById('data_entrada').value,
                observacao: document.getElementById('observacao').value,
                lotes: []
            };

            const tableBody = document.querySelector('#tabela_lotes tbody');
            Array.from(tableBody.rows).forEach(row => {
                const rowIndex = parseInt(row.id.replace('lote_row_', ''));
                
                const numLote = document.getElementById(`lote_num_${rowIndex}`);
                const statusLote = document.getElementById(`lote_status_${rowIndex}`);
                const vencimento = document.getElementById(`lote_vencimento_${rowIndex}`);
                const quantidade = document.getElementById(`lote_quantidade_${rowIndex}`);

                if (numLote && quantidade) {
                    formData.lotes.push({
                        num_lote: numLote.value,
                        status_lote: statusLote.value,
                        data_vencimento: vencimento.value,
                        // Salva o valor exato que o usuário digitou (com vírgula ou ponto)
                        quantidade: quantidade.value 
                    });
                }
            });
            localStorage.setItem('estoqueFormData', JSON.stringify(formData));
        }

        /**
         * Carrega os dados salvos no Local Storage.
         */
        function loadFormData() {
            const savedData = localStorage.getItem('estoqueFormData');
            
            if (!savedData) {
                addLoteRow({quantidade: '1'}); 
                return;
            }

            const data = JSON.parse(savedData);
            
            document.getElementById('produto_id').value = data.produto_id || '';
            document.getElementById('fornecedor_id').value = data.fornecedor_id || '';
            document.getElementById('data_entrada').value = data.data_entrada || '<?= date('Y-m-d') ?>';
            document.getElementById('observacao').value = data.observacao || ''; 

            if (data.produto_id) {
                handleProdutoChange();
            }

            if (data.lotes && data.lotes.length > 0) {
                // Remove a linha padrão se ela foi adicionada antes do carregamento
                const defaultRow = document.getElementById('lote_row_1');
                if (defaultRow && loteRowCount === 1) { 
                     defaultRow.remove();
                     loteRowCount = 0; 
                }
                
                data.lotes.forEach(lote => {
                    addLoteRow(lote); 
                });
            } else if(loteRowCount === 0) {
                addLoteRow({quantidade: '1'});
            }
            
            calcularTotalLotes();
        }


        // --- INICIALIZAÇÃO ---
        $(document).ready(function() {
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') && urlParams.get('status') === 'success' && urlParams.has('msg')) {
                const msg = urlParams.get('msg');
                Swal.fire({
                    icon: 'success',
                    title: 'Entrada Registrada!',
                    html: decodeURIComponent(msg), 
                    timer: 5000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            loadFormData();
            window.addEventListener('beforeunload', saveFormData);

            // Adiciona listener para salvar dados sempre que houver mudança nos inputs e selects
            $('#form_estoque').on('input change', 'select, input, textarea', saveFormData);
        });
    </script>
    <script>
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.body.classList.toggle('menu-open');
    });
    </script>
</body>
</html>
<?php 
// Garante que a conexão seja fechada se ainda estiver aberta no final do script
if (isset($conn)) $conn->close();
?>