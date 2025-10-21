<?php
// processa_cadastro_estoque.php

// 1. Inclui arquivos necessários
// Garanta que 'verifica_sessao.php' e 'conexao.php' existam e funcionem.
require_once 'verifica_sessao.php'; 
require_once 'conexao.php';         

// Garante que a requisição é um POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cadastro_estoque.php");
    exit();
}

// 2. Coleta de Dados
$produto_id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
$num_lote = trim($_POST['num_lote'] ?? '');
$quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_FLOAT);
$data_entrada = trim($_POST['data_entrada'] ?? '');
$data_vencimento = trim($_POST['data_vencimento'] ?? NULL);
$fornecedor_id = filter_input(INPUT_POST, 'fornecedor_id', FILTER_VALIDATE_INT);

// Captura e tratamento do status_lote (Correção do ENUM)
$status_lote = trim($_POST['status_lote'] ?? ''); 

// 3. Obter ID do Usuário Logado
$usuario_id = $_SESSION['usuario_id'] ?? NULL; 
if (!$usuario_id) {
    header("Location: login.php?msg=" . urlencode("Sessão expirada. Faça login novamente."));
    exit();
}

// 4. Validação do ENUM para 'status_lote'
$valid_statuses = ['Liberado Todos', 'Liberado VDI', 'Bloqueado'];

if (empty($status_lote) || !in_array($status_lote, $valid_statuses, true)) {
    $status_lote = 'Liberado Todos'; 
}

// 5. Validação de Dados Obrigatórios
if (!$produto_id || empty($num_lote) || $quantidade <= 0 || empty($data_entrada)) {
    header("Location: cadastro_estoque.php?status=error&msg=" . urlencode("Dados obrigatórios incompletos ou inválidos."));
    exit();
}

// 6. Preparação dos Dados para o DB
$saldo_atual = $quantidade;

// Tratamento de NULL para campos opcionais
$data_vencimento_db = !empty($data_vencimento) ? $data_vencimento : NULL;
$fornecedor_id_db = ($fornecedor_id > 0) ? $fornecedor_id : NULL;

// 7. Iniciar Transação
$conn->begin_transaction();

try {
    // 7.1. Inserção na Tabela de Lotes (lotes_estoque)
    
    // **CORREÇÃO APLICADA:** 'usuario_entrada' foi substituído por 'usuario_id'
    $sql_lote = "INSERT INTO lotes_estoque 
                 (produto_id, num_lote, quantidade, saldo_atual, data_entrada, data_vencimento, fornecedor_id, usuario_id, status_lote) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                 
    $stmt_lote = $conn->prepare($sql_lote);

    // Tipos: i, s, d, d, s, s, s, s, s (Usando 's' para opcionais/chaves para segurança contra NULL)
    $tipos_param = "isdssssss";

    // Bind dos parâmetros
    $stmt_lote->bind_param($tipos_param, 
        $produto_id, 
        $num_lote, 
        $quantidade, 
        $saldo_atual, 
        $data_entrada, 
        (string)$data_vencimento_db,
        (string)$fornecedor_id_db,
        (string)$usuario_id,         // <--- VARIÁVEL usuario_id como string
        $status_lote 
    );

    if (!$stmt_lote->execute()) {
        throw new Exception("Erro ao inserir lote: " . $stmt_lote->error);
    }
    
    $novo_lote_id = $conn->insert_id;

    // 7.2. Registro no Histórico de Movimentação (movimentacao_estoque)
    $observacao = "Entrada de Estoque/Lote {$num_lote}";
    $data_movimentacao = $data_entrada; 
    
    $sql_historico = "INSERT INTO movimentacao_estoque 
                      (lote_id, produto_id, tipo, quantidade, data_movimentacao, usuario_id, observacao) 
                      VALUES (?, ?, 'ENTRADA', ?, ?, ?, ?)";
                      
    $stmt_historico = $conn->prepare($sql_historico);

    if (!$stmt_historico) {
        throw new Exception("Erro na preparação do histórico: " . $conn->error);
    }
    
    // Tipos: i, i, d, s, i, s
    $stmt_historico->bind_param("iidiss", 
        $novo_lote_id, 
        $produto_id, 
        $quantidade, 
        $data_movimentacao, 
        $usuario_id, 
        $observacao
    );

    if (!$stmt_historico->execute()) {
        throw new Exception("Erro ao registrar histórico: " . $stmt_historico->error);
    }

    // 7.3. Commit da Transação
    $conn->commit();

    // Redireciona com sucesso
    header("Location: cadastro_estoque.php?status=success&msg=" . urlencode("Lote '{$num_lote}' de {$quantidade} registrado com sucesso."));
    
} catch (Exception $e) {
    // 7.4. Rollback em caso de erro
    $conn->rollback();
    error_log("Erro crítico no cadastro de estoque: " . $e->getMessage());
    header("Location: cadastro_estoque.php?status=error&msg=" . urlencode("Erro ao lançar estoque: " . $e->getMessage()));
    
} finally {
    // 7.5. Fechar statements e conexão
    if (isset($stmt_lote)) $stmt_lote->close();
    if (isset($stmt_historico)) $stmt_historico->close();
    if (isset($conn)) $conn->close();
    exit();
}
?>