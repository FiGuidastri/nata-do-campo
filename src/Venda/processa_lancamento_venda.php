<?php
// processa_lancamento_venda.php - Processa o formulário de Venda, Bonificação ou Troca
require_once 'verifica_sessao.php';
require_once 'conexao.php';

// Redireciona se não for POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: lancamento_venda.php?error=metodo_invalido");
    exit();
}

// 1. Coleta e Validação Inicial de Dados
$cliente_id         = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
$data_venda         = isset($_POST['data_venda']) ? $_POST['data_venda'] : date('Y-m-d');
$tipo_transacao     = isset($_POST['tipo_transacao']) ? $_POST['tipo_transacao'] : 'Venda'; // Venda, Bonificacao, Troca
$observacao         = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';
$valor_total        = isset($_POST['valor_total']) ? (float)$_POST['valor_total'] : 0.00;
$valor_custo_base_total = isset($_POST['valor_custo_base_total']) ? (float)$_POST['valor_custo_base_total'] : 0.00;
$itens_venda_json   = isset($_POST['itens_venda_json']) ? $_POST['itens_venda_json'] : '[]';

$itens_venda = json_decode($itens_venda_json, true);

// Validação dos dados principais
if ($cliente_id <= 0 || empty($data_venda) || empty($itens_venda)) {
    header("Location: lancamento_venda.php?error=dados_ausentes");
    exit();
}

// Validação de observação para Bonificação/Troca
if (($tipo_transacao === 'Bonificacao' || $tipo_transacao === 'Troca') && strlen($observacao) < 5) {
    header("Location: lancamento_venda.php?error=observacao_obrigatoria");
    exit();
}

// Inicia a Transação SQL
$conn->begin_transaction();
$sucesso = true;
$error_msg = '';
$venda_id = 0;

