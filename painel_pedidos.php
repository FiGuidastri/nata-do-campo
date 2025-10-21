<?php
// painel_pedidos.php - Histórico com foco em Cliente, Logística e Financeiro.
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; // Inclui a conexão para o PHP se necessário, mas o principal é o JS/API.

// Assumindo que verifica_sessao.php define $usuario_logado
$usuario_privilegio = isset($usuario_logado['privilegio']) ? $usuario_logado['privilegio'] : 'Visitante';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Pedidos | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        /* Estilos do novo painel de layout e Clube Nata */
        .painel-principal-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* 2/3 para pedidos, 1/3 para clube */
            gap: 20px;
            margin-top: 20px;
        }
        
        /* Ajuste do Título H2 do painel para alinhar com o h1 */
        .painel-principal-grid h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: var(--spacing-sm);
        }
        
        .painel-pontos {
            background-color: var(--card-bg); 
            padding: 15px;
            border-radius: 8px;
            box-shadow: var(--shadow); 
        }
        .club-points {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .data-table.small th, .data-table.small td {
            padding: 8px 10px;
            font-size: 0.9rem;
        }

        /* Destacar linha clicável */
        .data-table tbody tr {
            transition: background-color 0.2s;
        }
        .data-table tbody tr:hover {
            background-color: var(--hover-color);
        }
        
        /* Ajustes para o badge de status */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .status-pendente { background-color: var(--warning-color); }
        .status-liberado { background-color: var(--secondary-color); }
        /* CORREÇÃO AQUI: CSS para o status 'Entrega' */
        .status-entrega { background-color: var(--info-color); } 
        .status-faturado { background-color: var(--success-color); }
        .status-rejeitado { background-color: var(--danger-color); }
        .status-cancelado { background-color: #555; }


        /* MEDIA QUERY PARA REFINAR A RESPONSIVIDADE DO PAINEL */
        @media (max-width: 900px) {
            .painel-principal-grid {
                grid-template-columns: 1fr; 
            }
            .data-table .hide-on-mobile {
                display: none;
            }
        }
    </style>
</head>
<body class="<?= $usuario_privilegio === 'Admin' ? 'menu-open' : ''; ?>">
    <?php include 'top-header.php'; ?> 

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-clipboard-list"></i> Painel de Pedidos e Histórico</h1>
                <p>Gerenciamento dos últimos 100 pedidos lançados.</p>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="searchInput">Busca Rápida:</label>
                        <input type="text" class="form-control" id="searchInput" onkeyup="filtrarTabelaPedidos()" placeholder="Buscar por Cliente, CNPJ ou Status...">
                    </div>
                </div>

                <div class="painel-principal-grid">
                    <div>
                        <h2>Histórico de Pedidos</h2>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Pedido #</th>
                                        <th>Cliente (Cód.)</th>
                                        <th class="hide-on-mobile">Tipo Cliente</th>
                                        <th>Entrega</th>
                                        <th style="width: 120px;">Total</th>
                                        <th>Status</th>
                                        <th style="width: 150px;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_pedidos">
                                    <tr><td colspan="7" style="text-align: center;">Carregando pedidos...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="painel-pontos">
                        <h2><i class="fas fa-trophy"></i> Pontuação Clube</h2>
                        <div class="table-responsive">
                            <table class="data-table small">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Pontos</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_club_nata">
                                    <tr><td colspan="2" style="text-align: center;">Carregando dados do clube...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($usuario_privilegio === 'Gestor' || $usuario_privilegio === 'Admin'): ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="clube_nata.php" class="btn btn-secondary btn-sm"><i class="fas fa-cog"></i> Gerenciar Clube</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const USER_PRIVILEGE = "<?php echo $usuario_privilegio; ?>";

        function formatCurrency(value) {
            // Formata o valor como BRL (R$)
            return `R$ ${parseFloat(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
        }
        
        async function carregarPontosClube() {
            try {
                // Supondo que api_clube_nata_vendedor.php exista para este painel
                const response = await fetch('api_clube_nata_vendedor.php'); 
                const data = await response.json();
                const tbody = document.getElementById('tabela_club_nata');
                tbody.innerHTML = ''; 

                if (data.success && data.membros.length > 0) {
                    data.membros.forEach(membro => {
                        const row = tbody.insertRow();
                        row.insertCell().textContent = membro.nome_cliente;
                        row.insertCell().innerHTML = `<span class="club-points">${parseFloat(membro.pontuacao).toFixed(2).replace('.', ',')}</span>`;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="2" style="text-align: center;">Nenhum membro do clube encontrado.</td></tr>';
                }
            } catch (error) {
                console.error('Erro ao carregar pontos do clube:', error);
                document.getElementById('tabela_club_nata').innerHTML = '<tr><td colspan="2" style="text-align: center; color: var(--danger-color);">Erro ao carregar clube.</td></tr>';
            }
        }

        async function validarPedido(vendaId, novoStatus) {
            if (!confirm(`Confirmar mudança de status do Pedido #${vendaId} para ${novoStatus}?`)) {
                return;
            }

            try {
                const response = await fetch('api_valida_pedido.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `venda_id=${vendaId}&status=${novoStatus}`
                });
                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    carregarPedidos(); 
                    if (novoStatus === 'Faturado') { 
                       carregarPontosClube();
                    }
                } else {
                    alert('Falha ao validar: ' + data.message + (data.error_details ? ' (' + data.error_details + ')' : ''));
                }
            } catch (error) {
                console.error('Erro ao validar pedido:', error);
                alert('Erro de comunicação com o servidor ao validar pedido.');
            }
        }

        function filtrarTabelaPedidos() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('tabela_pedidos');
            const tr = table.getElementsByTagName('tr');
            let found = false;

            for (let i = 0; i < tr.length; i++) {
                if (tr[i].classList.contains('no-results')) continue;

                // Colunas 1 (Cliente/CNPJ), 2 (Tipo), 5 (Status)
                const tdCliente = tr[i].getElementsByTagName("td")[1]; 
                const tdTipo = tr[i].getElementsByTagName("td")[2]; 
                const tdStatus = tr[i].getElementsByTagName("td")[5]; 
                
                if (tdCliente || tdTipo || tdStatus) {
                    const textValue = (tdCliente ? tdCliente.textContent : '') + 
                                            (tdTipo ? tdTipo.textContent : '') + 
                                            (tdStatus ? tdStatus.textContent : '');
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        found = true;
                    } else {
                        tr[i].style.display = "none";
                    }
                }      
            }
            // Adiciona ou remove a linha "Nenhum resultado" (Se necessário)
            const noResultsRow = document.querySelector('.no-results');
            if (!found && tr.length > 0) {
                 if(!noResultsRow) {
                    const row = table.insertRow();
                    row.classList.add('no-results');
                    row.innerHTML = '<td colspan="7" style="text-align: center;">Nenhum pedido encontrado com o filtro.</td>';
                 }
            } else if(noResultsRow) {
                noResultsRow.remove();
            }
        }
        
        async function carregarPedidos() {
            try {
                // Chama a API de Pedidos
                const response = await fetch('api_pedidos.php'); 
                const data = await response.json();
                const tbody = document.getElementById('tabela_pedidos');
                tbody.innerHTML = ''; 

                if (data.error) {
                     // Exibe o erro retornado pelo PHP
                     tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--danger-color);">Erro da API: ${data.error}</td></tr>`;
                     console.error('Erro da API de Pedidos:', data.error);
                     return;
                }

                if (data.pedidos && data.pedidos.length > 0) {
                    data.pedidos.forEach(pedido => {
                        const row = tbody.insertRow();
                        
                        // Torna a linha clicável para abrir o detalhe
                        row.onclick = (e) => {
                             // Evita que o clique no SELECT ou botão de ação dispare o detalhe
                             if(e.target.tagName !== 'SELECT' && e.target.tagName !== 'OPTION' && e.target.tagName !== 'A' && e.target.closest('a') === null) {
                                 window.location.href = `detalhe_venda.php?venda_id=${pedido.venda_id}`;
                             }
                        };
                        row.style.cursor = 'pointer'; 

                        row.insertCell().textContent = pedido.venda_id; 
                        row.insertCell().textContent = `${pedido.nome_cliente} (Cód: ${pedido.codigo_cliente})`;
                        
                        const tipoClienteCell = row.insertCell();
                        tipoClienteCell.textContent = pedido.tipo_cliente_nome || 'N/A';
                        tipoClienteCell.classList.add('hide-on-mobile'); 
                        
                        row.insertCell().textContent = pedido.data_entrega_formatada;
                        row.insertCell().textContent = formatCurrency(pedido.valor_total);
                        
                        // Formata o status para CSS (ex: "Em Entrega" -> "status-em-entrega")
                        // NOTA: O status é exibido como está no banco (ex: "Entrega")
                        const statusClass = pedido.status.toLowerCase().replace(' ', '-');
                        row.insertCell().innerHTML = `<span class="status-badge status-${statusClass}">${pedido.status}</span>`; 

                        // Coluna Ação
                        const cellAcao = row.insertCell();
                        
                        // Lógica de Ações para Gestor/Admin
                        if (USER_PRIVILEGE === 'Gestor' || USER_PRIVILEGE === 'Admin') {
                            if (pedido.status === 'Pendente') {
                                cellAcao.innerHTML = `
                                    <select class="form-control" onchange="validarPedido(${pedido.venda_id}, this.value)">
                                        <option value="">Ações...</option>
                                        <option value="Liberado">Liberar</option>
                                        <option value="Rejeitado">Rejeitar</option>
                                    </select>
                                `;
                            } else if (pedido.status === 'Liberado') {
                                cellAcao.innerHTML = `
                                    <select class="form-control" onchange="validarPedido(${pedido.venda_id}, this.value)">
                                        <option value="">Ações...</option>
                                        <option value="Entrega">Entrega</option> 
                                        <option value="Faturado">Faturar</option>
                                    </select>
                                `;
                            } else {
                                // Para outros status
                                cellAcao.innerHTML = `<a href="detalhe_venda.php?venda_id=${pedido.venda_id}" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Ver</a>`;
                            }
                        } else {
                            // Usuários sem permissão de gestão
                            cellAcao.innerHTML = `<a href="detalhe_venda.php?venda_id=${pedido.venda_id}" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Ver</a>`;
                        }
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Nenhum pedido encontrado.</td></tr>';
                }
                
                filtrarTabelaPedidos(); 

            } catch (error) {
                console.error('Erro de rede/JSON ao carregar pedidos:', error);
                document.getElementById('tabela_pedidos').innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--danger-color);">Erro ao conectar com a API de pedidos. Verifique o console.</td></tr>';
            }
        }

        // Carrega os painéis ao iniciar a página
        document.addEventListener('DOMContentLoaded', () => {
            carregarPedidos();
            carregarPontosClube();
        });
        
        // Controle da Sidebar
        document.querySelector('.menu-toggle')?.addEventListener('click', function() {
            document.body.classList.toggle('menu-open');
        });
    </script>
</body>
</html>