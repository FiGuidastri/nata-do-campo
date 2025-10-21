<?php
// estoque_util.php - Funções utilitárias para manipulação de estoque.

/**
 * Retorna os lotes disponíveis para um produto específico.
 * 
 * @param mysqli $conn A conexão ativa com o banco de dados.
 * @param int $produto_id O ID do produto.
 * @return array Lista de lotes disponíveis com suas quantidades.
 */
function getLotesDisponiveis(mysqli $conn, int $produto_id): array {
    $sql = "SELECT 
                id, 
                codigo_lote,
                data_fabricacao,
                data_vencimento,
                saldo_atual
            FROM lotes_estoque 
            WHERE produto_id = ? 
                AND saldo_atual > 0 
            ORDER BY data_vencimento ASC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    $lotes = [];
    while ($row = $result->fetch_assoc()) {
        $lotes[] = $row;
    }
    
    return $lotes;
}

/**
 * Verifica se há estoque suficiente para uma quantidade específica de um produto.
 * 
 * @param mysqli $conn A conexão ativa com o banco de dados.
 * @param int $produto_id O ID do produto.
 * @param float $quantidade A quantidade necessária.
 * @return bool True se há estoque suficiente, false caso contrário.
 */
function verificaEstoqueSuficiente(mysqli $conn, int $produto_id, float $quantidade): bool {
    $sql = "SELECT SUM(saldo_atual) as total_disponivel 
            FROM lotes_estoque 
            WHERE produto_id = ? AND saldo_atual > 0";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return ($row['total_disponivel'] >= $quantidade);
}

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
/**
 * Verifica produtos com estoque baixo (abaixo do estoque mínimo).
 * 
 * @param mysqli $conn A conexão ativa com o banco de dados.
 * @return array Lista de produtos com estoque baixo.
 */
function getProdutosEstoqueBaixo(mysqli $conn): array {
    $sql = "SELECT 
                p.id,
                p.nome,
                p.estoque_minimo,
                COALESCE(SUM(le.saldo_atual), 0) as saldo_total
            FROM produtos p
            LEFT JOIN lotes_estoque le ON le.produto_id = p.id
            GROUP BY p.id
            HAVING saldo_total <= p.estoque_minimo
            ORDER BY p.nome";
    
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Erro ao consultar produtos com estoque baixo: " . $conn->error);
    }
    
    $produtos = [];
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }
    
    return $produtos;
}

/**
 * Registra uma movimentação de estoque.
 * 
 * @param mysqli $conn A conexão ativa com o banco de dados.
 * @param int $lote_id O ID do lote.
 * @param string $tipo O tipo de movimentação (entrada/saída).
 * @param float $quantidade A quantidade movimentada.
 * @param string $observacao Observação sobre a movimentação.
 * @return bool True se registrado com sucesso, false caso contrário.
 */
function registraMovimentacaoEstoque(mysqli $conn, int $lote_id, string $tipo, float $quantidade, string $observacao = ''): bool {
    $sql = "INSERT INTO movimentacoes_estoque (
                lote_id,
                tipo_movimentacao,
                quantidade,
                data_movimentacao,
                usuario_id,
                observacao
            ) VALUES (?, ?, ?, NOW(), ?, ?)";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar registro de movimentação: " . $conn->error);
    }
    
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    
    $stmt->bind_param("isdis", $lote_id, $tipo, $quantidade, $usuario_id, $observacao);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}
?>