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

    // 3. Prepara statements para uso no loop
    // Insert em itens_venda
    $sql_item = "INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, num_lote, status_lote, data_vencimento) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql_item);
    if ($stmt_item === false) throw new Exception("Erro ao preparar item_venda: " . $conn->error);
    
    // Update do saldo do lote
    $sql_lote_update = "UPDATE lotes_estoque SET saldo_atual = saldo_atual - ? WHERE id = ?";
    $stmt_lote_update = $conn->prepare($sql_lote_update);
    if ($stmt_lote_update === false) throw new Exception("Erro ao preparar update_lote: " . $conn->error);

    // Insert na movimentação de estoque
    $sql_movimentacao = "INSERT INTO movimentacao_estoque 
                        (lote_id, produto_id, tipo, quantidade, data_movimentacao, usuario_id, observacao) 
                        VALUES (?, ?, 'SAIDA', ?, NOW(), ?, CONCAT('Venda ID: ', ?))";
    $stmt_movimentacao = $conn->prepare($sql_movimentacao);
    if ($stmt_movimentacao === false) throw new Exception("Erro ao preparar movimentacao: " . $conn->error);

    // 4. Processa cada item de venda
    foreach ($itens_venda as $item) {
        $produto_id = (int)$item['produto_id'];
        $quantidade_total = (float)$item['quantidade'];
        $preco_unitario = (float)$item['preco_unitario'];
        $quantidade_restante = $quantidade_total;
        
        // Busca lotes disponíveis (FIFO/FEFO)
        $sql_lotes = "SELECT id, saldo_atual, num_lote, status_lote, data_vencimento 
                      FROM lotes_estoque 
                      WHERE produto_id = ? 
                        AND saldo_atual > 0 
                        AND status_lote = 'Liberado Todos'
                      ORDER BY data_vencimento ASC, data_entrada ASC";
        
        $stmt_lotes = $conn->prepare($sql_lotes);
        if ($stmt_lotes === false) throw new Exception("Erro ao preparar busca de lotes: " . $conn->error);
        
        $stmt_lotes->bind_param("i", $produto_id);
        $stmt_lotes->execute();
        $result_lotes = $stmt_lotes->get_result();

        if ($result_lotes->num_rows === 0) {
            throw new Exception("Estoque insuficiente para o Produto ID {$produto_id}. Nenhum lote disponível.");
        }

        // Processa cada lote até completar a quantidade necessária
        while (($row_lote = $result_lotes->fetch_assoc()) && $quantidade_restante > 0) {
            $lote_id = $row_lote['id'];
            $saldo_lote = (float)$row_lote['saldo_atual'];
            
            // Define quanto será baixado deste lote
            $quantidade_baixar = min($quantidade_restante, $saldo_lote);

            // Atualiza o saldo do lote
            $stmt_lote_update->bind_param("di", $quantidade_baixar, $lote_id);
            if (!$stmt_lote_update->execute()) {
                throw new Exception("Erro ao atualizar saldo do lote {$lote_id}: " . $stmt_lote_update->error);
            }

            // Registra a movimentação de estoque
            $stmt_movimentacao->bind_param("iidii", 
                $lote_id, 
                $produto_id, 
                $quantidade_baixar, 
                $usuario_id, 
                $venda_id
            );
            if (!$stmt_movimentacao->execute()) {
                throw new Exception("Erro ao registrar movimentação de estoque: " . $stmt_movimentacao->error);
            }

            // Insere o item de venda com os dados do lote
            $stmt_item->bind_param("iiddsss", 
                $venda_id, 
                $produto_id, 
                $quantidade_baixar, 
                $preco_unitario,
                $row_lote['num_lote'],
                $row_lote['status_lote'],
                $row_lote['data_vencimento']
            );
            if (!$stmt_item->execute()) {
                throw new Exception("Erro ao inserir item de venda: " . $stmt_item->error);
            }

            $quantidade_restante -= $quantidade_baixar;
        }

        $stmt_lotes->close();

        // Verifica se toda a quantidade foi atendida
        if ($quantidade_restante > 0.001) { // Usa 0.001 para lidar com erros de float
            throw new Exception("Estoque insuficiente para o Produto ID {$produto_id}. Faltam " . number_format($quantidade_restante, 3) . " unidades.");
        }
    }
    
    // 5. Fecha os statements
    $stmt_item->close();
    $stmt_lote_update->close();
    $stmt_movimentacao->close();
    
    // Commit da transação
    $conn->commit();
    $sucesso = true;
    
} catch (Exception $e) {
    // 6. Em caso de erro, desfaz tudo
    $conn->rollback();
    $sucesso = false;
    $error_msg = $e->getMessage();
    error_log("Erro no processamento da venda: " . $error_msg);
} finally {
    $conn->close();
}

// 7. Redirecionamento Final
if ($sucesso) {
    header("Location: painel_pedidos.php?success=venda_registrada&id=" . $venda_id . "&tipo=" . urlencode($tipo_transacao));
    exit();
} else {
    header("Location: lancamento_venda.php?error=" . urlencode("Falha no lançamento da Transação. Detalhe: " . $error_msg));
    exit();
}
?>