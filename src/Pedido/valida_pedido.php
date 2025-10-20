<?php
// valida_pedido.php - Altera o status de um pedido.
require_once 'verifica_sessao.php'; 
require_once 'conexao.php';

// Verifica se o usuário logado é Gestor. Se não for, encerra.
if ($usuario_logado['privilegio'] !== 'Gestor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas Gestores podem validar pedidos.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $venda_id = intval($_POST['venda_id']);
    $novo_status = $conn->real_escape_string($_POST['status']);

    if (!in_array($novo_status, ['Liberado', 'Rejeitado']) || $venda_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Status ou ID de venda inválido.']);
        $conn->close();
        exit;
    }

    $sql_update = "UPDATE vendas SET status = ?, usuario_validador = ?, data_validacao = NOW() WHERE id = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    // Note: Usamos o ID do usuário logado (Gestor) para auditoria.
    $stmt_update->bind_param("sii", $novo_status, $usuario_logado['id'], $venda_id); 

    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => "Pedido #$venda_id alterado para $novo_status."]);
    } else {
        error_log("Erro ao atualizar pedido: " . $stmt_update->error);
        echo json_encode(['success' => false, 'message' => 'Erro interno ao atualizar o pedido.']);
    }

    $stmt_update->close();
    $conn->close();
}
?>