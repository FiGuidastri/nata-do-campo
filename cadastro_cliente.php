<?php
// cadastro_cliente.php - Tela de formulário de cadastro de clientes
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// Restringe acesso a Vendedor e Admin.
if ($usuario_logado['privilegio'] !== 'Vendedor' && $usuario_logado['privilegio'] !== 'Admin') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$feedback = ''; // Usado para feedback de erro PHP/DB que não puderem ser redirecionados

// --- Lógica de Processamento: Cadastro de Cliente ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Coleta e Sanitização dos dados
    $tipo_cliente_id = intval($_POST['tipo_cliente_id'] ?? 0);
    $codigo_cliente = trim($_POST['codigo_cliente'] ?? '');
    $nome_cliente = trim($_POST['nome_cliente'] ?? '');
    // Limpar máscara de CNPJ/CPF
    $cnpj = trim(str_replace(['.', '-', '/', ' '], '', $_POST['cnpj'] ?? '')); 
    $telefone_contato = trim($_POST['telefone_contato'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $endereco_entrega = trim($_POST['endereco_entrega'] ?? '');

    $conn->begin_transaction();

    try {
        if (empty($nome_cliente) || $tipo_cliente_id <= 0 || empty($cnpj)) {
            throw new Exception("Nome, Tipo de Cliente e CNPJ/CPF são obrigatórios.");
        }
        
        // 2. Query de Inserção (Corrigida e Completa)
        $sql = "INSERT INTO clientes (tipo_cliente_id, codigo_cliente, nome_cliente, cnpj, telefone_contato, email, cidade, estado, endereco_entrega, data_cadastro) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters: i: integer, s: string
        $stmt->bind_param("issssssss", 
            $tipo_cliente_id, 
            $codigo_cliente, 
            $nome_cliente, 
            $cnpj, 
            $telefone_contato, 
            $email, 
            $cidade, 
            $estado, 
            $endereco_entrega
        );
        
        if (!$stmt->execute()) {
            // Verifica erro de duplicidade de CNPJ (se houver UNIQUE constraint no DB)
            if ($conn->errno === 1062) {
                throw new Exception("O CNPJ/CPF informado já está cadastrado.");
            }
            throw new Exception("Erro ao executar a inserção: " . $stmt->error);
        }

        $stmt->close();
        $conn->commit();
        
        // PRG: Redirecionamento com SweetAlert
        $msg_sucesso = "Cliente **" . htmlspecialchars($nome_cliente) . "** cadastrado com sucesso!";
        header("Location: cadastro_cliente.php?status=success&msg=" . urlencode($msg_sucesso));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Feedback de erro para exibição
        $feedback = '<p class="feedback-message status-error">Erro ao cadastrar cliente: ' . $e->getMessage() . '</p>';
    }
}

// Carrega Tipos de Cliente (do banco de dados)
$tipos_cliente = [];
try {
    $result_tipos = $conn->query("SELECT id, nome FROM tipos_cliente ORDER BY nome ASC");
    while ($row = $result_tipos->fetch_assoc()) {
        $tipos_cliente[] = $row;
    }
} catch (Exception $e) {
    // Adiciona feedback se a lista de tipos falhar
    if (empty($feedback)) {
        $feedback = '<p class="feedback-message status-error">Erro ao carregar tipos de cliente: ' . $e->getMessage() . '</p>';
    }
}
// Fecha a conexão após carregar os dados
$conn->close(); 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Clientes | Nata do Campo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* CSS REFINADO: A classe .form-grid já existe em style.css */
        /* Mantendo o full-width para o botão funcionar corretamente na linha */
        .submit-group {
            grid-column: 1 / -1; /* Ocupa a largura total da grid */
            margin-top: var(--spacing-sm);
        }
    </style>
</head>
<body>
    <?php include 'top-header.php'; ?> 

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                <h1><i class="fas fa-user-plus"></i> Cadastro de Cliente</h1>
                <p>Preencha todos os campos para registrar um novo cliente.</p>
                
                <?= $feedback ?> 
                <form action="cadastro_cliente.php" method="POST"> 
                    <fieldset>
                        <legend>Dados Principais</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="tipo_cliente_id">Tipo de Cliente:</label>
                                <select class="form-control" id="tipo_cliente_id" name="tipo_cliente_id" required>
                                    <option value="">Selecione o Tipo</option>
                                    <?php foreach ($tipos_cliente as $tipo): ?>
                                        <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="codigo_cliente">Código Cliente (ERP):</label>
                                <input type="text" class="form-control" id="codigo_cliente" name="codigo_cliente" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="nome_cliente">Nome / Razão Social:</label>
                                <input type="text" class="form-control" id="nome_cliente" name="nome_cliente" required>
                            </div>

                            <div class="form-group">
                                <label for="cnpj">CNPJ / CPF:</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="Apenas números" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefone_contato">Telefone:</label>
                                <input type="text" class="form-control" id="telefone_contato" name="telefone_contato" placeholder="(XX) XXXXX-XXXX">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="email">E-mail (Opcional):</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Endereço</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cidade">Cidade:</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="estado">Estado (UF):</label>
                                <input type="text" class="form-control" id="estado" name="estado" maxlength="2" required>
                            </div>

                            <div class="form-group full-width">
                                <label for="endereco_entrega">Endereço Completo para Entrega:</label>
                                <input type="text" class="form-control" id="endereco_entrega" name="endereco_entrega" required placeholder="Rua, Número, Bairro, CEP">
                            </div>
                        </div>
                    </fieldset>
                    
                    <div class="submit-group">
                        <button type="submit" class="btn btn-primary full-width"><i class="fas fa-save"></i> Cadastrar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Função para mostrar o SweetAlert de sucesso
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('status') && urlParams.get('status') === 'success' && urlParams.has('msg')) {
                const msg = urlParams.get('msg');
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    // Permite HTML para exibir texto em negrito (markdown **texto**)
                    html: decodeURIComponent(msg), 
                    timer: 5000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                // Limpa os parâmetros GET para evitar re-exibição do alerta
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
    <script>
    // CRÍTICO: Controla a abertura/fechamento da sidebar no mobile
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.body.classList.toggle('menu-open');
    });
    </script>
</body>
</html>