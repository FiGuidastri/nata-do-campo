<?php
// relatorio_vendas.php - Interface de filtro, visualização e cards de resumo.
require_once 'verifica_sessao.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Vendas | Nata do Campo</title>
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
                <h1><i class="fas fa-chart-bar"></i> Relatório Geral de Vendas por Produto</h1>
                <p>Use os filtros abaixo para analisar o desempenho de vendas.</p>
                
                <fieldset style="margin-bottom: var(--spacing-lg);">
                    <legend>Filtros de Relatório</legend>
                    <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        
                        <div class="form-group">
                            <label for="filter_ano">Ano:</label>
                            <select id="filter_ano" class="form-control">
                                <option value="">Todos</option>
                                <?php 
                                    // Gera opções de ano dinamicamente (ajuste conforme necessário)
                                    $currentYear = date('Y');
                                    for ($y = $currentYear; $y >= 2025; $y--) {
                                        $selected = ($y == $currentYear) ? 'selected' : '';
                                        echo "<option value=\"$y\" $selected>$y</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_mes">Mês:</label>
                            <select id="filter_mes" class="form-control">
                                <option value="">Todos</option>
                                <?php 
                                    $meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
                                    foreach($meses as $num => $nome) {
                                        echo "<option value=\"$num\">$nome</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_produto">Produto (SKU):</label>
                            <input type="text" id="filter_produto" class="form-control" placeholder="Digite parte do SKU">
                        </div>
                        <div class="form-group" style="align-self: flex-end;">
                            <button class="btn btn-primary full-width" onclick="carregarRelatorio()"><i class="fas fa-filter"></i> Aplicar Filtro</button>
                        </div>
                    </div>
                </fieldset>

                <div class="resumo-cards">
                    <div class="card verde">
                        <h3>Valor Total Bruto (R$)</h3>
                        <p id="total_valor">R$ 0,00</p>
                    </div>
                    <div class="card amarelo">
                        <h3>Total de Unidades Vendidas</h3>
                        <p id="total_quantidade">0</p>
                    </div>
                    <div class="card">
                        <h3>Venda Média por Produto (R$)</h3>
                        <p id="valor_medio">R$ 0,00</p>
                    </div>
                </div>

                <h2 style="margin-top: 30px; font-size: 1.5rem;"><i class="fas fa-table"></i> Detalhamento por Produto/Período</h2>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto (SKU)</th>
                                <th>Período</th>
                                <th>Total Vendido (Unidades)</th>
                                <th>Unidade Medida</th>
                                <th>Total Bruto (R$)</th>
                            </tr>
                        </thead>
                        <tbody id="tabela_relatorio">
                            <tr><td colspan="5" style="text-align: center;">Carregando dados...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para formatar números como moeda brasileira
        function formatCurrency(value) {
            return `R$ ${parseFloat(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
        }
        
        async function carregarRelatorio() {
            const ano = document.getElementById('filter_ano').value;
            const mes = document.getElementById('filter_mes').value;
            const produto = document.getElementById('filter_produto').value;

            const params = new URLSearchParams();
            if (ano) params.append('ano', ano);
            if (mes) params.append('mes', mes);
            if (produto) params.append('produto', produto);
            
            const url = `api_relatorio_vendas.php?${params.toString()}`;
            const tbody = document.getElementById('tabela_relatorio');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Carregando dados...</td></tr>';
            
            // Limpa os cards enquanto carrega
            document.getElementById('total_valor').textContent = '...';
            document.getElementById('total_quantidade').textContent = '...';
            document.getElementById('valor_medio').textContent = '...';

            try {
                const response = await fetch(url);
                const data = await response.json();
                tbody.innerHTML = ''; 
                
                // --- 1. ATUALIZA OS CARDS DE TOTAIS GERAIS ---
                const totais = data.totais || { valor_total_geral: 0, total_vendido_geral: 0 };
                const valorTotal = parseFloat(totais.valor_total_geral || 0);
                const qtdTotal = parseFloat(totais.total_vendido_geral || 0);

                document.getElementById('total_valor').textContent = formatCurrency(valorTotal);
                document.getElementById('total_quantidade').textContent = qtdTotal.toLocaleString('pt-BR');
                
                // Cálculo de Valor Médio
                const media = (valorTotal > 0 && data.relatorio && data.relatorio.length > 0) 
                              ? valorTotal / data.relatorio.length 
                              : 0;
                document.getElementById('valor_medio').textContent = formatCurrency(media);


                // --- 2. ATUALIZA A TABELA DE DETALHES ---
                if (data.relatorio && data.relatorio.length > 0) {
                    
                    data.relatorio.forEach(item => {
                        const row = tbody.insertRow();
                        row.insertCell().textContent = `${item.nome_produto} (${item.sku})`;
                        row.insertCell().textContent = item.ano_mes_venda;
                        row.insertCell().textContent = parseFloat(item.total_vendido_unidades).toLocaleString('pt-BR');
                        row.insertCell().textContent = item.unidade_medida;
                        row.insertCell().textContent = formatCurrency(item.total_valor_bruto);
                    });

                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum resultado encontrado com os filtros selecionados.</td></tr>';
                }
            } catch (error) {
                console.error('Erro ao carregar relatório:', error);
                // Reseta os totais em caso de erro
                document.getElementById('total_valor').textContent = formatCurrency(0);
                document.getElementById('total_quantidade').textContent = '0';
                document.getElementById('valor_medio').textContent = formatCurrency(0);
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--danger-color);">Erro ao conectar com a API de relatório.</td></tr>';
            }
        }
        
        document.addEventListener('DOMContentLoaded', carregarRelatorio); 
    </script>
</body>
</html>