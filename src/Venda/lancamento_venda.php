<?php
// lancamento_venda.php - Interface para o Vendedor lançar novas vendas/transações.
require_once 'verifica_sessao.php';
require_once 'conexao.php';

// Restringe acesso a Vendedor e Admin.
if ($usuario_logado['privilegio'] !== 'Vendedor' && $usuario_logado['privilegio'] !== 'Admin') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$feedback = '';

// --- 1. Carrega Dados para os Selects (Dropdowns) ---
$produtos = [];
try {
    $sql_produtos = "SELECT id, sku, nome, unidade_medida FROM produtos ORDER BY nome ASC";
    $result_produtos = $conn->query($sql_produtos);
    if ($result_produtos) {
        while ($row = $result_produtos->fetch_assoc()) {
            $produtos[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Erro ao carregar produtos: " . $e->getMessage());
}

$formas_pagamento = ['Pix', 'Dinheiro', 'Cartão', 'Faturamento'];
// Opções para Tipo de Transação
$tipos_transacao = ['Venda', 'Bonificação', 'Troca'];


// --- 2. Lógica de Processamento da Venda (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cliente_id'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $forma_pagamento = trim($_POST['forma_pagamento']);
    $data_entrega = trim($_POST['data_entrega']);
    
    $valor_base_ou_custo = floatval($_POST['valor_total']);
    
    $tipo_transacao = trim($_POST['tipo_transacao']);
    $itens = $_POST['itens'] ?? [];

    $valor_financeiro_venda = $valor_base_ou_custo;
    if (in_array($tipo_transacao, ['Bonificação', 'Troca'])) {
        $valor_financeiro_venda = 0.00;
        if (in_array($forma_pagamento, ['Pix', 'Dinheiro', 'Cartão'])) {
             $forma_pagamento = 'Bonificação/Troca'; 
        }
    }
    
    if ($cliente_id <= 0 || empty($forma_pagamento) || empty($data_entrega) || empty($tipo_transacao) || $valor_base_ou_custo <= 0 || empty($itens)) {
        $feedback = '<p class="feedback-message status-error">Preencha todos os campos e adicione ao menos um item válido à transação.</p>';
    } else {
        $conn->begin_transaction();
        try {
            $sql_venda = "INSERT INTO vendas (cliente_id, usuario_vendedor, data_venda, forma_pagamento, data_entrega, valor_total, tipo_transacao, status) VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Pendente')";
            $stmt_venda = $conn->prepare($sql_venda);
            $vendedor_id = $usuario_logado['id'];
            
            $stmt_venda->bind_param("iissds", $cliente_id, $vendedor_id, $forma_pagamento, $data_entrega, $valor_financeiro_venda, $tipo_transacao);
            
            if (!$stmt_venda->execute()) {
                throw new Exception("Erro ao inserir venda: " . $stmt_venda->error);
            }

            $venda_id = $conn->insert_id;
            $stmt_venda->close();

            $sql_item = "INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);

            foreach ($itens as $item) {
                $produto_id = intval($item['produto_id']);
                $quantidade = floatval($item['quantidade']);
                $preco_unitario = floatval($item['preco_unitario']); 
                
                if ($produto_id > 0 && $quantidade > 0 && $preco_unitario >= 0) {
                    $stmt_item->bind_param("iidd", $venda_id, $produto_id, $quantidade, $preco_unitario);
                    if (!$stmt_item->execute()) {
                        throw new Exception("Erro ao inserir item: " . $stmt_item->error);
                    }
                }
            }
            $stmt_item->close();

            $conn->commit();
            
            $msg_sucesso = 'Transação lançada com sucesso! ID: ' . $venda_id;
            echo '<script>localStorage.removeItem("vendaFormData"); window.location.href="lancamento_venda.php?status=success&msg=' . urlencode($msg_sucesso) . '";</script>';
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $feedback = '<p class="feedback-message status-error">Erro ao lançar a transação: ' . $e->getMessage() . '</p>';
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
    <title>Lançamento de Venda | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Estilos de layout e autocomplete */
        .ui-autocomplete { z-index: 1050; background: var(--card-bg); border: 1px solid var(--border-color); list-style: none; padding: 5px 0; margin: 0; box-shadow: var(--shadow); max-height: 300px; overflow-y: auto;}
        .ui-menu-item-wrapper { padding: 8px 10px; cursor: pointer; font-size: 0.9rem;}
        .ui-state-active, .ui-state-focus { background-color: var(--primary-light) !important; color: var(--text-color); border: none;}
        
        /* Ajuste do GRID para 4 colunas: Cliente, Tipo Transação, Pagamento, Entrega */
        .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-md);}
        @media (max-width: 1024px) {
            .form-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        /* ---------------------------------------------------------------------- */
        /* CSS OTIMIZADO: RESUMO FLUTUANTE FIXO (Vendedor) */
        /* ---------------------------------------------------------------------- */
        .venda-summary-fixed {
            position: fixed;
            bottom: 0; 
            left: 0; 
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: var(--spacing-sm) var(--spacing-lg); 
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.3);
            z-index: 1000; 
            transition: left 0.3s ease, width 0.3s ease;
            min-height: 60px;
        }
        .venda-summary-fixed .label { font-weight: 300; margin-right: var(--spacing-sm); font-size: 1rem; }
        .venda-summary-fixed .total-display { font-size: 1.4rem; font-weight: 700; margin-right: var(--spacing-lg); }

        @media (min-width: 992px) {
            /* Ajusta a barra fixa para respeitar a largura da sidebar */
            .venda-summary-fixed { 
                left: var(--sidebar-collapsed-width, 60px); 
                width: calc(100% - var(--sidebar-collapsed-width, 60px)); 
            }
            body.menu-open .venda-summary-fixed { 
                left: var(--sidebar-width, 250px); 
                width: calc(100% - var(--sidebar-width, 250px)); 
            }
            .main-content .container { padding-bottom: 70px; } 
        }
        
        /* ---------------------------------------------------------------------- */
        /* CORREÇÃO FINAL PARA CELULAR: Garante espaço inferior suficiente para o footer fixo */
        /* ---------------------------------------------------------------------- */
        @media (max-width: 991px) {
            /* Padding generoso para garantir que nada fique coberto */
            .main-content .container {
                padding-bottom: 150px; 
            }
        }
        
        /* ---------------------------------------------------------------------- */
        /* CORREÇÃO PARA QUEBRA DE LINHA DA TABELA (ITENS DO PEDIDO) - MOBILE */
        /* ---------------------------------------------------------------------- */
        @media screen and (max-width: 768px) {
            body {
                overflow-x: hidden;
            }

            .data-table {
                border: 0;
                width: 100%; 
            }

            .data-table thead {
                display: none; 
            }

            .data-table tr {
                background-color: var(--card-bg);
                border: 1px solid var(--border-color);
                margin-bottom: var(--spacing-sm);
                display: block; 
                padding: var(--spacing-sm);
                border-radius: var(--border-radius);
            }

            .data-table td {
                display: block; 
                text-align: right; 
                border: none;
                padding: 5px 0; 
            }

            .data-table td::before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
                text-transform: uppercase;
                margin-right: var(--spacing-sm);
                color: var(--text-color-light);
            }
            
            .data-table td .form-control {
                width: 100%;
                display: block;
                box-sizing: border-box; 
            }

            .data-table td:last-child {
                text-align: center;
                margin-top: var(--spacing-sm);
                padding-top: var(--spacing-sm);
                border-top: 1px solid var(--border-color-light);
            }
        }
        
    </style>
    
    <script>
        const PRODUTOS_DATA = <?= json_encode($produtos); ?>;
    </script>
</head>
<body class="<?= isset($usuario_logado) && $usuario_logado['privilegio'] === 'Admin' ? 'menu-open' : ''; ?>">
    <?php include 'top-header.php'; ?> 

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-cart-plus"></i> Lançamento de Nova Venda | Comercial</h1>
                
                <?= $feedback ?>

                <form id="form_venda" method="POST" action="lancamento_venda.php">
                    
                    <fieldset>
                        <legend>Dados do Cliente e Logística</legend>
                        <div class="form-grid">
                            
                            <div class="form-group">
                                <label for="search_cliente">Buscar (Nome, Cód. ou CNPJ):</label>
                                <input type="text" id="search_cliente" class="form-control" placeholder="Digite para buscar e selecionar..." required>
                                <input type="hidden" id="cliente_id" name="cliente_id" value="" required>
                            </div>
                            <input type="hidden" id="tipo_cliente_id_hidden" value="">

                            <div class="form-group">
                                <label for="tipo_transacao">Tipo de Transação:</label>
                                <select id="tipo_transacao" name="tipo_transacao" class="form-control" required onchange="calcularTotalVenda()">
                                    <?php foreach ($tipos_transacao as $tt): ?>
                                        <option value="<?= $tt ?>" <?= $tt === 'Venda' ? 'selected' : '' ?>><?= $tt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="forma_pagamento">Forma de Pagamento:</label>
                                <select id="forma_pagamento" name="forma_pagamento" class="form-control" required>
                                    <?php foreach ($formas_pagamento as $fp): ?>
                                        <option value="<?= $fp ?>"><?= $fp ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="data_entrega">Data de Entrega:</label>
                                <input type="date" id="data_entrega" name="data_entrega" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset style="margin-top: var(--spacing-md);">
                        <legend>Itens do Pedido</legend>
                        <div class="table-responsive">
                            <table class="data-table" id="tabela_itens">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;" data-label="Produto">Produto (SKU)</th>
                                        <th style="width: 15%;" data-label="Unidade">Unidade</th>
                                        <th style="width: 15%;" data-label="Preço Unitário">Preço Unitário</th>
                                        <th style="width: 15%;" data-label="Quantidade">Quantidade</th>
                                        <th style="width: 10%;" data-label="Subtotal">Subtotal</th>
                                        <th style="width: 5%;" data-label="Ação"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-secondary" style="margin-top: var(--spacing-sm);" onclick="addProdutoRow()"><i class="fas fa-cart-plus"></i> Adicionar Item</button>
                    </fieldset>

                    <input type="hidden" id="valor_total" name="valor_total" value="0.00"> 
                </form>
            </div>
        </div>
    </div>

    <div class="venda-summary-fixed">
        <div class="label">Valor Financeiro Total:</div>
        <div class="total-display">
            <strong id="valor_total_display">R$ 0,00</strong>
        </div>
        <button type="submit" form="form_venda" class="btn btn-primary" style="background-color: var(--secondary-color); border-color: var(--secondary-color);">
            <i class="fas fa-check-circle"></i> Lançar Transação
        </button>
    </div>

    <script>
        let rowCount = 0;
        
        function requiresDecimal(unidade) {
            const decimalUnits = ['KG', 'L', 'M', 'MT', 'CX/KG', 'CXKG']; 
            return decimalUnits.includes(unidade.toUpperCase());
        }

        /**
         * Adiciona uma nova linha para a inserção de um produto na tabela.
         */
        function addProdutoRow() {
            rowCount++;
            const tableBody = document.querySelector('#tabela_itens tbody');
            const newRow = tableBody.insertRow();
            newRow.id = `row_${rowCount}`;
            
            // Coluna Produto (Select)
            const cellProduto = newRow.insertCell();
            cellProduto.setAttribute('data-label', 'Produto:'); 
            const selectProduto = document.createElement('select');
            selectProduto.name = `itens[${rowCount}][produto_id]`;
            selectProduto.id = `produto_${rowCount}`;
            selectProduto.className = 'form-control';
            selectProduto.required = true;
            selectProduto.onchange = () => handleProdutoChange(rowCount);

            let options = '<option value="">Selecione o Produto</option>';
            PRODUTOS_DATA.forEach(p => {
                options += `<option value="${p.id}" data-unidade="${p.unidade_medida}">${p.nome} (${p.sku})</option>`;
            });
            selectProduto.innerHTML = options;
            cellProduto.appendChild(selectProduto);

            // Coluna Unidade de Medida
            const cellUnidade = newRow.insertCell();
            cellUnidade.setAttribute('data-label', 'Unidade:');
            cellUnidade.id = `unidade_display_${rowCount}`;
            cellUnidade.style.whiteSpace = 'nowrap';
            
            // Coluna Preço Unitário
            const cellPreco = newRow.insertCell();
            cellPreco.setAttribute('data-label', 'Preço Unitário:');
            const inputPreco = document.createElement('input');
            inputPreco.type = 'number';
            inputPreco.setAttribute('inputmode', 'decimal');
            inputPreco.name = `itens[${rowCount}][preco_unitario]`;
            inputPreco.id = `preco_${rowCount}`;
            inputPreco.className = 'form-control';
            inputPreco.step = '0.01';
            inputPreco.min = '0.00'; 
            inputPreco.required = true;
            inputPreco.readOnly = true; 
            inputPreco.oninput = () => calcularTotalLinha(rowCount);
            cellPreco.appendChild(inputPreco);

            // Coluna Quantidade
            const cellQtd = newRow.insertCell();
            cellQtd.setAttribute('data-label', 'Quantidade:');
            const inputQtd = document.createElement('input');
            inputQtd.type = 'number';
            inputQtd.setAttribute('inputmode', 'decimal');
            inputQtd.name = `itens[${rowCount}][quantidade]`;
            inputQtd.id = `quantidade_${rowCount}`;
            inputQtd.className = 'form-control';
            inputQtd.step = '1'; 
            inputQtd.min = '1'; 
            inputQtd.max = '9999'; 
            inputQtd.required = true;
            inputQtd.oninput = () => calcularTotalLinha(rowCount);
            cellQtd.appendChild(inputQtd);

            // Coluna Subtotal
            const cellSubtotal = newRow.insertCell();
            cellSubtotal.setAttribute('data-label', 'Subtotal:');
            cellSubtotal.id = `subtotal_${rowCount}`;
            cellSubtotal.textContent = 'R$ 0,00';
            cellSubtotal.style.fontWeight = 'bold';

            // Coluna Ação (Remover)
            const cellAction = newRow.insertCell();
            cellAction.setAttribute('data-label', 'Ação:');
            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.className = 'btn btn-danger btn-sm';
            btnRemove.innerHTML = '<i class="fas fa-trash"></i> Remover';
            btnRemove.onclick = () => removeProdutoRow(rowCount);
            cellAction.appendChild(btnRemove);
        }

        function removeProdutoRow(index) {
            const row = document.getElementById(`row_${index}`);
            if (row) {
                row.remove(); 
                calcularTotalVenda();
                saveFormData(); 
            }
        }

        function handleClienteSelection(clienteId, tipoClienteId) {
            document.getElementById('cliente_id').value = clienteId;
            document.getElementById('tipo_cliente_id_hidden').value = tipoClienteId || '';

            for (let i = 1; i <= rowCount; i++) {
                const produtoElement = document.getElementById(`produto_${i}`);
                if (produtoElement && document.getElementById(`row_${i}`) && produtoElement.value) {
                    handleProdutoChange(i); 
                }
            }
            saveFormData();
        }


        function handleProdutoChange(rowIndex) {
            const produtoElement = document.getElementById(`produto_${rowIndex}`);
            const precoElement = document.getElementById(`preco_${rowIndex}`);
            const unidadeDisplay = document.getElementById(`unidade_display_${rowIndex}`);
            const quantidadeElement = document.getElementById(`quantidade_${rowIndex}`);
            const tipoClienteId = document.getElementById('tipo_cliente_id_hidden').value;

            if (!produtoElement || !precoElement || !unidadeDisplay || !quantidadeElement) return;

            const selectedOption = produtoElement.options[produtoElement.selectedIndex];
            const produtoId = selectedOption.value;
            const unidadeMedida = selectedOption.getAttribute('data-unidade') || '';

            unidadeDisplay.textContent = unidadeMedida;
            
            if (requiresDecimal(unidadeMedida)) {
                quantidadeElement.step = 'any';
                quantidadeElement.min = '0.01';
            } else {
                quantidadeElement.step = '1';
                quantidadeElement.min = '1';
                if (quantidadeElement.value && parseFloat(quantidadeElement.value) < 1) {
                    quantidadeElement.value = '1';
                } else if (quantidadeElement.value) {
                    quantidadeElement.value = Math.floor(parseFloat(quantidadeElement.value)).toString();
                }
            }
            
            if (!produtoId || !tipoClienteId || tipoClienteId == 0) {
                precoElement.value = '0.00';
                calcularTotalLinha(rowIndex);
                return;
            }

            buscarPrecoProduto(produtoId, tipoClienteId, rowIndex);
        }


        async function buscarPrecoProduto(produtoId, tipoClienteId, rowIndex) {
            const precoElement = document.getElementById(`preco_${rowIndex}`);
            precoElement.value = '...'; 

            try {
                const response = await fetch(`api_precos.php?produto_id=${produtoId}&tipo_cliente_id=${tipoClienteId}`);
                const data = await response.json();

                if (data.preco !== null && data.preco !== undefined) {
                    precoElement.value = parseFloat(data.preco).toFixed(2);
                } else {
                    precoElement.value = '0.00';
                }
                calcularTotalLinha(rowIndex);
            } catch (error) {
                console.error('Erro ao buscar preço:', error);
                precoElement.value = '0.00';
                calcularTotalLinha(rowIndex);
            }
        }

        function calcularTotalLinha(rowIndex) {
            const preco = parseFloat(document.getElementById(`preco_${rowIndex}`).value || 0);
            const quantidadeElement = document.getElementById(`quantidade_${rowIndex}`);
            let quantidade = parseFloat(quantidadeElement.value || 0);
            const subtotalElement = document.getElementById(`subtotal_${rowIndex}`);
            
            const produtoElement = document.getElementById(`produto_${rowIndex}`);
            if (produtoElement) {
                const selectedOption = produtoElement.options[produtoElement.selectedIndex];
                const unidadeMedida = selectedOption.getAttribute('data-unidade') || '';

                if (!requiresDecimal(unidadeMedida) && quantidade > 0) {
                    quantidade = Math.floor(quantidade);
                    quantidadeElement.value = quantidade;
                }
            }
            
            const subtotal = preco * quantidade;
            subtotalElement.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;

            calcularTotalVenda();
        }

        function calcularTotalVenda() {
            let totalGeral = 0; 
            const tableBody = document.querySelector('#tabela_itens tbody');
            const tipoTransacao = document.getElementById('tipo_transacao').value; 
            
            Array.from(tableBody.rows).forEach(row => {
                const rowIndex = parseInt(row.id.replace('row_', ''));
                
                const precoElement = document.getElementById(`preco_${rowIndex}`);
                const quantidadeElement = document.getElementById(`quantidade_${rowIndex}`);

                if (precoElement && quantidadeElement) {
                    const preco = parseFloat(precoElement.value || 0);
                    const quantidade = parseFloat(quantidadeElement.value || 0);
                    totalGeral += preco * quantidade;
                }
            });
            
            document.getElementById('valor_total').value = totalGeral.toFixed(2); 

            let valorFinanceiroFinal = totalGeral;
            
            if (tipoTransacao === 'Bonificação' || tipoTransacao === 'Troca') {
                valorFinanceiroFinal = 0.00;
            } 
            
            document.getElementById('valor_total_display').textContent = `R$ ${valorFinanceiroFinal.toFixed(2).replace('.', ',')}`;

            saveFormData();
        }

        function saveFormData() {
            const tipo_cliente_id_hidden_value = document.getElementById('tipo_cliente_id_hidden').value;
            
            const formData = {
                search_cliente_text: document.getElementById('search_cliente').value, 
                cliente_id: document.getElementById('cliente_id').value,
                tipo_cliente_id: tipo_cliente_id_hidden_value, 
                forma_pagamento: document.getElementById('forma_pagamento').value,
                data_entrega: document.getElementById('data_entrega').value,
                tipo_transacao: document.getElementById('tipo_transacao').value,
                itens: []
            };

            const tableBody = document.querySelector('#tabela_itens tbody');
            Array.from(tableBody.rows).forEach(row => {
                const rowIndex = parseInt(row.id.replace('row_', ''));
                
                const produtoElement = document.getElementById(`produto_${rowIndex}`);
                const quantidadeElement = document.getElementById(`quantidade_${rowIndex}`);
                const precoElement = document.getElementById(`preco_${rowIndex}`);
                
                if (produtoElement && produtoElement.value) {
                    formData.itens.push({
                        produto_id: produtoElement.value,
                        quantidade: quantidadeElement ? quantidadeElement.value : '',
                        preco: precoElement ? precoElement.value : '',
                    });
                }
            });
            localStorage.setItem('vendaFormData', JSON.stringify(formData));
        }

        function loadFormData() {
            const savedData = localStorage.getItem('vendaFormData');
            
            if (!savedData) {
                addProdutoRow(); 
                return;
            }

            const data = JSON.parse(savedData);
            
            document.getElementById('search_cliente').value = data.search_cliente_text || '';
            document.getElementById('cliente_id').value = data.cliente_id || '';
            document.getElementById('forma_pagamento').value = data.forma_pagamento;
            document.getElementById('data_entrega').value = data.data_entrega;
            document.getElementById('tipo_cliente_id_hidden').value = data.tipo_cliente_id || ''; 
            if (data.tipo_transacao) {
                document.getElementById('tipo_transacao').value = data.tipo_transacao;
            }

            if (data.itens && data.itens.length > 0) {
                data.itens.forEach(item => {
                    addProdutoRow(); 
                    
                    const produtoElement = document.getElementById(`produto_${rowCount}`);
                    const quantidadeElement = document.getElementById(`quantidade_${rowCount}`);
                    const precoElement = document.getElementById(`preco_${rowCount}`);
                    
                    if (produtoElement) produtoElement.value = item.produto_id;
                    if (quantidadeElement) quantidadeElement.value = item.quantidade;
                    if (precoElement) precoElement.value = item.preco;
                    
                    handleProdutoChange(rowCount);
                });
            } else {
                addProdutoRow();
            }

            calcularTotalVenda();
        }

        // --- Início do Script Principal (Document Ready) ---
        $(function() {
            // CORREÇÃO E DEBUG NO Autocomplete Cliente
            $("#search_cliente").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "api_clientes.php",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            if (data && data.length) {
                                response($.map(data, function(item) {
                                    return {
                                        label: item.nome + " (" + item.cnpj_cpf + ")",
                                        value: item.nome,
                                        id: item.id,
                                        tipo_cliente_id: item.tipo_cliente_id
                                    };
                                }));
                            } else {
                                // Exibe feedback se a busca retornar vazio
                                response([{ label: "Nenhum cliente encontrado.", value: "" }]);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            // LOG DE ERRO CRÍTICO PARA DEPURAR FALHAS DA API
                            console.error("Erro na busca de clientes (AJAX):", textStatus, errorThrown);
                            response([{ label: "Erro ao buscar dados.", value: "" }]);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    if (ui.item.id) {
                        handleClienteSelection(ui.item.id, ui.item.tipo_cliente_id);
                    }
                    return true;
                },
                change: function(event, ui) {
                    if (!ui.item || !ui.item.id) {
                        document.getElementById('cliente_id').value = '';
                        document.getElementById('tipo_cliente_id_hidden').value = '';
                        handleClienteSelection(0, 0); 
                    }
                }
            });
            
            loadFormData();
            
            $('#forma_pagamento, #data_entrega, #tipo_transacao').on('change', saveFormData);

            // Exibe feedback de sucesso, se houver
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const msg = urlParams.get('msg');
            
            if (status === 'success' && msg) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: decodeURIComponent(msg),
                    confirmButtonText: 'OK'
                });
            }
        });
        
        // --- CORREÇÃO DO MENU HAMBÚRGUER (Toggle Sidebar) ---
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    document.body.classList.toggle('menu-open');
                });
            }
        });
    </script>
</body>
</html>