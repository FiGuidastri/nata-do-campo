<?php
require_once __DIR__ . '/../../includes/layout.php';
render_header('Login');
?>

<div class="login-container">
    <div class="logo">
        <img src="<?php echo url('/public/assets/images/nata.png'); ?>" alt="Logo Nata do Campo">
    </div>
    
    <form action="<?php echo url('/api/login'); ?>" method="POST" class="login-form">
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            $error = $_GET['error'];
            switch($error) {
                case 'invalid':
                    echo 'Email ou senha inválidos';
                    break;
                case 'empty':
                    echo 'Por favor, preencha todos os campos';
                    break;
                default:
                    echo 'Erro ao fazer login';
            }
            ?>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
        </div>

        <button type="submit" class="btn btn-primary">Entrar</button>
    </form>
</div>

<?php render_footer(); ?>