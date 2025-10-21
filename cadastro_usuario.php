<?php
// cadastro_usuario.php - Interface para cadastrar novos usuários no sistema.
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// AGORA RESTRINGIDO APENAS AO ADMIN
if ($usuario_logado['privilegio'] !== 'Admin') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}
// ... (resto do código)

$feedback = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $privilegio = trim($_POST['privilegio'] ?? '');
    
    // Define quais privilégios podem ser criados.
    // Incluímos 'Admin' aqui.
    $privilegios_validos = ['Admin', 'Gestor', 'Industria', 'Vendedor'];

    // 1. Validação
    if (empty($nome) || empty($email) || empty($senha) || !in_array($privilegio, $privilegios_validos)) {
        $feedback = '<p class="feedback-message status-error">Por favor, preencha todos os campos corretamente e escolha um privilégio válido.</p>';
    } elseif (strlen($senha) < 6) {
        $feedback = '<p class="feedback-message status-error">A senha deve ter no mínimo 6 caracteres.</p>';
    } else {
        // Lógica de Segurança Adicional (opcional, mas recomendada): 
        // Apenas Admin pode criar outro Admin. Como a tela é de Gestor, permitiremos que ele crie Admin, mas você pode mudar isso.
        
        // 2. Criação do Hash de Senha (Segurança!)
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $conn->begin_transaction();

        try {
            // 3. Verifica se o e-mail já existe
            $sql_check = "SELECT id FROM usuarios WHERE email = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                $feedback = '<p class="feedback-message status-error">Erro: Já existe um usuário cadastrado com este e-mail.</p>';
                $stmt_check->close();
                throw new Exception("Email duplicado.");
            }
            $stmt_check->close();
            
            // 4. Insere o novo usuário
            $sql_insert = "INSERT INTO usuarios (nome, email, senha_hash, privilegio) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $nome, $email, $senha_hash, $privilegio);
            
            if (!$stmt_insert->execute()) {
                 throw new Exception("Falha ao salvar no banco de dados: " . $stmt_insert->error);
            }
            
            $conn->commit();
            $feedback = '<p class="feedback-message status-success">Usuário **' . htmlspecialchars($nome) . '** cadastrado com sucesso como **' . $privilegio . '**!</p>';

            // Limpa o formulário após sucesso
            unset($nome, $email, $privilegio);

        } catch (Exception $e) {
            $conn->rollback();
            if (strpos($e->getMessage(), "Email duplicado") === false) {
                 $feedback = '<p class="feedback-message status-error">Erro na transação: ' . $e->getMessage() . '</p>';
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuários | Gestor</title>
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
                <h1><i class="fas fa-user-friends"></i> Cadastro de Novo Usuário</h1>
                <p>Crie novas contas para Administradores, Gestores ou Vendedores.</p>
                
                <?= $feedback ?>

                <form method="POST" action="cadastro_usuario.php">
                    <div class="form-grid">
                        
                        <div class="form-group full-width">
                            <label for="nome">Nome Completo:</label>
                            <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail (Login):</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="senha">Senha:</label>
                            <input type="password" id="senha" name="senha" class="form-control" required placeholder="Mínimo 6 caracteres">
                        </div>

                        <div class="form-group full-width">
                            <label for="privilegio">Privilégio no Sistema:</label>
                            <select id="privilegio" name="privilegio" class="form-control" required>
                                <option value="">Selecione o Nível</option>
                                <option value="Admin" <?= (($_POST['privilegio'] ?? '') === 'Admin') ? 'selected' : '' ?>>Admin (Acesso Total)</option>
                                <option value="Gestor" <?= (($_POST['privilegio'] ?? '') === 'Gestor') ? 'selected' : '' ?>>Gestor</option>
                                <option value="Industria" <?= (($_POST['privilegio'] ?? '') === 'Industria') ? 'selected' : '' ?>>Indústria</option>
                                
                                <option value="Vendedor" <?= (($_POST['privilegio'] ?? '') === 'Vendedor') ? 'selected' : '' ?>>Vendedor</option>
                            </select>
                        </div>

                    </div>
                    
                    <div style="padding: 0 20px 20px 20px;">
                        <button type="submit" class="btn btn-primary full-width"><i class="fas fa-user-plus"></i> Cadastrar Usuário</button>
                    </div>
                </form>
                
                <h2 style="margin-top: 30px; font-size: 1.5rem;"><i class="fas fa-shield-alt"></i> Regras de Acesso</h2>
                <ul style="padding-left: 20px;">
                    <li>Admin: Superusuário (futuras configurações avançadas).</li>
                    <li>Gestor: Painel de Pedidos e Relatórios).</li>
                    <li>Vendedor: Acesso limitado (Lançar Vendas, Cadastrar Clientes e Painel de Pedidos).</li>
                </ul>

            </div>
        </div>
    </div>
</body>
</html>