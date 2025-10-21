<?php
/**
 * conexao.php - Configurações de Conexão MySQL
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../src/Database/Database.php';

// Inicia conexão usando singleton
try {
    $db = Database::getInstance();
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    if (APP_ENV === 'development') {
        die("
        <div style='border: 2px solid red; padding: 20px; margin: 50px auto; width: 80%; background-color: #fdd; font-family: Arial;'>
            <h2>ERRO CRÍTICO DE CONEXÃO COM O BANCO DE DADOS!</h2>
            <p><strong>Causa provável:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>Detalhes:</strong></p>
            <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
            <p><strong>Ação:</strong> Verifique as configurações no arquivo <code>.env</code> e certifique-se que o banco de dados está acessível.</p>
        </div>
        ");
    } else {
        // Em produção, mostra mensagem genérica
        http_response_code(500);
        die("Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.");
    }
}
?>