<?php
// cadastro_recompensa.php - CRUD de Recompensas para o Clube Nata (Ação do Gestor)
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E GESTOR
if ($usuario_logado['privilegio'] !== 'Admin' && $usuario_logado['privilegio'] !== 'Gestor') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$feedback = '';
$recompensa_editar = null;

// --- Lógica de Processamento: Cadastro, Edição e Exclusão ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $custo_pontos = intval($_POST['custo_pontos'] ?? 0);
    $status = $_POST['status'] ?? 'Ativo';
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($action === 'salvar') {
            if (empty($nome) || $custo_pontos <= 0) {
                throw new Exception("Nome e Custo em Pontos válidos são obrigatórios.");
            }

            if ($id > 0) {
                // UPDATE (Edição)
                $sql = "UPDATE clube_nata_recompensas SET nome=?, descricao=?, custo_pontos=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisi", $nome, $descricao, $custo_pontos, $status, $id);
                $stmt->execute();
                // Redireciona com feedback
                header("Location: cadastro_recompensa.php?status=success&msg=" . urlencode("Recompensa atualizada com sucesso!"));
                exit();
            } else {
                // INSERT (Cadastro)
                $sql = "INSERT INTO clube_nata_recompensas (nome, descricao, custo_pontos, status) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", $nome, $descricao, $custo_pontos, $status);
                $stmt->execute();
                // Redireciona com feedback
                header("Location: cadastro_recompensa.php?status=success&msg=" . urlencode("Recompensa cadastrada com sucesso!"));
                exit();
            }
            $stmt->close();
        } elseif ($action === 'deletar' && $id > 0) {
            // DELETE (Exclusão)
            $sql = "DELETE FROM clube_nata_recompensas WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            // Redireciona com feedback
            header("Location: cadastro_recompensa.php?status=success&msg=" . urlencode("Recompensa excluída com sucesso!"));
            exit();
        }
    } catch (Exception $e) {
        $feedback = '<p class="feedback-message status-error">Erro ao processar a recompensa: ' . $e->getMessage() . '</p>';
    }
}

// --- Lógica para Carregar Recompensa para Edição ---
if (isset($_GET['edit'])) {
    $id_editar = intval($_GET['edit']);
    $sql = "SELECT * FROM clube_nata_recompensas WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $result = $stmt->get_result();
    $recompensa_editar = $result->fetch_assoc();
    $stmt->close();
}

