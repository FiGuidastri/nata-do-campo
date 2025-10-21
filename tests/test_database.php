<?php
require_once '../config/config.php';
require_once '../config/conexao.php';

// Função auxiliar para log
function logTeste($mensagem, $sucesso = true) {
    $simbolo = $sucesso ? "✓" : "✗";
    echo "$simbolo $mensagem\n";
}

echo "=== Iniciando testes de banco de dados ===\n\n";

// 1. Teste de conexão
try {
    $db = Database::getInstance();
    echo "✓ Conexão com banco estabelecida com sucesso\n";
} catch (Exception $e) {
    echo "✗ Erro na conexão com banco: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Teste de consulta simples
try {
    $result = $db->query("SELECT NOW() as current_time");
    if ($result && $row = $result->fetch_assoc()) {
        logTeste("Consulta simples executada com sucesso. Hora atual: " . $row['current_time']);
    } else {
        throw new Exception("Erro ao executar consulta simples");
    }
} catch (Exception $e) {
    logTeste("Erro na consulta simples: " . $e->getMessage(), false);
}

// 3. Teste de prepared statement
try {
    $stmt = $db->prepare("SELECT id, nome FROM produtos WHERE id = ?");
    $id = 1;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        logTeste("Prepared statement executado com sucesso. Produto encontrado: " . $row['nome']);
    } else {
        logTeste("Prepared statement executado, mas nenhum produto encontrado com ID 1");
    }
    $stmt->close();
} catch (Exception $e) {
    logTeste("Erro no prepared statement: " . $e->getMessage(), false);
}

// 4. Teste de transação
try {
    $db->beginTransaction();
    
    // Inserir um registro temporário
    $stmt = $db->prepare("INSERT INTO produtos (nome, descricao) VALUES (?, ?)");
    $nome = "Produto Teste";
    $descricao = "Produto temporário para teste de transação";
    $stmt->bind_param("ss", $nome, $descricao);
    $stmt->execute();
    $id_inserido = $stmt->insert_id;
    $stmt->close();
    
    // Deletar o registro temporário
    $stmt = $db->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id_inserido);
    $stmt->execute();
    $stmt->close();
    
    $db->commit();
    logTeste("Transação executada com sucesso");
} catch (Exception $e) {
    $db->rollback();
    logTeste("Erro na transação: " . $e->getMessage(), false);
}

echo "\n=== Testes de banco de dados concluídos ===\n";
?>