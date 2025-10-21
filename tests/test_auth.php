<?php
require_once '../config/config.php';
require_once '../config/conexao.php';
require_once '../includes/verifica_sessao.php';

echo "=== Iniciando testes de autenticação ===\n\n";

// Função auxiliar para log
function logTeste($mensagem, $sucesso = true) {
    $simbolo = $sucesso ? "✓" : "✗";
    echo "$simbolo $mensagem\n";
}

// 1. Teste de login com credenciais válidas
try {
    session_start();
    
    $email = "admin@teste.com";
    $senha = "123456";
    
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, nome, email, nivel_acesso FROM usuarios WHERE email = ? AND senha = ?");
    $senha_hash = hash('sha256', $senha);
    $stmt->bind_param("ss", $email, $senha_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($usuario = $result->fetch_assoc()) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel_acesso'];
        
        logTeste("Login realizado com sucesso para: " . $usuario['nome']);
    } else {
        throw new Exception("Credenciais inválidas");
    }
    
    // 2. Teste de verificação de sessão
    if (isset($_SESSION['usuario_id'])) {
        logTeste("Sessão criada corretamente");
    } else {
        throw new Exception("Sessão não foi criada");
    }
    
    // 3. Teste de CSRF Token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    if (strlen($_SESSION['csrf_token']) === 64) {
        logTeste("Token CSRF gerado corretamente");
    } else {
        throw new Exception("Token CSRF inválido");
    }
    
    // 4. Teste de níveis de acesso
    $niveis_permitidos = ['admin', 'gerente'];
    if (in_array($_SESSION['usuario_nivel'], $niveis_permitidos)) {
        logTeste("Verificação de nível de acesso funcionando");
    } else {
        throw new Exception("Usuário sem nível de acesso adequado");
    }
    
    // 5. Teste de logout
    session_destroy();
    if (!isset($_SESSION['usuario_id'])) {
        logTeste("Logout realizado com sucesso");
    } else {
        throw new Exception("Falha ao realizar logout");
    }
    
} catch (Exception $e) {
    logTeste("Erro nos testes de autenticação: " . $e->getMessage(), false);
}

echo "\n=== Testes de autenticação concluídos ===\n";
?>