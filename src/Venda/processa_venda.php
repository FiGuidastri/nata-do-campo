<?php
// processa_venda.php - Salva a venda e seus itens com transação segura, incluindo
// DÉBITO DE ESTOQUE por Lote (FIFO) e registro em movimentacao_estoque.

// Garante que a sessão e a conexão estejam configuradas
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// ID do usuário logado para registrar a venda e a movimentação
if (!isset($usuario_logado)) {
    header("Location: login.php");
    exit();
}
$usuario_id = $usuario_logado['id']; 


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inicia a transação segura
    $conn->begin_transaction();
    $itens_vendidos_para_processar = []; // Array para armazenar produto_id, quantidade e preço

    try {
        // --- 1. COLETA E SANITIZAÇÃO DE DADOS DO CABEÇALHO ---
        $cliente_id = intval($_POST['cliente_id']);
        $data_entrega = trim($_POST['data_entrega']);
        $forma_pagamento = trim($_POST['forma_pagamento']);
        
        // Converte o valor total para float, tratando vírgula como decimal
        $valor_total = floatval(str_replace(',', '.', trim($_POST['valor_total'] ?? '0')));
        
        // Verifica dados básicos
        if ($cliente_id <= 0 || $valor_total <= 0) { 
            throw new Exception("Dados da venda inválidos. Selecione um cliente e adicione itens."); 
        }

        // --- 2. INSERE O CABEÇALHO DA VENDA ---
        $sql_venda = "INSERT INTO vendas (cliente_id, data_venda, data_entrega, forma_pagamento, valor_total, status, usuario_id) 
                      VALUES (?, NOW(), ?, ?, ?, 'Processando', ?)";
        $stmt_venda = $conn->prepare($sql_venda);
        
        // Parâmetros: i (cliente_id), s (data_entrega), s (forma_pagamento), d (valor_total), i (usuario_id)
        $stmt_venda->bind_param("issdi", $cliente_id, $data_entrega, $forma_pagamento, $valor_total, $usuario_id);
        
        if (!$stmt_venda->execute()) { 
            throw new Exception("Erro ao salvar cabeçalho da venda: " . $stmt_venda->error); 
        }

        $venda_id = $conn->insert_id;
        $stmt_venda->close();

        // --- 3. PROCESSA ITENS E PREPARA PARA BAIXA DE ESTOQUE ---
        $item_count = 0;
        foreach ($_POST as $key => $value) {
            // Identifica itens pela chave 'qtd_' e quantidade maior que zero
            if (strpos($key, 'qtd_') === 0 && floatval(str_replace(',', '.', trim($value))) > 0) {
                $sku = str_replace('qtd_', '', $key);
                $quantidade = floatval(str_replace(',', '.', trim($value))); 

                // Busca o ID do Produto e Preço (Segurança: usa o preço real do DB)
                $sql_preco = "SELECT pr.preco, p.id AS produto_id 
                              FROM precos pr 
                              JOIN produtos p ON pr.produto_id = p.id
                              JOIN clientes c ON c.tipo_cliente_id = pr.tipo_cliente_id
                              WHERE p.sku = ? AND c.id = ?";
                
                $stmt_preco = $conn->prepare($sql_preco);
                $stmt_preco->bind_param("si", $sku, $cliente_id);
                $stmt_preco->execute();
                $result_preco = $stmt_preco->get_result();
                
                if ($result_preco->num_rows === 0) { 
                    throw new Exception("Preço do produto SKU: $sku não encontrado para este cliente. Item não adicionado."); 
                }
                
                $data_preco = $result_preco->fetch_assoc();
                $preco_unitario = floatval($data_preco['preco']);
                $produto_id = intval($data_preco['produto_id']);
                $stmt_preco->close();

                // Armazena o item para processamento posterior (baixa de lote)
                $itens_vendidos_para_processar[] = [
                    'produto_id' => $produto_id, 
                    'quantidade_vendida' => $quantidade,
                    'preco_unitario' => $preco_unitario
                ];
                $item_count++;
            }
        }
        
        if ($item_count === 0) { 
            throw new Exception("Nenhum item válido foi adicionado à venda."); 
        }

        // --- 4. DÉBITO DE ESTOQUE POR LOTE (FIFO) E INSERÇÃO DE ITENS COMPLETOS ---
        
        // Prepara statements fora do loop para eficiência
        // UPDATE - Baixa o saldo do lote
        $sql_update_lote = "UPDATE lotes_estoque SET saldo_atual = saldo_atual - ? WHERE id = ?";
        $stmt_update_lote = $conn->prepare($sql_update_lote);
        if (!$stmt_update_lote) throw new Exception("Erro de preparação (Update Lote): " . $conn->error);

        // INSERT - Registra a saída no histórico (Tipo: iidsis)
        $sql_movimentacao = "INSERT INTO movimentacao_estoque 
                            (lote_id, produto_id, tipo, quantidade, data_movimentacao, usuario_id, observacao) 
                            VALUES (?, ?, 'SAIDA', ?, NOW(), ?, CONCAT('Venda ID: ', ?))";
        $stmt_movimentacao = $conn->prepare($sql_movimentacao);
        if (!$stmt_movimentacao) throw new Exception("Erro de preparação (Movimentação): " . $conn->error);
        
        // INSERT - Insere o Item de Venda com rastreabilidade completa
        $sql_item_completo = "INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, num_lote, status_lote, data_vencimento) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_item_completo = $conn->prepare($sql_item_completo);
        if (!$stmt_item_completo) throw new Exception("Erro de preparação (Item Venda Completo): " . $conn->error);


        foreach ($itens_vendidos_para_processar as $item) {
            $produto_id = $item['produto_id'];
            $quantidade_restante = $item['quantidade_vendida']; // Quanto ainda precisamos baixar
            $preco_unitario = $item['preco_unitario'];

            // 4.1. Busca Lotes Disponíveis (FIFO: menor data de vencimento primeiro)
            $sql_lotes = "SELECT id, saldo_atual, num_lote, status_lote, data_vencimento 
                          FROM lotes_estoque 
                          WHERE produto_id = ? AND saldo_atual > 0 AND status_lote = 'Liberado Todos'
                          ORDER BY data_vencimento ASC, data_entrada ASC";
            $stmt_lotes = $conn->prepare($sql_lotes);
            $stmt_lotes->bind_param("i", $produto_id);
            $stmt_lotes->execute();
            $result_lotes = $stmt_lotes->get_result();

            if ($result_lotes->num_rows === 0) {
                // Se não há lotes, o estoque está zerado ou bloqueado
                throw new Exception("Estoque insuficiente para o Produto ID: " . $produto_id . ". (Nenhum lote disponível)");
            }
            
            $lotes_disponiveis = $result_lotes->fetch_all(MYSQLI_ASSOC);
            $stmt_lotes->close();

            // 4.2. Itera sobre os lotes para fazer a baixa (Consumo FIFO)
            foreach ($lotes_disponiveis as $lote) {
                if ($quantidade_restante <= 0) break; // Venda já foi totalmente atendida
                
                $lote_id = $lote['id'];
                $saldo_lote = floatval($lote['saldo_atual']);
                
                // Define a quantidade a baixar (o mínimo entre o que resta vender e o saldo do lote)
                $quantidade_a_baixar = min($quantidade_restante, $saldo_lote);
                
                if ($quantidade_a_baixar <= 0) continue; 

                // A. UPDATE: Baixa o saldo no lote
                // bind_param: d (quantidade_a_baixar), i (lote_id)
                $stmt_update_lote->bind_param("di", $quantidade_a_baixar, $lote_id);
                if (!$stmt_update_lote->execute()) {
                    throw new Exception("Falha no UPDATE do lote {$lote_id}: " . $stmt_update_lote->error);
                }
                
                // B. INSERT: Registra o movimento de SAÍDA no Histórico
                // bind_param (iidsis): i (lote_id), i (produto_id), d (quantidade), i (usuario_id), s (observacao/venda_id)
                $tipos_historico = "iidis"; 
                $stmt_movimentacao->bind_param($tipos_historico, 
                    $lote_id, 
                    $produto_id, 
                    $quantidade_a_baixar, 
                    $usuario_id, 
                    $venda_id
                );

                if (!$stmt_movimentacao->execute()) {
                    throw new Exception("Falha ao registrar histórico de saída: " . $stmt_movimentacao->error);
                }
                
                // C. INSERE o item na tabela itens_venda (com rastreabilidade)
                // bind_param: i (venda_id), i (produto_id), d (quantidade), d (preco), s (num_lote), s (status_lote), s (data_vencimento)
                $tipos_item_venda = "iiddss"; 
                $stmt_item_completo->bind_param($tipos_item_venda, 
                    $venda_id, 
                    $produto_id, 
                    $quantidade_a_baixar, 
                    $preco_unitario, 
                    $lote['num_lote'], 
                    $lote['status_lote'], 
                    $lote['data_vencimento']
                );

                if (!$stmt_item_completo->execute()) {
                    throw new Exception("Falha ao inserir item de venda com lote: " . $stmt_item_completo->error);
                }
                

                $quantidade_restante -= $quantidade_a_baixar;
            } // Fim do foreach Lotes

            // 4.3. Checa se toda a quantidade vendida foi atendida
            if (round($quantidade_restante, 4) > 0) {
                // Se sobrou quantidade, o estoque era insuficiente.
                throw new Exception("Estoque insuficiente. Faltaram " . round($quantidade_restante, 4) . " para o Produto ID: " . $produto_id . ". Venda cancelada.");
            }
        } // Fim do foreach Itens da Venda

        // Fecha os statements preparados no Bloco 4
        $stmt_update_lote->close();
        $stmt_movimentacao->close();
        $stmt_item_completo->close();

        // 5. Sucesso: Commita a transação
        $conn->commit();
        header("Location: lancamento_venda.php?status=success&msg=" . urlencode("Venda #$venda_id registrada com sucesso e estoque baixado!"));
        
    } catch (Exception $e) {
        // 6. Falha: Rollback e mensagem de erro útil
        $conn->rollback();
        
        $mensagem_erro = $e->getMessage();

        // Tenta fechar statements abertos em caso de erro
        if (isset($stmt_venda) && $stmt_venda->error) $stmt_venda->close();
        if (isset($stmt_preco) && $stmt_preco->error) $stmt_preco->close();
        if (isset($stmt_lotes) && $stmt_lotes->error) $stmt_lotes->close();
        if (isset($stmt_update_lote) && $stmt_update_lote->error) $stmt_update_lote->close();
        if (isset($stmt_movimentacao) && $stmt_movimentacao->error) $stmt_movimentacao->close();
        if (isset($stmt_item_completo) && $stmt_item_completo->error) $stmt_item_completo->close();
        
        // Redireciona de volta para a tela de lançamento de venda
        header("Location: lancamento_venda.php?status=error&msg=" . urlencode("ERRO NA TRANSAÇÃO: " . $mensagem_erro));
        
    } finally {
        $conn->close();
    }
} else {
    // Redireciona se não for um POST
    header("Location: lancamento_venda.php");
}
?>