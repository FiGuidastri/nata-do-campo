<?php
// painel_pedidos.php - Dashboard principal do sistema
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/verifica_sessao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Pedidos | Nata do Campo</title>
    <link rel="stylesheet" href="<?php echo url('/public/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <?php include __DIR__ . '/../../includes/top-header.php'; ?>

            <!-- Content -->
            <div class="content-wrapper">
                <h1>Painel de Pedidos</h1>
                <div id="pedidosLista">
                    <!-- Os pedidos serão carregados aqui via AJAX -->
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para carregar pedidos
        async function carregarPedidos() {
            try {
                const response = await fetch(BASE_URL + '/api/pedidos.php');
                const data = await response.json();
                const pedidosLista = document.getElementById('pedidosLista');
                
                if (data.error) {
                    pedidosLista.innerHTML = `<p class="error">${data.error}</p>`;
                    return;
                }
                
                // Renderiza os pedidos
                pedidosLista.innerHTML = data.map(pedido => `
                    <div class="pedido-card">
                        <h3>Pedido #${pedido.id}</h3>
                        <p>Cliente: ${pedido.nome_cliente}</p>
                        <p>Data: ${pedido.data_venda}</p>
                        <p>Status: ${pedido.status}</p>
                        <p>Valor: R$ ${pedido.valor_total}</p>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Erro ao carregar pedidos:', error);
            }
        }

        // Carrega os pedidos quando a página é aberta
        carregarPedidos();
    });
    </script>
</body>
</html>