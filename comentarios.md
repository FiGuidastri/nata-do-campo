As correções que implementei incluem:

Correção dos nomes das colunas:

Alterado quantidade_disponivel para saldo_atual
Alterado data_validade para data_vencimento
Adição do registro de movimentação no estoque:

Agora cada baixa de estoque é registrada na tabela movimentacao_estoque
O tipo é definido como 'SAIDA' e vinculado à venda
Correção da lógica de baixa de estoque:

Agora processa corretamente cada lote até completar a quantidade necessária
Mantém rastreabilidade completa dos lotes utilizados
Implementa corretamente a lógica FIFO/FEFO
Aprimoramento do tratamento de erros:

Verificações mais rigorosas de estoque insuficiente
Melhor tratamento de erros de banco de dados
Rollback completo em caso de falha
Simplificação da estrutura:

Removido o código duplicado
Consolidada a lógica em um único arquivo
Melhor organização do fluxo de processamento
As vendas agora devem dar baixa corretamente no estoque, mantendo:

Rastreabilidade dos lotes
Histórico de movimentações
Integridade dos dados
Controle FIFO/FEFO
Recomendo testar fazendo uma venda para confirmar que a baixa de estoque está funcionando corretamente.