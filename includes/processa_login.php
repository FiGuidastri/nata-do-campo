<?php
/**
 * processa_login.php - Controlador de autenticação
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexao.php';

// Inicializa a sessão de forma segura
initSecureSession();

// Verifica o método da requisição
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . url('/public/login.php'));
    exit();
}

// Verifica token CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    logLoginAttempt('Tentativa de login com token CSRF inválido', 'warning', ['ip' => $_SERVER['REMOTE_ADDR']]);
    header("Location: " . url('/public/login.php?error=invalid_token'));
    exit();
}

// Coleta e sanitiza os dados
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($email) || empty($senha)) {
    logLoginAttempt('Tentativa de login com campos vazios', 'warning');
    header("Location: " . url('/public/login.php?error=empty_fields'));
    exit();
}

// Verifica se o email tem formato válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logLoginAttempt('Tentativa de login com email inválido', 'warning', ['email' => $email]);
    header("Location: " . url('/public/login.php?error=invalid_email'));
    exit();
}

try {
    // Busca o usuário
    $sql = "
        SELECT 
            id, 
            nome, 
            email, 
            senha_hash, 
            privilegio, 
            tentativas_login, 
            bloqueado_ate,
            ultimo_login,
            ativo
        FROM usuarios 
        WHERE email = ?
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verifica se o usuário existe
    if (!$user) {
        logLoginAttempt('Tentativa de login com email não cadastrado', 'warning', ['email' => $email]);
        header("Location: " . url('/public/login.php?error=invalid_credentials'));
        exit();
    }
    
    // Verifica se a conta está ativa
    if (!$user['ativo']) {
        logLoginAttempt('Tentativa de login em conta inativa', 'warning', [
            'user_id' => $user['id'],
            'email' => $email
        ]);
        header("Location: " . url('/public/login.php?error=account_inactive'));
        exit();
    }
    
    // Verifica se a conta está bloqueada
    if ($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()) {
        logLoginAttempt('Tentativa de login em conta bloqueada', 'warning', [
            'user_id' => $user['id'],
            'email' => $email,
            'bloqueado_ate' => $user['bloqueado_ate']
        ]);
        header("Location: " . url('/public/login.php?error=account_locked'));
        exit();
    }
    
    // Aplica o pepper à senha antes de verificar
    $senha_com_pepper = hash_hmac('sha256', $senha, PASSWORD_PEPPER);
    
    // Verifica a senha
    if (!password_verify($senha_com_pepper, $user['senha_hash'])) {
        // Incrementa contador de tentativas
        $novas_tentativas = ($user['tentativas_login'] ?? 0) + 1;
        
        // Se atingiu o limite de tentativas, bloqueia a conta
        if ($novas_tentativas >= 5) {
            $bloqueio = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $sql_block = "
                UPDATE usuarios 
                SET tentativas_login = ?, bloqueado_ate = ?
                WHERE id = ?
            ";
            $stmt = $conn->prepare($sql_block);
            $stmt->bind_param("isi", $novas_tentativas, $bloqueio, $user['id']);
            $stmt->execute();
            
            logLoginAttempt('Conta bloqueada por excesso de tentativas', 'warning', [
                'user_id' => $user['id'],
                'email' => $email,
                'tentativas' => $novas_tentativas
            ]);
            
            header("Location: " . url('/public/login.php?error=account_locked'));
            exit();
        }
        
        // Atualiza número de tentativas
        $sql_attempts = "UPDATE usuarios SET tentativas_login = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_attempts);
        $stmt->bind_param("ii", $novas_tentativas, $user['id']);
        $stmt->execute();
        
        logLoginAttempt('Senha incorreta', 'warning', [
            'user_id' => $user['id'],
            'email' => $email,
            'tentativas' => $novas_tentativas
        ]);
        
        header("Location: " . url('/public/login.php?error=invalid_credentials'));
        exit();
    }
    
    // Login bem-sucedido!
    
    // Reseta contadores de tentativa e bloqueio
    $sql_reset = "
        UPDATE usuarios 
        SET tentativas_login = 0, 
            bloqueado_ate = NULL,
            ultimo_login = NOW()
        WHERE id = ?
    ";
    $stmt = $conn->prepare($sql_reset);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    
    // Configura a sessão
    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nome'] = $user['nome'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['privilegio'] = $user['privilegio'];
    $_SESSION['ultimo_login'] = $user['ultimo_login'];
    $_SESSION['last_activity'] = time();
    
    // Regenera o ID da sessão após o login
    session_regenerate_id(true);
    
    logLoginAttempt('Login bem-sucedido', 'info', [
        'user_id' => $user['id'],
        'email' => $email,
        'privilegio' => $user['privilegio']
    ]);
    
    // Redireciona para a página solicitada ou para o dashboard
    $redirect = $_SESSION['redirect_after_login'] ?? '/src/Venda/lancamento_venda.php';
    unset($_SESSION['redirect_after_login']);
    
    header("Location: " . url($redirect));
    exit();
    
} catch (Exception $e) {
    // Log do erro
    logError('Erro no processamento do login: ' . $e->getMessage(), [
        'level' => 'error',
        'email' => $email,
        'trace' => $e->getTraceAsString()
    ]);
    
    header("Location: " . url('/public/login.php?error=system_error'));
    exit();
    
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}