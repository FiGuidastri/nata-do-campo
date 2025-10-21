<?php
// visao_usuarios.php - Painel de controle e listagem de usuários.
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN
if ($usuario_logado['privilegio'] !== 'Admin') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$feedback = '';
$usuarios = [];

// 1. Lógica de Ação (Edição de Privilégio ou Deleção)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $target_id = intval($_POST['user_id']);

    if ($target_id === $usuario_logado['id']) {
        $feedback = '<p class="feedback-message status-error">Erro: Você não pode modificar ou desativar sua própria conta.</p>';
    } else {
        try {
            if ($_POST['action'] == 'update_privilege' && isset($_POST['new_privilegio'])) {
                $new_privilegio = trim($_POST['new_privilegio']);
                
                // Validação de privilégio (já que a coluna é ENUM)
                if (!in_array($new_privilegio, ['Admin', 'Gestor', 'Vendedor'])) {
                    throw new Exception("Privilégio inválido.");
                }

                $sql = "UPDATE usuarios SET privilegio = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_privilegio, $target_id);
                $stmt->execute();
                $stmt->close();
                $feedback = '<p class="feedback-message status-success">Privilégio do Usuário ID **' . $target_id . '** alterado para **' . $new_privilegio . '**.</p>';
                
            } elseif ($_POST['action'] == 'delete') {
                $sql = "DELETE FROM usuarios WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $target_id);
                $stmt->execute();
                $stmt->close();
                $feedback = '<p class="feedback-message status-success">Usuário ID **' . $target_id . '** excluído com sucesso.</p>';
            }
        } catch (Exception $e) {
            $feedback = '<p class="feedback-message status-error">Erro na operação: ' . $e->getMessage() . '</p>';
        }
    }
}

// 2. Carrega todos os usuários
$sql_select = "SELECT id, nome, email, privilegio FROM usuarios ORDER BY privilegio, nome ASC";
$result_select = $conn->query($sql_select);
while ($row = $result_select->fetch_assoc()) {
    $usuarios[] = $row;
}
$conn->close();

$privilegios_opcoes = ['Admin', 'Gestor', 'Vendedor'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários | Admin</title>
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
                <h1><i class="fas fa-users-cog"></i> Gerenciamento de Usuários</h1>
                <p>Visão geral e controle de todos os logins do sistema.</p>
                
                <?= $feedback ?>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail (Login)</th>
                                <th>Privilégio Atual</th>
                                <th style="width: 250px;">Ações Administrativas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr><td colspan="5" style="text-align: center;">Nenhum usuário encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['nome']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><span class="status-badge status-<?= $user['privilegio'] ?>"><?= $user['privilegio'] ?></span></td>
                                        <td>
                                            <form method="POST" style="display: inline-flex; gap: 5px;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="action" value="update_privilege">
                                                
                                                <select name="new_privilegio" class="form-control" style="width: 130px;">
                                                    <?php foreach($privilegios_opcoes as $p): ?>
                                                        <option value="<?= $p ?>" <?= ($user['privilegio'] === $p) ? 'selected' : '' ?>>
                                                            <?= $p ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                
                                                <button type="submit" class="btn btn-secondary btn-sm" title="Mudar Privilégio" 
                                                    <?= ($user['id'] === $usuario_logado['id']) ? 'disabled' : '' ?>>
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>

                                            <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nome']) ?>')" 
                                                    class="btn btn-danger btn-sm" title="Excluir Usuário"
                                                    <?= ($user['id'] === $usuario_logado['id']) ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <a href="cadastro_usuario.php" class="btn btn-secondary" style="margin-top: 20px;"><i class="fas fa-user-plus"></i> Cadastrar Novo</a>
            </div>
        </div>
    </div>

    <script>
        const LOGGED_USER_ID = <?= $usuario_logado['id'] ?>;

        function confirmDelete(id, nome) {
            if (id === LOGGED_USER_ID) {
                alert("Erro: Você não pode excluir sua própria conta.");
                return;
            }
            if (confirm(`Tem certeza que deseja EXCLUIR o usuário "${nome}" (ID: ${id})? Essa ação é irreversível.`)) {
                // Cria um formulário temporário para enviar o POST de exclusão
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'visao_usuarios.php';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = id;
                form.appendChild(userIdInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>