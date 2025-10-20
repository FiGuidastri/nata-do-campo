<?php
// cadastro_produto.php - CRUD de Produtos e Preços Diferenciados
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// AGORA RESTRINGIDO APENAS AO ADMIN
if ($usuario_logado['privilegio'] !== 'Admin') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

// ... (resto do código)

$feedback = '';
$produto_para_editar = null; 
$precos_cliente = []; 

// 1. Carrega todos os Tipos de Cliente
$tipos_cliente = [];
$result_tipos = $conn->query("SELECT id, nome FROM tipos_cliente ORDER BY nome ASC");
while ($row = $result_tipos->fetch_assoc()) {
    $tipos_cliente[] = $row;
}

// Lógica de Processamento (Inserir, Editar, Deletar)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $nome = trim($_POST['nome'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    // NOTE: Preço base removido pois a coluna não existe mais na tabela 'produtos'
    $unidade_medida = trim($_POST['unidade_medida'] ?? '');
    $id = intval($_POST['id'] ?? 0);
    $precos = $_POST['precos'] ?? []; 

    if (empty($nome) || empty($sku) || empty($unidade_medida)) {
         $feedback = '<p class="feedback-message status-error">Nome, SKU e Unidade de Medida são obrigatórios.</p>';
    } else {
        $conn->begin_transaction(); 

        try {
            if ($_POST['action'] == 'inserir' && $id == 0) {
                // CORREÇÃO CRÍTICA: Removendo 'preco' do INSERT na tabela 'produtos'
                $sql = "INSERT INTO produtos (nome, sku, unidade_medida) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                // Tipos: s (nome), s (sku), s (unidade_medida)
                $stmt->bind_param("sss", $nome, $sku, $unidade_medida); 
                $stmt->execute();
                $produto_id = $conn->insert_id;
                $stmt->close();
            } elseif ($_POST['action'] == 'editar' && $id > 0) {
                // CORREÇÃO CRÍTICA: Removendo 'preco' do UPDATE na tabela 'produtos'
                $sql = "UPDATE produtos SET nome = ?, sku = ?, unidade_medida = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                // Tipos: s (nome), s (sku), s (unidade_medida), i (id)
                $stmt->bind_param("sssi", $nome, $sku, $unidade_medida, $id);
                $stmt->execute();
                $produto_id = $id;
                $stmt->close();
            } else {
                throw new Exception("Ação inválida.");
            }

            // ATUALIZA/INSERE PREÇOS DIFERENCIADOS na tabela 'precos' (CORRETO)
            if ($produto_id) {
                $sql_preco = "INSERT INTO precos (produto_id, tipo_cliente_id, preco) VALUES (?, ?, ?)
                              ON DUPLICATE KEY UPDATE preco = VALUES(preco)";
                $stmt_preco = $conn->prepare($sql_preco);
                
                foreach ($precos as $tipo_id => $preco) {
                    $preco_valido = floatval($preco);
                    if ($preco_valido >= 0) { 
                        // Tipos: i (produto_id), i (tipo_cliente_id), d (preco)
                        $stmt_preco->bind_param("iid", $produto_id, $tipo_id, $preco_valido);
                        $stmt_preco->execute();
                    }
                }
                $stmt_preco->close();
            }

            $conn->commit();
            $feedback = '<p class="feedback-message status-success">Produto e Preços atualizados com sucesso!</p>';

        } catch (Exception $e) {
            $conn->rollback();
            // A mensagem de erro original foi mantida para rastreio
            $feedback = '<p class="feedback-message status-error">Erro na transação: ' . $e->getMessage() . '. Verifique se o SKU é único.</p>';
        }
    }
} 

// Processamento de DELEÇÃO (Mantido)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Deve haver um FOREIGN KEY ON DELETE CASCADE na sua tabela 'precos' para excluir os preços
    $sql = "DELETE FROM produtos WHERE id = ?"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $feedback = '<p class="feedback-message status-success">Produto ID **' . $delete_id . '** excluído com sucesso!</p>';
    } else {
        $feedback = '<p class="feedback-message status-error">Erro ao excluir. Verifique se há vendas associadas a este produto.</p>';
    }
}

// Lógica para CARREGAR DADOS DE EDIÇÃO (Mantido)
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $sql = "SELECT * FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $produto_para_editar = $result->fetch_assoc();
        $feedback = '<p class="feedback-message status-info">Editando produto ID **' . $edit_id . '**. Altere os campos e clique em Salvar.</p>';
        
        // CARREGA PREÇOS DIFERENCIADOS EXISTENTES da tabela 'precos'
        $sql_precos = "SELECT tipo_cliente_id, preco FROM precos WHERE produto_id = ?";
        $stmt_precos = $conn->prepare($sql_precos);
        $stmt_precos->bind_param("i", $edit_id);
        $stmt_precos->execute();
        $result_precos = $stmt_precos->get_result();
        while ($row = $result_precos->fetch_assoc()) {
            $precos_cliente[$row['tipo_cliente_id']] = $row['preco'];
        }
    }
}