try {
    // 2. Insere o registro principal da Venda na tabela 'vendas'
    $sql_venda = "INSERT INTO vendas (cliente_id, usuario_id, data_venda, valor_total, valor_custo_base, tipo_transacao, observacao) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_venda = $conn->prepare($sql_venda);
    if ($stmt_venda === false) throw new Exception("Erro ao preparar venda: " . $conn->error);
    
    // Associa o ID do usuário logado (autor da ação)
    $usuario_id = $usuario_logado['id']; 
    
    $stmt_venda->bind_param("iisddss", 
        $cliente_id, 
        $usuario_id, 
        $data_venda, 
        $valor_total, 
        $valor_custo_base_total,
        $tipo_transacao,
        $observacao
    );

    if (!$stmt_venda->execute()) throw new Exception("Erro ao inserir venda: " . $stmt_venda->error);
    
    $venda_id = $conn->insert_id;
    $stmt_venda->close();

    // 3. Processa cada item de venda
    $sql_item = "INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal, preco_custo_base) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt_item = $conn->prepare($sql_item);
    if ($stmt_item === false) throw new Exception("Erro ao preparar item_venda: " . $conn->error);
    
    // 4. Lógica de Baixa no Estoque (FIFO/FEFO)
    // Usaremos a tabela lotes_estoque para garantir a rastreabilidade
    $sql_lote_update = "UPDATE lotes_estoque SET quantidade_disponivel = quantidade_disponivel - ?, baixa_por = 'Venda', baixa_data = NOW(), venda_id = ? WHERE id = ?";
    $stmt_lote_update = $conn->prepare($sql_lote_update);
    if ($stmt_lote_update === false) throw new Exception("Erro ao preparar update_lote: " . $conn->error);

    $sql_estoque_update = "UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?";
    $stmt_estoque_update = $conn->prepare($sql_estoque_update);
    if ($stmt_estoque_update === false) throw new Exception("Erro ao preparar update_produto: " . $conn->error);
    

    foreach ($itens_venda as $item) {
        $produto_id     = (int)$item['produto_id'];
        $quantidade     = (float)$item['quantidade'];
        $preco_unitario = (float)$item['preco_unitario'];
        $preco_custo_base = (float)$item['preco_custo_base']; // Preço do PDV Indústria
        
        $subtotal = $quantidade * $preco_unitario;

        // 3a. Insere o item na tabela 'itens_venda'
        $stmt_item->bind_param("iiddds", 
            $venda_id, 
            $produto_id, 
            $quantidade, 
            $preco_unitario, 
            $subtotal,
            $preco_custo_base // Registra o custo base individualmente
        );
        if (!$stmt_item->execute()) throw new Exception("Erro ao inserir item_venda: " . $stmt_item->error);
        
        // 4a. Baixa do Estoque (Lógica FIFO/FEFO)
        $quantidade_restante = $quantidade;
        
        // Seleciona lotes disponíveis (Liberado Todos ou Liberado VDI) e ordena por Data de Validade (FEFO)
        // Se a data de validade não for usada, a ordenação por data de entrada (FIFO) é o padrão.
        $sql_lotes = "
            SELECT 
                id, 
                quantidade_disponivel 
            FROM lotes_estoque 
            WHERE 
                produto_id = ? 
                AND quantidade_disponivel > 0
                AND (status_lote = 'Liberado Todos' OR status_lote = 'Liberado VDI')
            ORDER BY 
                data_validade ASC, -- FEFO (First Expired, First Out)
                data_entrada ASC    -- FIFO (First In, First Out)
            LIMIT 10 -- Limita o número de lotes para processar
        ";
        
        $stmt_lotes = $conn->prepare($sql_lotes);
        if ($stmt_lotes === false) throw new Exception("Erro ao preparar busca de lotes: " . $conn->error);
        $stmt_lotes->bind_param("i", $produto_id);
        $stmt_lotes->execute();
        $result_lotes = $stmt_lotes->get_result();

        if ($result_lotes->num_rows === 0) {
            throw new Exception("Estoque insuficiente para o Produto ID {$produto_id}. Nenhum lote disponível.");
        }

        while ($row_lote = $result_lotes->fetch_assoc() && $quantidade_restante > 0) {
            $lote_id = $row_lote['id'];
            $qtd_lote = (float)$row_lote['quantidade_disponivel'];
            
            $qtd_a_dar_baixa = min($quantidade_restante, $qtd_lote);

            // 4b. Atualiza o lote
            $stmt_lote_update->bind_param("dii", $qtd_a_dar_baixa, $venda_id, $lote_id);
            if (!$stmt_lote_update->execute()) throw new Exception("Erro ao dar baixa no Lote ID {$lote_id}: " . $stmt_lote_update->error);

            $quantidade_restante -= $qtd_a_dar_baixa;
        }

        $stmt_lotes->close();

        // Verifica se a baixa foi completa
        if ($quantidade_restante > 0.001) { // Usa 0.001 para lidar com erros de float
             throw new Exception("Estoque insuficiente para o Produto ID {$produto_id}. Faltam " . number_format($quantidade_restante, 2) . " unidades.");
        }

        // 4c. Atualiza o estoque total do produto na tabela 'produtos'
        $stmt_estoque_update->bind_param("di", $quantidade, $produto_id);
        if (!$stmt_estoque_update->execute()) throw new Exception("Erro ao atualizar estoque do produto: " . $stmt_estoque_update->error);
    }
    
    // 5. Finaliza as transações
    $stmt_item->close();
    $stmt_lote_update->close();
    $stmt_estoque_update->close();
    
    $conn->commit();
    
} catch (Exception $e) {
    // 6. Em caso de erro, desfaz tudo (Rollback)
    $conn->rollback();
    $sucesso = false;
    $error_msg = $e->getMessage();
    error_log("Erro no processamento da transação: " . $error_msg);
}

$conn->close();

// 7. Redirecionamento Final
if ($sucesso) {
    header("Location: painel_pedidos.php?success=venda_registrada&id=" . $venda_id . "&tipo=" . urlencode($tipo_transacao));
    exit();
} else {
    // Redireciona de volta para o formulário com a mensagem de erro
    header("Location: lancamento_venda.php?error=" . urlencode("Falha no lançamento da Transação. Detalhe: " . $error_msg));
    exit();
}

?>