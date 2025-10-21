<?php
// top-header.php - Cabeçalho superior (incluído em todas as páginas)
// Requer que a variável $usuario_logado esteja definida.
?>
<header class="top-header">
    <!-- Menu toggle para mobile -->
    <div class="top-header-left">
        <button class="menu-toggle d-lg-none" aria-label="Toggle navigation menu">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Título da página atual -->
        <h1 class="top-header-title"></h1>
    </div>

    <!-- Ações e informações do usuário -->
    <div class="top-header-actions">
        <!-- Notificações -->
        <div class="notification-menu">
            <button class="notification-toggle" aria-label="View notifications">
                <i class="fas fa-bell"></i>
                <?php 
                // TODO: Implementar contagem de notificações
                $notificacoes = 0;
                if ($notificacoes > 0): ?>
                    <span class="notification-badge"><?= $notificacoes ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Menu do usuário -->
        <div class="user-menu">
            <button class="user-menu-button" aria-label="User menu">
                <div class="user-avatar">
                    <!-- TODO: Implementar avatares de usuário -->
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($usuario_logado['nome']) ?></span>
                    <span class="user-role"><?= htmlspecialchars($usuario_logado['privilegio']) ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>

            <!-- Dropdown do menu do usuário -->
            <div class="user-menu-dropdown">
                <a href="<?php echo url('/src/Usuario/perfil.php'); ?>" class="user-menu-item">
                    <i class="fas fa-user"></i>
                    <span>Meu Perfil</span>
                </a>
                <a href="<?php echo url('/src/Usuario/configuracoes.php'); ?>" class="user-menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo url('/public/logout.php'); ?>" class="user-menu-item text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts específicos do top-header -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Atualiza o título da página baseado no título do documento
            const titleElement = document.querySelector('.top-header-title');
            const pageTitle = document.title.split('|')[0].trim();
            titleElement.textContent = pageTitle;

            // Toggle do menu mobile
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    document.body.classList.toggle('sidebar-open');
                });
            }

            // Fecha o menu mobile quando clicar fora
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && // Apenas em mobile
                    !e.target.closest('.sidebar') && 
                    !e.target.closest('.menu-toggle') &&
                    sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            });

            // Gerencia as notificações (placeholder para futura implementação)
            const notificationToggle = document.querySelector('.notification-toggle');
            if (notificationToggle) {
                notificationToggle.addEventListener('click', function() {
                    // TODO: Implementar lógica de notificações
                    console.log('Notificações não implementadas ainda');
                });
            }
        });
    </script>
</header>