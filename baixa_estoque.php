<?php
// baixa_estoque.php - Tela de Saída/Baixa de Estoque (FIFO/FEFO)
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E USUÁRIO "INDÚSTRIA"
if ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Industria') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$produtos_lista = [];
$feedback = '';

// Lógica de Feedback após processamento (do processa_baixa_estoque.php)
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $feedback = '<p class="feedback-message status-success"><i class="fas fa-check-circle"></i> Baixa de estoque registrada com sucesso!</p>';
    } elseif ($_GET['status'] === 'error') {
        $feedback = '<p class="feedback-message status-error"><i class="fas fa-times-circle"></i> Erro ao registrar baixa: ' . htmlspecialchars($_GET['msg'] ?? 'Erro desconhecido') . '</p>';
    }
}


// 1. Buscar todos os Produtos que têm saldo disponível na lotes_estoque
// 💡 CORREÇÃO CRÍTICA: O estoque atual do produto é a SOMA dos saldos dos lotes.
$sql_produtos = "
    SELECT 
        p.id, 
        p.nome, 
        p.sku, 
        p.unidade_medida, 
        SUM(le.saldo_atual) as estoque_disponivel
    FROM 
        produtos p
    INNER JOIN 
        lotes_estoque le ON p.id = le.produto_id
    WHERE 
        le.saldo_atual > 0 
        AND le.status_lote IN ('Liberado Todos', 'Liberado VDI') -- Considera apenas lotes liberados
    GROUP BY 
        p.id
    HAVING 
        estoque_disponivel > 0
    ORDER BY 
        p.nome ASC
";

try {
    $result_produtos = $conn->query($sql_produtos);
    if ($result_produtos) {
        while ($row = $result_produtos->fetch_assoc()) {
            $produtos_lista[] = $row;
        }
    }
} catch (Exception $e) {
     error_log("Erro ao buscar produtos com estoque: " . $e->getMessage());
     $feedback = '<p class="feedback-message status-error">Erro ao carregar lista de produtos. Verifique o log de erro.</p>';
}

$conn->close();

function formatar_data($data) {
    return $data ? date('d/m/Y', strtotime($data)) : 'N/A';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baixa de Estoque | FIFO/FEFO</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        /* [Seus estilos CSS] */
        .card-baixa {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
        }
        .form-group label {
            font-weight: 600;
        }
        #lotes-disponiveis-container {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
        }
        #lotes-disponiveis-container h4 {
            border-bottom: 2px solid var(--primary-color-light);
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        .lote-item {
            padding: 10px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-left: 5px solid var(--primary-color);
            border-radius: var(--border-radius);
            background-color: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s, border-color 0.3s;
        }
        /* Lote Selecionado pelo cálculo FEFO/FIFO */
        .lote-item.selected {
            background-color: #e6ffed; /* Fundo verde claro */
            border-left-color: var(--success-color);
            font-weight: bold;
        }
        .lote-info span {
            display: inline-block;
            margin-right: 20px;
            font-size: 0.9em;
        }
        .lote-info strong {
            color: var(--primary-color-dark);
        }
        .priority-badge {
            background-color: var(--danger-color); /* Vencimento mais próximo */
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.75em;
        }
        .info-block {
            padding: 10px;
            background-color: #e9ecef;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 0.95em;
        }
        .info-block strong {
            color: var(--primary-color-dark);
        }
    </style>
