<?php
require_once '../config/config.php';
require_once '../config/conexao.php';
require_once '../src/Utils/estoque_util.php';

echo "=== Iniciando testes de funcionalidades de estoque ===\n\n";

// Função auxiliar para log
function logTeste($mensagem, $sucesso = true) {
    $simbolo = $sucesso ? "✓" : "✗";
    echo "$simbolo $mensagem\n";
}

$db = Database::getInstance();
$conn = $GLOBALS['conn']; // Usando a conexão global do arquivo conexao.php

// 1. Teste da função baixaEstoqueFIFO
try {
    // Criar uma venda de teste
    $conn->begin_transaction();
    
    $sql = "INSERT INTO vendas (cliente_id, data_venda, usuario_id) VALUES (1, NOW(), 1)";
    $result = $conn->query($sql);
    $venda_id = $conn->insert_id;
    
    // Adicionar item à venda
    $stmt = $conn->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade) VALUES (?, 1, 1)");
    $stmt->bind_param("i", $venda_id);
    $stmt->execute();
    
    // Testar baixa FIFO
    $resultado = baixaEstoqueFIFO($conn, $venda_id);
    
    if ($resultado['success']) {
        logTeste("Baixa FIFO realizada com sucesso");
    } else {
        throw new Exception("Falha na baixa FIFO: " . $resultado['message']);
    }
    
    $conn->rollback();
} catch (Exception $e) {
    $conn->rollback();
    logTeste("Erro no teste de baixa FIFO: " . $e->getMessage(), false);
}

// 2. Teste da função getLotesDisponiveis
try {
    $lotes = getLotesDisponiveis($conn, 1); // Produto ID 1
    
    if (is_array($lotes)) {
        logTeste("Busca de lotes disponíveis funcionando - Encontrados " . count($lotes) . " lotes");
        
        // Verifica estrutura do retorno
        if (isset($lotes[0]['codigo_lote'])) {
            logTeste("Estrutura dos lotes correta");
        } else {
            throw new Exception("Estrutura dos lotes inválida");
        }
    } else {
        throw new Exception("Falha ao buscar lotes disponíveis");
    }
} catch (Exception $e) {
    logTeste("Erro no teste de getLotesDisponiveis: " . $e->getMessage(), false);
}

// 3. Teste da função verificaEstoqueSuficiente
try {
    $produto_id = 1;
    $quantidade = 1;
    
    if (verificaEstoqueSuficiente($conn, $produto_id, $quantidade)) {
        logTeste("Verificação de estoque suficiente funcionando");
    } else {
        throw new Exception("Produto sem estoque suficiente");
    }
    
    // Teste com quantidade impossível
    $quantidade_impossivel = 999999;
    if (!verificaEstoqueSuficiente($conn, $produto_id, $quantidade_impossivel)) {
        logTeste("Verificação de estoque insuficiente funcionando");
    } else {
        throw new Exception("Falha ao detectar estoque insuficiente");
    }
} catch (Exception $e) {
    logTeste("Erro no teste de verificaEstoqueSuficiente: " . $e->getMessage(), false);
}

// 4. Teste da função getProdutosEstoqueBaixo
try {
    $produtos_baixos = getProdutosEstoqueBaixo($conn);
    
    if (is_array($produtos_baixos)) {
        logTeste("Busca de produtos com estoque baixo funcionando - Encontrados " . count($produtos_baixos) . " produtos");
        
        // Verifica estrutura do retorno
        if (isset($produtos_baixos[0]['estoque_minimo'])) {
            logTeste("Estrutura dos produtos com estoque baixo correta");
        } else {
            throw new Exception("Estrutura dos produtos com estoque baixo inválida");
        }
    } else {
        throw new Exception("Falha ao buscar produtos com estoque baixo");
    }
} catch (Exception $e) {
    logTeste("Erro no teste de getProdutosEstoqueBaixo: " . $e->getMessage(), false);
}

echo "\n=== Testes de funcionalidades de estoque concluídos ===\n";
?>