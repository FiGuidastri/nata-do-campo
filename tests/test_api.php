<?php
require_once '../config/config.php';
require_once '../config/conexao.php';

echo "=== Iniciando testes dos endpoints da API ===\n\n";

// Função auxiliar para log
function logTeste($mensagem, $sucesso = true) {
    $simbolo = $sucesso ? "✓" : "✗";
    echo "$simbolo $mensagem\n";
}

// Função para fazer requisições HTTP
function fazerRequisicao($endpoint, $metodo = 'GET', $dados = null) {
    $ch = curl_init();
    
    $url = "http://localhost/nata-do-campo/api/" . $endpoint;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($metodo === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($dados) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $resultado = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'codigo' => $httpcode,
        'resposta' => json_decode($resultado, true)
    ];
}

// 1. Teste do endpoint precos.php
try {
    $resultado = fazerRequisicao('precos.php');
    
    if ($resultado['codigo'] === 200 && is_array($resultado['resposta'])) {
        logTeste("Endpoint precos.php funcionando corretamente");
        
        // Verifica estrutura da resposta
        if (isset($resultado['resposta'][0]['produto_id'])) {
            logTeste("Estrutura de resposta dos preços correta");
        } else {
            throw new Exception("Estrutura de resposta dos preços inválida");
        }
    } else {
        throw new Exception("Falha ao acessar endpoint precos.php");
    }
} catch (Exception $e) {
    logTeste("Erro no teste de precos.php: " . $e->getMessage(), false);
}

// 2. Teste do endpoint pedidos.php
try {
    // Teste de listagem de pedidos
    $resultado = fazerRequisicao('pedidos.php');
    
    if ($resultado['codigo'] === 200 && is_array($resultado['resposta'])) {
        logTeste("Endpoint pedidos.php (GET) funcionando corretamente");
    } else {
        throw new Exception("Falha ao listar pedidos");
    }
    
    // Teste de criação de pedido
    $pedido_teste = [
        'cliente_id' => 1,
        'items' => [
            ['produto_id' => 1, 'quantidade' => 1]
        ]
    ];
    
    $resultado = fazerRequisicao('pedidos.php', 'POST', $pedido_teste);
    
    if ($resultado['codigo'] === 201 && isset($resultado['resposta']['pedido_id'])) {
        logTeste("Criação de pedido funcionando corretamente");
    } else {
        throw new Exception("Falha ao criar pedido");
    }
} catch (Exception $e) {
    logTeste("Erro no teste de pedidos.php: " . $e->getMessage(), false);
}

// 3. Teste de validação de pedido
try {
    $pedido_invalido = [
        'cliente_id' => 999, // ID inválido
        'items' => [
            ['produto_id' => 999, 'quantidade' => -1] // Quantidade inválida
        ]
    ];
    
    $resultado = fazerRequisicao('valida_pedido.php', 'POST', $pedido_invalido);
    
    if ($resultado['codigo'] === 400 && isset($resultado['resposta']['erros'])) {
        logTeste("Validação de pedidos funcionando corretamente");
    } else {
        throw new Exception("Falha na validação de pedidos");
    }
} catch (Exception $e) {
    logTeste("Erro no teste de valida_pedido.php: " . $e->getMessage(), false);
}

echo "\n=== Testes dos endpoints da API concluídos ===\n";
?>