<?php
// verifica_sessao.php - Garante que o usuário está logado.
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$usuario_logado = [
    'id' => $_SESSION['user_id'] ?? null,
    'nome' => $_SESSION['nome'] ?? 'Usuário Desconhecido',
    'privilegio' => $_SESSION['privilegio'] ?? 'Vendedor',
];
?>