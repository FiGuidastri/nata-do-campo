<?php
// login.php - Tela de Login (PHP necessário para exibir erro)
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Vendas Nata do Campo</title>
    <link rel="stylesheet" href="<?php echo url('/public/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="logo"><img src="<?php echo url('/public/assets/images/nata.png'); ?>" alt="Logo Nata do Campo"></div>
        <h1>Acesso ao Sistema</h1>
        
        <form action="<?php echo url('/public/processa_login.php'); ?>" method="POST">
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder=" ">
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" class="form-control" id="senha" name="senha" required placeholder=" ">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;"><i class="fas fa-sign-in-alt"></i> Entrar</button>
            
            <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid'): ?>
                <p class="feedback-message status-error" style="margin-top: 15px;">E-mail ou senha inválidos.</p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>