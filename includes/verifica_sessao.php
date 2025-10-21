<?php
/**
 * verifica_sessao.php - Middleware de autenticação e autorização
 */

require_once __DIR__ . '/../config/config.php';

// Garante que a sessão está iniciada de forma segura
initSecureSession();

// Verifica autenticação
if (!isAuthenticated()) {
    // Salva URL atual para redirecionamento pós-login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Registra tentativa de acesso não autorizado
    logError('Tentativa de acesso não autorizado', [
        'level' => 'warning',
        'uri' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    // Redireciona para login
    header("Location: " . url('/public/login.php'));
    exit;
}

// Verifica expiração da sessão
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    secureLogout();
    header("Location: " . url('/public/login.php?msg=session_expired'));
    exit;
}

// Atualiza timestamp da última atividade
$_SESSION['last_activity'] = time();

// Define dados do usuário logado
$usuario_logado = [
    'id' => $_SESSION['user_id'] ?? null,
    'nome' => $_SESSION['nome'] ?? 'Usuário Desconhecido',
    'privilegio' => $_SESSION['privilegio'] ?? 'Visitante',
    'email' => $_SESSION['email'] ?? '',
];

// Validação adicional de segurança
if (!$usuario_logado['id'] || !$usuario_logado['privilegio']) {
    secureLogout();
    header("Location: " . url('/public/login.php?msg=invalid_session'));
    exit;
}

// Define constantes de segurança para o request atual
define('USER_ID', $usuario_logado['id']);
define('USER_PRIVILEGE', $usuario_logado['privilegio']);

// Validação de CSRF para requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        http_response_code(403);
        die('Token CSRF inválido ou expirado. Por favor, recarregue a página e tente novamente.');
    }
}
?>