// --- Carrega Lista de Recompensas ---
$recompensas_lista = [];
$sql_lista = "SELECT id, nome, custo_pontos, status FROM clube_nata_recompensas ORDER BY custo_pontos ASC";
$result_lista = $conn->query($sql_lista);
while ($row = $result_lista->fetch_assoc()) {
    $recompensas_lista[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Recompensas | Clube Nata</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Estilos Específicos (MANTIDOS) */
        .custo-pontos { 
            font-weight: bold; 
            color: var(--primary-color); 
        }
        .form-recompensa-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-sm);
        }
        .form-recompensa-grid .full-width { 
            grid-column: 1 / 3; 
        }
        .form-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .form-card h2 {
            margin-top: 0;
            color: var(--secondary-color);
            border-bottom: 2px solid var(--border-color);
            padding-bottom: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .delete-form { 
            display: inline-block; 
            margin-left: 5px; 
        }
    </style>
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
                <h1><i class="fas fa-money-bill-alt"></i> Gerenciar Recompensas do Clube Nata</h1>
                <p>Defina e edite as recompensas que os membros podem resgatar com seus pontos.</p>
                
                <?= $feedback ?> <div class="form-card">
                    <h2><i class="fas fa-plus-circle"></i> <?= $recompensa_editar ? 'Editar Recompensa ID: ' . $recompensa_editar['id'] : 'Nova Recompensa' ?></h2>
                    <form method="POST" action="cadastro_recompensa.php" class="form-recompensa-grid">
                        <input type="hidden" name="action" value="salvar">
                        <input type="hidden" name="id" value="<?= $recompensa_editar['id'] ?? 0 ?>">
                        
                        <div class="form-group">
                            <label for="nome">Nome da Recompensa:</label>
                            <input type="text" id="nome" name="nome" class="form-control" 
                                   value="<?= htmlspecialchars($recompensa_editar['nome'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="custo_pontos">Custo em Pontos (Inteiro):</label>
                            <input type="number" id="custo_pontos" name="custo_pontos" class="form-control" 
                                   value="<?= htmlspecialchars($recompensa_editar['custo_pontos'] ?? '') ?>" required min="1">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="descricao">Descrição (Detalhes da Recompensa):</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="3"><?= htmlspecialchars($recompensa_editar['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="Ativo" <?= ($recompensa_editar['status'] ?? 'Ativo') == 'Ativo' ? 'selected' : '' ?>>Ativo (Disponível para Resgate)</option>
                                <option value="Inativo" <?= ($recompensa_editar['status'] ?? '') == 'Inativo' ? 'selected' : '' ?>>Inativo (Oculto no Resgate)</option>
                            </select>
                        </div>

                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary full-width">
                                <i class="fas fa-save"></i> <?= $recompensa_editar ? 'Salvar Edição' : 'Cadastrar Recompensa' ?>
                            </button>
                            <?php if ($recompensa_editar): ?>
                                <a href="cadastro_recompensa.php" class="btn btn-secondary full-width" style="margin-top: 10px;">
                                    <i class="fas fa-times-circle"></i> Cancelar Edição
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <h2><i class="fas fa-list"></i> Catálogo de Recompensas</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Custo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recompensas_lista)): ?>
                                <tr><td colspan="5" style="text-align: center;">Nenhuma recompensa cadastrada.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recompensas_lista as $recompensa): ?>
                                    <tr>
                                        <td><?= $recompensa['id'] ?></td>
                                        <td><?= htmlspecialchars($recompensa['nome']) ?></td>
                                        <td class="custo-pontos"><?= number_format($recompensa['custo_pontos'], 0, ',', '.') ?> pts</td>
                                        <td><?= $recompensa['status'] ?></td>
                                        <td>
                                            <a href="cadastro_recompensa.php?edit=<?= $recompensa['id'] ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <button 
                                                type="button" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="confirmarExclusao(<?= $recompensa['id'] ?>, '<?= htmlspecialchars($recompensa['nome']) ?>')"
                                            >
                                                <i class="fas fa-trash-alt"></i> Excluir
                                            </button>
                                            
                                            <form id="delete-form-<?= $recompensa['id'] ?>" method="POST" action="cadastro_recompensa.php" class="delete-form" style="display: none;">
                                                <input type="hidden" name="action" value="deletar">
                                                <input type="hidden" name="id" value="<?= $recompensa['id'] ?>">
                                            </form>
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
    <footer class="footer">2025 &copy; Comunicação & Marketing | Grupo AMB</footer>

    <script>
        // Função para exibir o SweetAlert de confirmação de exclusão
        function confirmarExclusao(id, nome) {
            Swal.fire({
                title: 'Tem certeza?',
                html: "Você irá **excluir permanentemente** a recompensa: **" + nome + "**.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#388e3c',
                confirmButtonText: 'Sim, Excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Se confirmado, submete o formulário oculto
                    document.getElementById('delete-form-' + id).submit();
                }
            })
        }

        // Função para mostrar o SweetAlert de sucesso após o redirecionamento
        <?php if (isset($_GET['status']) && $_GET['status'] === 'success' && isset($_GET['msg'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: decodeURIComponent("<?= urlencode($_GET['msg']) ?>"),
                timer: 3000,
                showConfirmButton: false
            });
            // Limpa os parâmetros GET para que o alerta não apareça em um recarregamento manual
            window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>
    </script>
</body>
</html>