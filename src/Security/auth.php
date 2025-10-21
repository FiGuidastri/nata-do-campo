<?php
/**
 * auth.php - Funções centralizadas de autenticação e segurança
 */

// Função para verificar se o usuário está autenticado
function isAuthenticated(): bool {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

// Função para verificar privilégios do usuário
function hasPrivilege(string|array $requiredPrivileges): bool {
    if (!isAuthenticated()) {
        return false;
    }

    $userPrivilege = $_SESSION['privilegio'] ?? '';
    
    // Admin tem acesso total
    if ($userPrivilege === 'Admin') {
        return true;
    }

    // Converte para array se for string
    if (is_string($requiredPrivileges)) {
        $requiredPrivileges = [$requiredPrivileges];
    }

    return in_array($userPrivilege, $requiredPrivileges);
}

// Função para validar a força da senha
function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'A senha deve ter no mínimo 8 caracteres';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'A senha deve conter pelo menos uma letra maiúscula';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'A senha deve conter pelo menos uma letra minúscula';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'A senha deve conter pelo menos um número';
    }
    
    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        $errors[] = 'A senha deve conter pelo menos um caractere especial';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// Função para fazer log de tentativas de login de forma segura
function logLoginAttempt(string $message, string $type = 'info', ?array $context = null): void {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'ip' => $ip,
        'type' => $type,
        'message' => $message,
        'user_agent' => $userAgent,
        'context' => $context
    ];
    
    $logFile = __DIR__ . '/../../logs/auth.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(
        $logFile,
        json_encode($logEntry) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

// Função para iniciar uma sessão de forma segura
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações de segurança da sessão
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', 3600); // 1 hora
        
        session_start();
    }
    
    // Regenera o ID da sessão periodicamente para prevenir fixação de sessão
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Função para limpar dados sensíveis da sessão ao fazer logout
function secureLogout(): void {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

// Função para prevenir CSRF
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Função para gerar campo hidden com token CSRF
function csrfField(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}