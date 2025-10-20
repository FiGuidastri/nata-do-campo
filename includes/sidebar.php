<?php 
// sidebar.php - Menu de navegação lateral organizado EXCLUSIVAMENTE por tipo de usuário
// Requer que a variável $usuario_logado esteja definida e contenha o campo 'privilegio'
?>

<div class="sidebar">
    <nav class="sidebar-nav">
        
        <a href="painel_pedidos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'painel_pedidos.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <div class="nav-separator">Área de <?= htmlspecialchars($usuario_logado['privilegio']) ?></div>

        
        <?php 
        // =========================================================
        // OPÇÕES EXCLUSIVAS PARA ADMINISTRADOR (ADMIN)
        // =========================================================
        if ($usuario_logado['privilegio'] === 'Admin'): ?>

            <div class="nav-separator">Vendas</div>
            <a href="lancamento_venda.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'lancamento_venda.php' ? 'active' : '' ?>">
                <i class="fas fa-cash-register"></i> Lançar Venda
            </a>
            
            <a href="historico_cliente.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'historico_cliente.php' ? 'active' : '' ?>">
                <i class="fas fa-user-tag"></i> Histórico Cliente
            </a>
            
            <div class="nav-separator">Estoque e Rastreabilidade</div>
            
            <a href="cadastro_estoque.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'cadastro_estoque.php' ? 'active' : '' ?>">
                <i class="fas fa-truck-loading"></i> Lançar Entrada
            </a>
            
            <a href="baixa_estoque.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'baixa_estoque.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-out-alt"></i> Registrar Saída/Baixa
            </a>
            
            <a href="consulta_lancamentos_estoque.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'consulta_lancamentos_estoque.php' ? 'active' : '' ?>">
                <i class="fas fa-search"></i> Consulta Lotes (FIFO)
            </a>

            <div class="nav-separator">Análise e Relatórios</div>
            <a href="relatorio_vendas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'relatorio_vendas.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Vendas por Período
            </a>
            <a href="relatorio_estoque_baixo.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'relatorio_estoque_baixo.php' ? 'active' : '' ?>">
                <i class="fas fa-exclamation-triangle"></i> Estoque Baixo
            </a>

            <div class="nav-separator">Administração</div>
            <a href="cadastro_usuario.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'cadastro_usuario.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Cadastrar Usuário
            </a>
            <a href="cadastro_produto.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'cadastro_produto.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-basket"></i> Cadastrar Produto
            </a>
            <a href="cadastro_cliente.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'cadastro_cliente.php' ? 'active' : '' ?>">
                <i class="fas fa-address-card"></i> Cadastrar Cliente
            </a>


        <?php 
        // =========================================================
        // OPÇÕES EXCLUSIVAS PARA GESTOR
        // =========================================================
        elseif ($usuario_logado['privilegio'] === 'Gestor'): ?>

            <a href="historico_cliente.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'historico_cliente.php' ? 'active' : '' ?>">
                <i class="fas fa-user-tag"></i> Histórico Cliente
            </a>

            <div class="nav-separator">Relatórios</div>
            <a href="relatorio_vendas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'relatorio_vendas.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Vendas por Período
            </a>


        <?php 
        // =========================================================
        // OPÇÕES EXCLUSIVAS PARA VENDEDOR
        // =========================================================
        elseif ($usuario_logado['privilegio'] === 'Vendedor'): ?>

            <a href="cadastro_cliente.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'cadastro_cliente.php' ? 'active' : '' ?>">
                <i class="fas fa-address-card"></i> Cadastrar Cliente
            </a>

            <a href="lancamento_venda.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'lancamento_venda.php' ? 'active' : '' ?>">
                <i class="fas fa-cash-register"></i> Lançar Venda
            </a>
            
        
        <?php 
        // =========================================================
        // OPÇÕES EXCLUSIVAS PARA INDÚSTRIA
        // =========================================================
        elseif ($usuario_logado['privilegio'] === 'Industria'): ?>

            <div class="nav-separator">Estoque e Movimentação</div>
            
            <a href="cadastro_estoque.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'cadastro_estoque.php' ? 'active' : '' ?>">
                <i class="fas fa-truck-loading"></i> Lançar Entrada
            </a>

            <a href="baixa_estoque.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'baixa_estoque.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-out-alt"></i> Registrar Saída/Baixa
            </a>
            
            <div class="nav-separator">Rastreabilidade e Monitoramento</div>
            
            <a href="consulta_lancamentos_estoque.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'consulta_lancamentos_estoque.php' ? 'active' : '' ?>">
                <i class="fas fa-search"></i> Consulta Lotes (FIFO)
            </a>
            
            <a href="relatorio_estoque_baixo.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'relatorio_estoque_baixo.php' ? 'active' : '' ?>">
                <i class="fas fa-exclamation-triangle"></i> Estoque Baixo
            </a>

        <?php endif; ?>
        
    </nav>

    <div class="sidebar-footer">
        <p>© 2025 Nata do Campo</p>
        <p style="font-size: 0.75rem;">Desenvolvido com <i class="fas fa-heart" style="color: var(--danger-color);"></i> por Marketing.</p>
    </div>

</div>