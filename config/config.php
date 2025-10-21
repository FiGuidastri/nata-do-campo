<?php
/**
 * config.php - Configurações globais do sistema
 * 
 * IMPORTANTE: Em produção, este arquivo deve ter permissões restritas (0600)
 * e estar fora do diretório web público.
 */

// Carrega variáveis de ambiente se existir arquivo .env
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Configurações globais do sistema
define('BASE_URL', $_ENV['BASE_URL'] ?? '/nata-do-campo');
define('ROOT_PATH', __DIR__ . '/..');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);

// Configurações do banco de dados
define('DB_SERVER', $_ENV['DB_SERVER'] ?? 'localhost');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'nata_do_campo');

// Configurações de segurança
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? bin2hex(random_bytes(32)));
define('PASSWORD_PEPPER', $_ENV['PASSWORD_PEPPER'] ?? bin2hex(random_bytes(32)));
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 3600);

// Configurações de logging
define('LOG_PATH', ROOT_PATH . '/logs');
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'error');

// Configurações de upload
define('UPLOAD_PATH', ROOT_PATH . '/storage/uploads');
define('MAX_UPLOAD_SIZE', $_ENV['MAX_UPLOAD_SIZE'] ?? 5242880); // 5MB

// Inicialização do sistema
require_once __DIR__ . '/../src/Security/auth.php';

// Configura exibição de erros baseado no ambiente
if (APP_ENV === 'development' && APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Inicia sessão segura
initSecureSession();

// Função auxiliar para gerar URLs completas
function url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Função auxiliar para logs
function logError(string $message, array $context = []): void {
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    $logLevels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];
    
    $configuredLevel = $logLevels[strtolower(LOG_LEVEL)] ?? 3;
    $messageLevel = $context['level'] ?? 'error';
    $messageLevelNum = $logLevels[strtolower($messageLevel)] ?? 3;
    
    if ($messageLevelNum >= $configuredLevel) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $messageLevel,
            'message' => $message,
            'context' => $context
        ];
        
        file_put_contents(
            LOG_PATH . '/app.log',
            json_encode($logEntry) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

// Função para sanitizar inputs
function sanitize($input, string $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}