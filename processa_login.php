<?php
// processa_login.php - Processa as credenciais e inicia a sessão.
session_start();
// O ponto de falha mais comum. Se aqui falhar, o PHP para.
require_once 'conexao.php'; 

// Adiciona um log simples para debug - VERIFIQUE ESTE ARQUIVO!
function log_login_attempt($message) {
    $file = 'login_debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    error_log("$timestamp $message\n", 3, $file);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha_digitada = $_POST['senha'] ?? '';
    
    // Log inicial
    log_login_attempt("Tentativa de login para email: $email");

    // 1. Validação Básica
    if (empty($email) || empty($senha_digitada)) {
        log_login_attempt("Falha: E-mail ou senha vazios.");
        header("Location: login.php?error=invalid");
        exit();
    }

    // 2. Busca do Usuário
    $sql = "SELECT id, nome, email, senha_hash, privilegio FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        log_login_attempt("Falha na preparação da consulta SQL: " . $conn->error);
        header("Location: login.php?error=sql_error"); // Redireciona para um erro de SQL
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        log_login_attempt("Falha: Usuário '$email' não encontrado no DB.");
        header("Location: login.php?error=invalid");
        exit();
    }

    // 3. Verificação de Senha (CRÍTICO)
    // Se o hash não bater, esta função retorna false.
    if (password_verify($senha_digitada, $user['senha_hash'])) {
        // SUCESSO!
        log_login_attempt("Sucesso: Login para usuário ID " . $user['id']);
        
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['privilegio'] = $user['privilegio'];

        $conn->close();
        header("Location: lancamento_venda.php");
        exit();
        
    } else {
        // FALHA NA SENHA
        log_login_attempt("Falha: Senha incorreta para usuário ID " . $user['id'] . ". Hash no DB: " . $user['senha_hash']);
        header("Location: login.php?error=invalid");
        exit();
    }

    $conn->close();
} else {
    header("Location: login.php");
    exit();
}