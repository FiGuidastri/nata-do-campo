<?php
// top-header.php - Cabeçalho superior (incluído em todas as páginas)
// Requer que a variável $usuario_logado esteja definida.
?>
<header class="top-header">
    <button id="menu-toggle" class="menu-toggle"><i class="fas fa-bars"></i></button> 
    
    <div class="logo"><img src="logo.png" alt="Logo Nata do Campo"></div>
    
    <div class="user-info">
        <span class="user-name">Olá, <span><?= htmlspecialchars($usuario_logado['nome']) ?></span> (<?= $usuario_logado['privilegio'] ?>)</span>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </div>
</header>