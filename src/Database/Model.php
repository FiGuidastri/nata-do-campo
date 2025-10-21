<?php
/**
 * Model.php - Classe base para modelos do banco de dados
 */

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $casts = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Busca um registro pelo ID
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    // Busca todos os registros
    public function all() {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->fetchAll($sql);
    }
    
    // Busca registros com filtros
    public function where(array $conditions = [], array $params = []) {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Cria um novo registro
    public function create(array $data) {
        $data = $this->filterFillable($data);
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ($placeholders)";
        
        return $this->db->insert($sql, $values);
    }
    
    // Atualiza um registro
    public function update($id, array $data) {
        $data = $this->filterFillable($data);
        $fields = array_keys($data);
        $values = array_values($data);
        
        $sets = array_map(function($field) {
            return "$field = ?";
        }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(',', $sets) . " WHERE {$this->primaryKey} = ?";
        $values[] = $id;
        
        return $this->db->update($sql, $values);
    }
    
    // Deleta um registro
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->executeQuery($sql, [$id]);
    }
    
    // Filtra apenas os campos preenchíveis
    protected function filterFillable(array $data): array {
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    // Remove campos escondidos
    protected function removeHidden(array $data): array {
        return array_diff_key($data, array_flip($this->hidden));
    }
    
    // Converte tipos de dados
    protected function castValues(array $data): array {
        foreach ($this->casts as $field => $type) {
            if (isset($data[$field])) {
                switch ($type) {
                    case 'int':
                        $data[$field] = (int)$data[$field];
                        break;
                    case 'float':
                        $data[$field] = (float)$data[$field];
                        break;
                    case 'bool':
                        $data[$field] = (bool)$data[$field];
                        break;
                    case 'datetime':
                        $data[$field] = new DateTime($data[$field]);
                        break;
                }
            }
        }
        return $data;
    }
    
    // Prepara dados para JSON
    public function toArray(array $data): array {
        $data = $this->removeHidden($data);
        $data = $this->castValues($data);
        return $data;
    }
    
    // Inicia uma transação
    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }
    
    // Confirma uma transação
    public function commit(): bool {
        return $this->db->commit();
    }
    
    // Reverte uma transação
    public function rollback(): bool {
        return $this->db->rollback();
    }
}