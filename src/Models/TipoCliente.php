<?php

class TipoCliente {
    private $id;
    private $nome;
    private $descricao;
    private $ativo;
    private $data_cadastro;
    private $data_atualizacao;

    public function __construct() {
        $this->ativo = true;
        $this->data_cadastro = date('Y-m-d H:i:s');
        $this->data_atualizacao = $this->data_cadastro;
    }

    // Getters e Setters
    public function getId() {
        return $this->id;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
        return $this;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
        return $this;
    }

    public function isAtivo() {
        return $this->ativo;
    }

    public function setAtivo($ativo) {
        $this->ativo = (bool)$ativo;
        return $this;
    }

    // Métodos de persistência
    public function save() {
        $db = Database::getInstance();

        if (isset($this->id)) {
            // Update
            $sql = "UPDATE tipos_cliente SET 
                    nome = ?, 
                    descricao = ?, 
                    ativo = ?,
                    data_atualizacao = NOW()
                WHERE id = ?";

            $stmt = $db->prepare($sql);
            $stmt->bind_param("ssii",
                $this->nome,
                $this->descricao,
                $this->ativo,
                $this->id
            );

        } else {
            // Insert
            $sql = "INSERT INTO tipos_cliente (
                    nome, 
                    descricao, 
                    ativo, 
                    data_cadastro, 
                    data_atualizacao
                ) VALUES (?, ?, ?, NOW(), NOW())";

            $stmt = $db->prepare($sql);
            $stmt->bind_param("ssi",
                $this->nome,
                $this->descricao,
                $this->ativo
            );
        }

        $result = $stmt->execute();
        if ($result && !isset($this->id)) {
            $this->id = $stmt->insert_id;
        }

        return $result;
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM tipos_cliente WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_object(self::class)) {
            return $row;
        }
        
        return null;
    }

    public static function findAll($includeInactive = false) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM tipos_cliente";
        
        if (!$includeInactive) {
            $sql .= " WHERE ativo = 1";
        }
        
        $sql .= " ORDER BY nome";
        
        $result = $db->query($sql);
        $tipos = [];
        
        while ($row = $result->fetch_object(self::class)) {
            $tipos[] = $row;
        }
        
        return $tipos;
    }

    public function delete() {
        if (!isset($this->id)) {
            return false;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM tipos_cliente WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function softDelete() {
        if (!isset($this->id)) {
            return false;
        }

        $this->ativo = false;
        $this->data_atualizacao = date('Y-m-d H:i:s');

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE tipos_cliente SET ativo = 0, data_atualizacao = NOW() WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }
}