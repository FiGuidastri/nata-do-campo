<?php
require_once '../../config/config.php';
require_once '../../includes/verifica_sessao.php';
require_once '../../src/Database/Database.php';
require_once '../../src/Models/TipoCliente.php';

// Restringe acesso a Vendedor e Admin.
if ($usuario_logado['privilegio'] !== 'Vendedor' && $usuario_logado['privilegio'] !== 'Admin') {
    header("Location: " . url('/public/dashboard/painel_pedidos.php') . "?error=acesso_negado");
    exit();
}

// Busca tipos de cliente ativos
$tiposCliente = TipoCliente::findAll();

// Recupera dados do formulário em caso de erro
$formData = $_SESSION['form_data'] ?? null;
unset($_SESSION['form_data']);

// Recupera mensagem flash se existir
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Gera token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente - <?php echo APP_NAME; ?></title>
    
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="<?php echo url('public/assets/css/base.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('public/assets/css/layout.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('public/assets/css/forms.css'); ?>">

    <!-- Scripts -->
    <script src="<?php echo url('public/assets/js/main.js'); ?>" defer></script>
    <script src="<?php echo url('public/assets/js/forms.js'); ?>" defer></script>
    <script src="<?php echo url('public/assets/js/cliente.js'); ?>" defer></script>
</head>
<body>
    <?php require_once '../../includes/top-header.php'; ?>
    
    <div class="main-container">
        <?php require_once '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <h1>Cadastro de Cliente</h1>
                <nav class="breadcrumb">
                    <ul>
                        <li><a href="<?php echo url('/public/index.php'); ?>">Home</a></li>
                        <li><a href="<?php echo url('/src/Cliente/buscar_cliente.php'); ?>">Clientes</a></li>
                        <li>Cadastro</li>
                    </ul>
                </nav>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible">
                <?php echo $flash['message']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Dados do Cliente</h2>
                </div>
                <div class="card-body">
                    <form id="formCadastroCliente" action="<?php echo url('/src/Cliente/processa_cadastro_cliente.php'); ?>" method="POST" class="form-validate">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="form-group required">
                            <label>Tipo de Pessoa</label>
                            <div class="form-check-group">
                                <label class="form-check">
                                    <input type="radio" name="tipo_pessoa" value="F" class="form-check-input" required
                                        <?php echo ($formData['tipo_pessoa'] ?? 'F') === 'F' ? 'checked' : ''; ?>>
                                    Pessoa Física
                                </label>
                                <label class="form-check">
                                    <input type="radio" name="tipo_pessoa" value="J" class="form-check-input" required
                                        <?php echo ($formData['tipo_pessoa'] ?? '') === 'J' ? 'checked' : ''; ?>>
                                    Pessoa Jurídica
                                </label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4 required">
                                <label for="tipo_cliente_id">Tipo de Cliente</label>
                                <select name="tipo_cliente_id" id="tipo_cliente_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($tiposCliente as $tipo): ?>
                                    <option value="<?php echo $tipo->getId(); ?>"
                                        <?php echo ($formData['tipo_cliente_id'] ?? '') == $tipo->getId() ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo->getNome()); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-8 required">
                                <label for="nome_cliente">Nome/Razão Social</label>
                                <input type="text" id="nome_cliente" name="nome_cliente" class="form-control"
                                    value="<?php echo htmlspecialchars($formData['nome_cliente'] ?? ''); ?>"
                                    maxlength="150" required>
                            </div>

                            <div class="form-group col-md-4 required cpf-group">
                                <label for="cpf">CPF</label>
                                <input type="text" id="cpf" name="cpf" class="form-control mask-cpf"
                                    value="<?php echo htmlspecialchars($formData['cpf'] ?? ''); ?>"
                                    maxlength="14" required>
                            </div>

                            <div class="form-group col-md-4 required cnpj-group" style="display: none;">
                                <label for="cnpj">CNPJ</label>
                                <input type="text" id="cnpj" name="cnpj" class="form-control mask-cnpj"
                                    value="<?php echo htmlspecialchars($formData['cnpj'] ?? ''); ?>"
                                    maxlength="18">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="email">E-mail</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                    maxlength="150">
                            </div>

                            <div class="form-group col-md-4 required">
                                <label for="telefone_contato">Telefone</label>
                                <input type="text" id="telefone_contato" name="telefone_contato" class="form-control mask-phone"
                                    value="<?php echo htmlspecialchars($formData['telefone_contato'] ?? ''); ?>"
                                    maxlength="15" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-3 required">
                                <label for="cep">CEP</label>
                                <div class="input-group">
                                    <input type="text" id="cep" name="cep" class="form-control mask-cep"
                                        value="<?php echo htmlspecialchars($formData['cep'] ?? ''); ?>"
                                        maxlength="9" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="buscarCep">
                                            Buscar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-md-7 required">
                                <label for="logradouro">Logradouro</label>
                                <input type="text" id="logradouro" name="logradouro" class="form-control"
                                    value="<?php echo htmlspecialchars($formData['logradouro'] ?? ''); ?>"
                                    maxlength="150" required>
                            </div>

                            <div class="form-group col-md-2 required">
                                <label for="numero">Número</label>
                                <input type="text" id="numero" name="numero" class="form-control"
                                    value="<?php echo htmlspecialchars($formData['numero'] ?? ''); ?>"
                                    maxlength="10" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="complemento">Complemento</label>
                                <input type="text" id="complemento" name="complemento" class="form-control"
                                    value="<?php echo htmlspecialchars($formData['complemento'] ?? ''); ?>"
                                    maxlength="100">
                            </div>

                            <div class="form-group col-md-4 required">
                                <label for="bairro">Bairro</label>
                                <input type="text" id="bairro" name="bairro" class="form-control"
                                    value="<?php echo htmlspecialchars($formData['bairro'] ?? ''); ?>"
                                    maxlength="100" required>
                            </div>

                            <div class="form-group col-md-3 required">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" class="form-control"
                                    value="<?php echo htmlspecialchars($formData['cidade'] ?? ''); ?>"
                                    maxlength="100" required>
                            </div>

                            <div class="form-group col-md-1 required">
                                <label for="estado">UF</label>
                                <input type="text" id="estado" name="estado" class="form-control text-uppercase"
                                    value="<?php echo htmlspecialchars($formData['estado'] ?? ''); ?>"
                                    maxlength="2" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="observacoes">Observações</label>
                                <textarea id="observacoes" name="observacoes" class="form-control" rows="3"
                                    maxlength="500"><?php echo htmlspecialchars($formData['observacoes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-12">
                                <label class="form-check">
                                    <input type="checkbox" name="ativo" value="1" class="form-check-input"
                                        <?php echo ($formData['ativo'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                    Cliente ativo
                                </label>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                Salvar
                            </button>
                            <a href="<?php echo url('/src/Cliente/buscar_cliente.php'); ?>" class="btn btn-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>