// --- 3. Carrega a Lista de Produtos (para a Tabela) --- (Mantido)
$produtos = [];
$sql_select = "SELECT * FROM produtos ORDER BY nome ASC";
$result_select = $conn->query($sql_select);
while ($row = $result_select->fetch_assoc()) {
    $produtos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos | Gestor</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="top-header">
        <div class="logo"><img src="logo.png" alt="Logo Nata do Campo"></div>
        <div class="user-info">
            <span class="user-name">Olá, <span><?= htmlspecialchars($usuario_logado['nome']) ?></span> (<?= $usuario_logado['privilegio'] ?>)</span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </header>

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-box-open"></i> <?= $produto_para_editar ? 'Editar Produto' : 'Cadastro de Produtos' ?></h1>
                
                <?= $feedback ?>

                <form method="POST" action="cadastro_produto.php">
                    <input type="hidden" name="id" value="<?= $produto_para_editar['id'] ?? 0 ?>">
                    <input type="hidden" name="action" value="<?= $produto_para_editar ? 'editar' : 'inserir' ?>">
                    
                    <fieldset>
                        <legend>Dados do Produto</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nome">Nome do Produto:</label>
                                <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($produto_para_editar['nome'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="sku">SKU / Código:</label>
                                <input type="text" id="sku" name="sku" class="form-control" value="<?= htmlspecialchars($produto_para_editar['sku'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="unidade_medida">Unidade de Medida (Ex: UN, L, G):</label>
                                <input type="text" id="unidade_medida" name="unidade_medida" class="form-control" value="<?= htmlspecialchars($produto_para_editar['unidade_medida'] ?? '') ?>" required>
                            </div>
                            <input type="hidden" name="preco_base" value="0.00"> 
                        </div>
                    </fieldset>

                    <fieldset style="margin-top: var(--spacing-md);">
                        <legend>Preços de Venda por Tipo de Cliente</legend>
                        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <?php foreach ($tipos_cliente as $tipo): ?>
                                <?php 
                                    $preco_atual = $precos_cliente[$tipo['id']] ?? ''; 
                                    // Se estiver cadastrando novo, garante que o campo esteja vazio
                                    if (!$produto_para_editar) $preco_atual = ''; 
                                ?>
                                <div class="form-group">
                                    <label for="preco_<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?> (R$):</label>
                                    <input type="number" 
                                           id="preco_<?= $tipo['id'] ?>" 
                                           name="precos[<?= $tipo['id'] ?>]" 
                                           class="form-control" 
                                           step="0.01" 
                                           min="0" 
                                           value="<?= htmlspecialchars($preco_atual) ?>" 
                                           placeholder="R$ para <?= $tipo['nome'] ?>">
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($tipos_cliente)): ?>
                                <div class="full-width">
                                    <p class="feedback-message status-error">Nenhum Tipo de Cliente cadastrado. Por favor, cadastre os tipos antes de definir os preços.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <div style="padding: 0 20px 20px 20px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $produto_para_editar ? 'Salvar Edição de Produto/Preços' : 'Cadastrar/Definir Preços' ?>
                        </button>
                        <?php if ($produto_para_editar): ?>
                            <a href="cadastro_produto.php" class="btn btn-secondary"><i class="fas fa-times-circle"></i> Cancelar Edição</a>
                        <?php endif; ?>
                    </div>
                </form>

                <hr style="margin: 30px 0; border-color: #ccc;">

                <h2><i class="fas fa-list"></i> Produtos Cadastrados</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>SKU</th>
                                <th>Unidade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos)): ?>
                                <tr><td colspan="5" style="text-align: center;">Nenhum produto cadastrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($produtos as $produto): ?>
                                    <tr>
                                        <td><?= $produto['id'] ?></td>
                                        <td><?= htmlspecialchars($produto['nome']) ?></td>
                                        <td><?= htmlspecialchars($produto['sku']) ?></td>
                                        <td><?= htmlspecialchars($produto['unidade_medida']) ?></td>
                                        <td>
                                            <a href="cadastro_produto.php?edit_id=<?= $produto['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Editar Preços</a>
                                            <button onclick="confirmDelete(<?= $produto['id'] ?>, '<?= htmlspecialchars($produto['nome']) ?>')" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Excluir</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id, nome) {
            if (confirm(`Tem certeza que deseja excluir o produto "${nome}" (ID: ${id})? Essa ação é irreversível e pode falhar se o produto já foi vendido.`)) {
                window.location.href = `cadastro_produto.php?delete_id=${id}`;
            }
        }
    </script>
</body>
</html>