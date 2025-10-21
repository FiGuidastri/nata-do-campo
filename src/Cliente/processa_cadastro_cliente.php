<?php
require_once '../../config/config.php';
require_once '../../includes/verifica_sessao.php';
require_once '../../src/Database/Database.php';
require_once '../../src/Models/Cliente.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

// Verifica token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('HTTP/1.1 403 Forbidden');
    exit('Token CSRF inválido');
}

try {
    // Inicia a transação
    $db = Database::getInstance();
    $db->beginTransaction();

    // Sanitiza e valida os dados
    $cliente = new Cliente();
    $cliente->tipo_pessoa = filter_input(INPUT_POST, 'tipo_pessoa', FILTER_SANITIZE_STRING);
    $cliente->tipo_cliente_id = filter_input(INPUT_POST, 'tipo_cliente_id', FILTER_VALIDATE_INT);
    $cliente->nome = filter_input(INPUT_POST, 'nome_cliente', FILTER_SANITIZE_STRING);
    $cliente->email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $cliente->telefone = preg_replace('/[^0-9]/', '', filter_input(INPUT_POST, 'telefone_contato', FILTER_SANITIZE_STRING));
    $cliente->cep = preg_replace('/[^0-9]/', '', filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING));
    $cliente->logradouro = filter_input(INPUT_POST, 'logradouro', FILTER_SANITIZE_STRING);
    $cliente->numero = filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_STRING);
    $cliente->complemento = filter_input(INPUT_POST, 'complemento', FILTER_SANITIZE_STRING);
    $cliente->bairro = filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING);
    $cliente->cidade = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING);
    $cliente->estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $cliente->observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);
    $cliente->ativo = filter_input(INPUT_POST, 'ativo', FILTER_VALIDATE_BOOLEAN);
    
    // Define CPF ou CNPJ baseado no tipo de pessoa
    if ($cliente->tipo_pessoa === 'F') {
        $cliente->cpf = preg_replace('/[^0-9]/', '', filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING));
        $cliente->cnpj = null;
    } else {
        $cliente->cnpj = preg_replace('/[^0-9]/', '', filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_STRING));
        $cliente->cpf = null;
    }

    // Validações adicionais
    $errors = [];

    if (!$cliente->tipo_cliente_id) {
        $errors[] = "Tipo de cliente é obrigatório";
    }

    if (!$cliente->nome) {
        $errors[] = "Nome é obrigatório";
    }

    if (!$cliente->email) {
        $errors[] = "E-mail inválido";
    }

    if ($cliente->tipo_pessoa === 'F' && (!$cliente->cpf || strlen($cliente->cpf) !== 11)) {
        $errors[] = "CPF inválido";
    }

    if ($cliente->tipo_pessoa === 'J' && (!$cliente->cnpj || strlen($cliente->cnpj) !== 14)) {
        $errors[] = "CNPJ inválido";
    }

    if (!$cliente->telefone || strlen($cliente->telefone) < 10) {
        $errors[] = "Telefone inválido";
    }

    if (!$cliente->cep || strlen($cliente->cep) !== 8) {
        $errors[] = "CEP inválido";
    }

    // Se houver erros, lança exceção
    if (!empty($errors)) {
        throw new Exception("Erros de validação: " . implode(", ", $errors));
    }

    // Verifica se o CPF/CNPJ já existe
    $documento = $cliente->tipo_pessoa === 'F' ? $cliente->cpf : $cliente->cnpj;
    $stmt = $db->prepare("SELECT id FROM clientes WHERE cpf = ? OR cnpj = ?");
    $stmt->bind_param("ss", $documento, $documento);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("CPF/CNPJ já cadastrado");
    }

    // Salva o cliente
    if (!$cliente->save()) {
        throw new Exception("Erro ao salvar o cliente");
    }

    // Commit da transação
    $db->commit();

    // Redireciona com mensagem de sucesso
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => "Cliente {$cliente->nome} cadastrado com sucesso!"
    ];
    
    header("Location: " . url('/src/Cliente/cadastro_cliente.php'));
    exit();

} catch (Exception $e) {
    // Rollback em caso de erro
    if (isset($db)) {
        $db->rollback();
    }

    // Salva os dados do formulário na sessão para recuperação
    $_SESSION['form_data'] = $_POST;
    
    // Salva a mensagem de erro
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => "Erro ao cadastrar cliente: " . $e->getMessage()
    ];
    
    // Redireciona de volta ao formulário
    header("Location: " . url('/src/Cliente/cadastro_cliente.php'));
    exit();
}