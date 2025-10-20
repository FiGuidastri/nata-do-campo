<?php
// estoque_util.php - Funções utilitárias para manipulação de estoque.

/**
 * Realiza a baixa de estoque de todos os itens de uma venda, seguindo o critério FIFO (First-In, First-Out, 
 * priorizando a validade mais próxima).
 *
 * @param mysqli $conn A conexão ativa com o banco de dados.
 * @param int $venda_id O ID da venda a ser processada.
 * @return array Retorna ['success' => bool, 'message' => string].
 */
function baixaEstoqueFIFO(mysqli $conn, int $venda_id): array {
    // 1. Recupera os itens da venda que precisam de baixa (Tabela: itens_venda)
    $sql_itens = "SELECT produto_id, quantidade FROM itens_venda WHERE venda_id = ?";
    $stmt_itens = $conn->prepare($sql_itens);
    
    if (!$stmt_itens) { return ['success' => false, 'message' => "Erro de preparação de SQL (Itens Venda): " . $conn->error]; }
    
    $stmt_itens->bind_param("i", $venda_id);
    $stmt_itens->execute();
    $result_itens = $stmt_itens->get_result();
    $stmt_itens->close();
    
    if ($result_itens->num_rows === 0) { return ['success' => true, 'message' => 'Venda sem itens.']; }

    while ($item = $result_itens->fetch_assoc()) {
        $produto_id = $item['produto_id'];
        $quantidade_necessaria = $item['quantidade'];
        $quantidade_pendente = $quantidade_necessaria;

        // 2. Recupera os lotes disponíveis (Tabela: lotes_estoque)
        // CORREÇÃO: Usando a coluna confirmada: data_vencimento para ordenar FIFO
        $sql_lotes = "
            SELECT id, saldo_atual 
            FROM lotes_estoque 
            WHERE produto_id = ? AND saldo_atual > 0 
            ORDER BY data_vencimento ASC, id ASC"; 
        
        $stmt_lotes = $conn->prepare($sql_lotes);

        if (!$stmt_lotes) { return ['success' => false, 'message' => "Erro de preparação de SQL (Lotes): " . $conn->error]; }

        $stmt_lotes->bind_param("i", $produto_id);
        $stmt_lotes->execute();
        $result_lotes = $stmt_lotes->get_result();
        $stmt_lotes->close();

        if ($result_lotes->num_rows === 0) {
            return ['success' => false, 'message' => "Estoque zero para o Produto ID {$produto_id}."];
        }

        // 3. Processa a baixa lote por lote (Consumo)
        while ($lote = $result_lotes->fetch_assoc()) {
            if ($quantidade_pendente <= 0) break; 

            $lote_id = $lote['id'];
            $saldo_lote = $lote['saldo_atual'];

            $consumir = min($quantidade_pendente, $saldo_lote);
            
            // Atualiza o saldo do lote no banco
            $sql_update_lote = "UPDATE lotes_estoque SET saldo_atual = saldo_atual - ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_lote);
            
            if (!$stmt_update) { return ['success' => false, 'message' => "Erro de preparação de SQL (Update Lote): " . $conn->error]; }
            
            $stmt_update->bind_param("di", $consumir, $lote_id); 
            
            if (!$stmt_update->execute()) {
                $stmt_update->close();
                return ['success' => false, 'message' => "Erro ao atualizar o Lote ID {$lote_id}: " . $conn->error];
            }
            $stmt_update->close();

            // Atualiza a quantidade pendente
            $quantidade_pendente -= $consumir;
        }
        
        // 4. Verifica se todo o estoque necessário foi encontrado
        if ($quantidade_pendente > 0) {
            return ['success' => false, 'message' => "Estoque insuficiente para o Produto ID {$produto_id}. Faltam {$quantidade_pendente} unidades."];
        }
    }
    
    return ['success' => true, 'message' => 'Baixa FIFO concluída.'];
}
?>