</head>
<body>
    <?php include 'top-header.php'; ?>

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-sign-out-alt"></i> Baixa / Saída de Estoque</h1>
                <p>Selecione o produto e a quantidade. O sistema aplicará a regra FEFO/FIFO (Vencimento mais próximo primeiro) para determinar os lotes consumidos.</p>
                
                <?= $feedback ?> <div class="card-baixa">
                    <form id="form-baixa-estoque" method="POST" action="processa_baixa_estoque.php">
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="produto_id">Produto para Baixa:</label>
                                <select name="produto_id" id="produto_id" class="form-control" required>
                                    <option value="">-- Selecione o Produto --</option>
                                    <?php foreach ($produtos_lista as $p): ?>
                                        <option 
                                            value="<?= $p['id'] ?>" 
                                            data-estoque="<?= number_format($p['estoque_disponivel'], 0, ',', '.') ?>" 
                                            data-um="<?= htmlspecialchars($p['unidade_medida']) ?>"
                                        >
                                            <?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['sku']) ?>) - Est. Disp.: <?= number_format($p['estoque_disponivel'], 0, ',', '.') ?> <?= htmlspecialchars($p['unidade_medida']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="quantidade_baixa">Quantidade de Saída:</label>
                                <input type="number" step="0.01" name="quantidade_baixa" id="quantidade_baixa" class="form-control" required min="0.01" disabled>
                                <small class="form-text text-muted" id="unidade_medida_baixa"></small>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label for="data_saida">Data da Saída:</label>
                                <input type="date" name="data_saida" id="data_saida" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div id="info-estoque" class="info-block" style="display:none;">
                            <strong>Estoque Total Disponível:</strong> <span id="estoque-total">0.00</span> | 
                            <strong>Unidade:</strong> <span id="unidade-baixa"></span>
                        </div>

                        <div id="lotes-disponiveis-container">
                            <h4><i class="fas fa-truck-loading"></i> Lotes Disponíveis para Baixa</h4>
                            <div id="lotes-list">
                                <p class="text-muted">Selecione um produto acima para ver os lotes disponíveis para baixa.</p>
                            </div>
                            <input type="hidden" name="lotes_para_baixa_json" id="lotes_para_baixa_json">
                        </div>

                        <div class="form-group">
                            <label for="observacao_baixa">Observação (Motivo da Baixa):</label>
                            <textarea name="observacao_baixa" id="observacao_baixa" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg mt-3" id="btn-submit-baixa" disabled><i class="fas fa-check"></i> Registrar Baixa de Estoque</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let lotesDisponiveis = []; // Armazena os lotes ordenados
        
        // Helper para formatar data (similar ao PHP)
        function formatarData(dataString) {
            if (!dataString) return 'N/A';
            const partes = dataString.split('-');
            // Verifica se a string é yyyy-mm-dd
            if (partes.length === 3) {
                return `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
            return dataString;
        }

        // -----------------------------------------------------------
        // 1. Carregamento e Renderização dos Lotes
        // -----------------------------------------------------------
        $('#produto_id').on('change', function() {
            const produtoId = $(this).val();
            const $estoqueTotalSpan = $('#estoque-total');
            const $quantidadeBaixaInput = $('#quantidade_baixa');
            const $unidadeMedidaBaixa = $('#unidade_medida_baixa');
            const $infoEstoqueBlock = $('#info-estoque');
            
            // Limpa e reseta o estado
            lotesDisponiveis = [];
            $('#lotes-list').html('<p><i class="fas fa-spinner fa-spin"></i> Carregando lotes...</p>');
            $('#btn-submit-baixa').prop('disabled', true);
            $quantidadeBaixaInput.prop('disabled', true).val('');
            
            if (produtoId) {
                const selectedOption = $(this).find('option:selected');
                const estoqueTotal = selectedOption.data('estoque');
                const unidade = selectedOption.data('um');
                // Converte a string formatada para número para usar no JS
                const estoqueNumerico = parseFloat(estoqueTotal.toString().replace('.', '').replace(',', '.'));
                
                // Atualiza o bloco de informações e libera o campo de quantidade
                $('#unidade-baixa').text(unidade);
                $estoqueTotalSpan.text(estoqueTotal);
                $unidadeMedidaBaixa.text(`Máximo: ${estoqueTotal} ${unidade}`);
                $infoEstoqueBlock.show();
                
                $quantidadeBaixaInput
                    .prop('disabled', false)
                    .attr('max', estoqueNumerico)
                    .focus();

                // Chamada AJAX para buscar lotes (que serão FILTRADOS e ORDENADOS)
                // O arquivo get_lotes_disponiveis.php será criado no próximo passo
                $.ajax({
                    url: 'get_lotes_disponiveis.php', 
                    type: 'GET',
                    data: { produto_id: produtoId },
                    dataType: 'json',
                    success: function(response) {
                        lotesDisponiveis = response; // Lotes já vêm ordenados por FEFO/FIFO do backend
                        renderLotesList();
                        // Recalcula a baixa com a quantidade já digitada (se houver)
                        $quantidadeBaixaInput.trigger('input');
                    },
                    error: function(xhr, status, error) {
                         // Melhor debug: exibe o erro completo no console
                         console.error("Erro ao carregar lotes:", status, error); 
                         console.log("Resposta do servidor:", xhr.responseText);
                         $('#lotes-list').html('<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar os lotes. Consulte o console para detalhes.</p>');
                    }
                });
            } else {
                $('#lotes-list').html('<p class="text-muted">Selecione um produto acima para ver os lotes disponíveis para baixa.</p>');
                $infoEstoqueBlock.hide();
            }
        });
        
        function renderLotesList() {
            const $lotesList = $('#lotes-list');
            $lotesList.empty();

            if (lotesDisponiveis.length === 0) {
                $lotesList.html('<p class="text-warning"><i class="fas fa-exclamation-circle"></i> Nenhum lote Liberado/VDI disponível para baixa (ou todo o saldo foi consumido).</p>');
                return;
            }

            lotesDisponiveis.forEach((lote, index) => {
                const vencimentoFormatado = formatarData(lote.data_vencimento);
                const entradaFormatada = formatarData(lote.data_entrada);
                const saldoFormatado = parseFloat(lote.saldo_atual).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                const isPrioritario = index === 0;
                
                // Usar a cor de status_lote para a borda
                let statusClass = 'primary-color';
                if (lote.status_lote === 'Bloqueado') {
                    statusClass = 'danger-color';
                } else if (lote.status_lote === 'Liberado VDI') {
                    statusClass = 'warning-color'; // ou outra cor para VDI
                } else {
                    statusClass = 'success-color';
                }

                const badge = isPrioritario ? '<span class="priority-badge">PRIORITÁRIO (FEFO/FIFO)</span>' : '';
                
                $lotesList.append(`
                    <div class="lote-item" data-lote-id="${lote.id}" data-saldo="${lote.saldo_atual}" style="border-left-color: var(--${statusClass});">
                        <div class="lote-info">
                            <span><strong>Lote:</strong> ${lote.num_lote}</span>
                            <span><strong>Vencimento:</strong> ${vencimentoFormatado}</span>
                            <span><strong>Entrada:</strong> ${entradaFormatada}</span>
                            <span><strong>Saldo:</strong> ${saldoFormatado}</span>
                            <span><strong>Status:</strong> ${lote.status_lote}</span>
                        </div>
                        ${badge}
                    </div>
                `);
            });
        }

        // -----------------------------------------------------------
        // 2. Lógica de Cálculo de Baixa (FEFO/FIFO)
        // -----------------------------------------------------------
        $('#quantidade_baixa').on('input', function() {
            const $quantidadeInput = $(this);
            let quantidadeDesejada = parseFloat($quantidadeInput.val().replace(',', '.'));
            const estoqueTotalStr = $('#estoque-total').text().replace('.', '').replace(',', '.');
            const estoqueTotal = parseFloat(estoqueTotalStr);
            const $btnSubmit = $('#btn-submit-baixa');
            
            // Reseta a visualização e o JSON
            $btnSubmit.prop('disabled', true);
            $('#lotes_para_baixa_json').val('');
            $('.lote-item').removeClass('selected');
            
            if (isNaN(quantidadeDesejada) || quantidadeDesejada <= 0) {
                return;
            }
            
            // 1. Validação (Aviso já no JS)
            if (quantidadeDesejada > estoqueTotal) {
                 // Esta validação é repetitiva, o atributo 'max' já cuida disso visualmente.
                 // Vamos apenas garantir que o valor não ultrapasse o max.
                 quantidadeDesejada = estoqueTotal;
                 $quantidadeInput.val(estoqueTotal);
            }

            let quantidadeRestante = quantidadeDesejada;
            let lotesParaBaixa = [];
            
            // 2. Aplicação da Regra FEFO/FIFO nos Lotes Ordenados
            lotesDisponiveis.forEach(lote => {
                if (quantidadeRestante > 0 && parseFloat(lote.saldo_atual) > 0) {
                    const $loteItem = $(`[data-lote-id="${lote.id}"]`);
                    
                    let saldoLote = parseFloat(lote.saldo_atual);
                    let quantidadeDesteLote = 0;
                    
                    if (quantidadeRestante >= saldoLote) {
                        // Consome o lote inteiro
                        quantidadeDesteLote = saldoLote;
                        quantidadeRestante -= saldoLote;
                    } else {
                        // Consome parte do lote e zera a necessidade
                        quantidadeDesteLote = quantidadeRestante;
                        quantidadeRestante = 0;
                    }

                    if (quantidadeDesteLote > 0) {
                        $loteItem.addClass('selected'); // Destaca o lote consumido
                        
                        lotesParaBaixa.push({
                            lote_id: lote.id,
                            num_lote: lote.num_lote,
                            // Garante 0 casas decimais no JSON
                            quantidade_baixa: parseFloat(quantidadeDesteLote.toFixed(0)) 
                        });
                    }
                }
            });
            
            // 3. Conclusão da Validação e Liberação do Botão
            // Verifica se a quantidade total necessária foi atendida.
            if (Math.abs(quantidadeRestante) < 0.001) { // Usamos tolerância para float
                // JSON final a ser enviado ao backend
                $('#lotes_para_baixa_json').val(JSON.stringify(lotesParaBaixa));
                $btnSubmit.prop('disabled', false);
            } else {
                // Caso a quantidade restante não seja 0 (pode ocorrer por erro de cálculo/dados)
                $btnSubmit.prop('disabled', true);
                // Você pode adicionar um feedback visual aqui, se desejar
            }
        });
        
        // Listener do menu
        document.getElementById('menu-toggle')?.addEventListener('click', function() {
            document.body.classList.toggle('menu-open');
        });

        // Tenta carregar os lotes se um produto já estiver selecionado no load da página
        $(document).ready(function() {
            if ($('#produto_id').val()) {
                $('#produto_id').trigger('change');
            }
        });
    </script>
</body>
</html>