<?php 
// sidebar.php - Menu de navegação lateral organizado EXCLUSIVAMENTE por tipo de usuário
// Requer que a variável $usuario_logado esteja definida e contenha o campo 'privilegio'
?>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?php echo url('/public/assets/images/logo.png'); ?>" alt="Nata do Campo">
    </div>

    <nav class="sidebar-nav">
        <!-- Item principal do Dashboard -->
        <div class="sidebar-nav-item">
            <a href="<?php echo url('/public/dashboard/painel_pedidos.php'); ?>" 
               class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'painel_pedidos.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Cabeçalho da área do usuário -->
        <div class="sidebar-section">
            <h6 class="sidebar-section-title">Área de <?= htmlspecialchars($usuario_logado['privilegio']) ?></h6>
        </div>

        <?php if ($usuario_logado['privilegio'] === 'Admin'): ?>
            <!-- Seção de Vendas -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Vendas</h6>
                
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Venda/lancamento_venda.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'lancamento_venda.php' ? 'active' : '' ?>">
                        <i class="fas fa-cash-register"></i>
                        <span>Lançar Venda</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Cliente/historico_cliente.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'historico_cliente.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-tag"></i>
                        <span>Histórico Cliente</span>
                    </a>
                </div>
            </div>

            <!-- Seção de Estoque -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Estoque e Rastreabilidade</h6>
                
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/cadastro_estoque.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'cadastro_estoque.php' ? 'active' : '' ?>">
                        <i class="fas fa-truck-loading"></i>
                        <span>Lançar Entrada</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/baixa_estoque.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'baixa_estoque.php' ? 'active' : '' ?>">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Registrar Saída/Baixa</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/consulta_lancamentos_estoque.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'consulta_lancamentos_estoque.php' ? 'active' : '' ?>">
                        <i class="fas fa-search"></i>
                        <span>Consulta Lotes (FIFO)</span>
                    </a>
                </div>
            </div>

            <!-- Seção de Relatórios -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Análise e Relatórios</h6>
                
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Venda/relatorio_vendas.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorio_vendas.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Vendas por Período</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/relatorio_estoque_baixo.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorio_estoque_baixo.php' ? 'active' : '' ?>">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Estoque Baixo</span>
                    </a>
                </div>
            </div>

            <!-- Seção de Administração -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Administração</h6>
                
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Usuario/cadastro_usuario.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'cadastro_usuario.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Cadastrar Usuário</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Cliente/cadastro_cliente.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'cadastro_cliente.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Cadastrar Cliente</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Produto/cadastro_produto.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'cadastro_produto.php' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i>
                        <span>Cadastrar Produto</span>
                    </a>
                </div>
            </div>

        <?php 
        elseif ($usuario_logado['privilegio'] === 'Gestor'): ?>
            <!-- Seção de Gestão -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Gestão</h6>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Cliente/historico_cliente.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'historico_cliente.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-tag"></i>
                        <span>Histórico Cliente</span>
                    </a>
                </div>
            </div>

            <!-- Seção de Relatórios -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Relatórios</h6>
                
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Venda/relatorio_vendas.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorio_vendas.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Vendas por Período</span>
                    </a>
                </div>
            </div>

        <?php 
        elseif ($usuario_logado['privilegio'] === 'Vendedor'): ?>
            <!-- Seção de Vendas -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Vendas</h6>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Cliente/cadastro_cliente.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'cadastro_cliente.php' ? 'active' : '' ?>">
                        <i class="fas fa-address-card"></i>
                        <span>Cadastrar Cliente</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Venda/lancamento_venda.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'lancamento_venda.php' ? 'active' : '' ?>">
                        <i class="fas fa-cash-register"></i>
                        <span>Lançar Venda</span>
                    </a>
                </div>
            </div>

        <?php 
        elseif ($usuario_logado['privilegio'] === 'Industria'): ?>
            <!-- Seção de Estoque -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Estoque e Movimentação</h6>
                
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/cadastro_estoque.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'cadastro_estoque.php' ? 'active' : '' ?>">
                        <i class="fas fa-truck-loading"></i>
                        <span>Lançar Entrada</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/baixa_estoque.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'baixa_estoque.php' ? 'active' : '' ?>">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Registrar Saída/Baixa</span>
                    </a>
                </div>
            </div>

            <!-- Seção de Rastreabilidade -->
            <div class="sidebar-section">
                <h6 class="sidebar-section-title">Rastreabilidade e Monitoramento</h6>
                
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/consulta_lancamentos_estoque.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'consulta_lancamentos_estoque.php' ? 'active' : '' ?>">
                        <i class="fas fa-search"></i>
                        <span>Consulta Lotes (FIFO)</span>
                    </a>
                </div>

                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/Estoque/relatorio_estoque_baixo.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorio_estoque_baixo.php' ? 'active' : '' ?>">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Estoque Baixo</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Seção do Clube Nata (disponível para todos) -->
        <div class="sidebar-section">
            <h6 class="sidebar-section-title">Clube Nata</h6>
            
            <div class="sidebar-nav-item">
                <a href="<?php echo url('/src/ClubeNata/clube_nata.php'); ?>" 
                   class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'clube_nata.php' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i>
                    <span>Clube de Pontos</span>
                </a>
            </div>

            <?php if (in_array($usuario_logado['privilegio'], ['Admin', 'Gestor'])): ?>
                <div class="sidebar-nav-item">
                    <a href="<?php echo url('/src/ClubeNata/relatorio_resgates.php'); ?>" 
                       class="sidebar-nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorio_resgates.php' ? 'active' : '' ?>">
                        <i class="fas fa-gift"></i>
                        <span>Relatório de Resgates</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="sidebar-footer">
        <p>© <?php echo date('Y'); ?> Nata do Campo</p>
        <p class="text-muted">Versão 2.0.0</p>
    </div>
</aside>