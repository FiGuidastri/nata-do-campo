<?php
/**
 * logout.php - Controlador de logout
 */

require_once __DIR__ . '/../config/config.php';

// Verifica se há uma sessão ativa
initSecureSession();

// Registra o logout
if (isset($_SESSION['user_id'])) {
    logLoginAttempt('Logout realizado', 'info', [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? 'Unknown'
    ]);
}

// Realiza o logout de forma segura
secureLogout();

// Redireciona para a página de login
header("Location: " . url('/public/login.php?msg=logout_success'));
exit;
?>