<?php
// conexao.php - Configurações de Conexão MySQL
// Usuário do banco: joaoco37_pedidos
// Banco de dados: joaoco37_pedidos

// --- Configurações Com Suas Credenciais ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
// 🚨 O PONTO CRÍTICO: COLOQUE A SENHA DO SEU USUÁRIO MYSQL (JOAOCO37_PEDIDOS) AQUI!
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'nata_do_campo'); 

// --- Tentativa de Conexão ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    // Se falhar, exibe uma mensagem clara de ERRO FATAL DE CONEXÃO
    die("
    <div style='border: 2px solid red; padding: 20px; margin: 50px auto; width: 80%; background-color: #fdd; font-family: Arial;'>
        <h2>ERRO CRÍTICO DE CONEXÃO COM O BANCO DE DADOS!</h2>
        <p><strong>Causa provável:</strong> Senha do MySQL incorreta no arquivo <code>conexao.php</code>.</p>
        <p><strong>Detalhe Técnico:</strong> Falha de conexão: " . $conn->connect_error . "</p>
        <p><strong>Ação:</strong> Edite o arquivo <code>conexao.php</code> e insira a senha correta na linha <code>define('DB_PASSWORD', 'SUA_SENHA_DO_MYSQL_AQUI');</code>.</p>
    </div>
    ");
}
$conn->set_charset("utf8");
?>