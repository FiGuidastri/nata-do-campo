<?php

class Cliente {
    private $id;
    public $tipo_pessoa;
    public $tipo_cliente_id;
    public $nome;
    public $cpf;
    public $cnpj;
    public $email;
    public $telefone;
    public $cep;
    public $logradouro;
    public $numero;
    public $complemento;
    public $bairro;
    public $cidade;
    public $estado;
    public $observacoes;
    public $ativo;
    public $data_cadastro;
    public $data_atualizacao;

    public function __construct() {
        $this->ativo = true;
        $this->data_cadastro = date('Y-m-d H:i:s');
        $this->data_atualizacao = $this->data_cadastro;
    }

    public function save() {
        $db = Database::getInstance();

        if (isset($this->id)) {
            // Update
            $sql = "UPDATE clientes SET
                    tipo_pessoa = ?,
                    tipo_cliente_id = ?,
                    nome = ?,
                    cpf = ?,
                    cnpj = ?,
                    email = ?,
                    telefone = ?,
                    cep = ?,
                    logradouro = ?,
                    numero = ?,
                    complemento = ?,
                    bairro = ?,
                    cidade = ?,
                    estado = ?,
                    observacoes = ?,
                    ativo = ?,
                    data_atualizacao = NOW()
                WHERE id = ?";

            $stmt = $db->prepare($sql);
            $stmt->bind_param("sisssssssssssssii",
                $this->tipo_pessoa,
                $this->tipo_cliente_id,
                $this->nome,
                $this->cpf,
                $this->cnpj,
                $this->email,
                $this->telefone,
                $this->cep,
                $this->logradouro,
                $this->numero,
                $this->complemento,
                $this->bairro,
                $this->cidade,
                $this->estado,
                $this->observacoes,
                $this->ativo,
                $this->id
            );

        } else {
            // Insert
            $sql = "INSERT INTO clientes (
                    tipo_pessoa,
                    tipo_cliente_id,
                    nome,
                    cpf,
                    cnpj,
                    email,
                    telefone,
                    cep,
                    logradouro,
                    numero,
                    complemento,
                    bairro,
                    cidade,
                    estado,
                    observacoes,
                    ativo,
                    data_cadastro,
                    data_atualizacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $db->prepare($sql);
            $stmt->bind_param("sissssssssssssssi",
                $this->tipo_pessoa,
                $this->tipo_cliente_id,
                $this->nome,
                $this->cpf,
                $this->cnpj,
                $this->email,
                $this->telefone,
                $this->cep,
                $this->logradouro,
                $this->numero,
                $this->complemento,
                $this->bairro,
                $this->cidade,
                $this->estado,
                $this->observacoes,
                $this->ativo
            );
        }

        $result = $stmt->execute();
        if ($result && !isset($this->id)) {
            $this->id = $db->insert_id;
        }

        return $result;
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_object(self::class)) {
            return $row;
        }
        
        return null;
    }

    public static function findAll($where = "", $params = []) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM clientes";
        
        if ($where) {
            $sql .= " WHERE " . $where;
        }
        
        $stmt = $db->prepare($sql);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params)); // assume all strings by default
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $clientes = [];
        while ($row = $result->fetch_object(self::class)) {
            $clientes[] = $row;
        }
        
        return $clientes;
    }

    public function getId() {
        return $this->id;
    }
}