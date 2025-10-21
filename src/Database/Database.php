<?php
/**
 * Database.php - Classe para operações no banco de dados
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $inTransaction = false;
    
    // Construtor privado para Singleton
    private function __construct() {
        $this->connect();
    }
    
    // Método para obter instância
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Conecta ao banco de dados
    private function connect(): void {
        try {
            $this->connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Falha na conexão: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8");
            
        } catch (Exception $e) {
            logError('Erro ao conectar ao banco de dados: ' . $e->getMessage(), [
                'level' => 'critical'
            ]);
            throw $e;
        }
    }
    
    // Inicia uma transação
    public function beginTransaction(): bool {
        if ($this->inTransaction) {
            return false;
        }
        
        if ($this->connection->begin_transaction()) {
            $this->inTransaction = true;
            return true;
        }
        
        return false;
    }
    
    // Confirma uma transação
    public function commit(): bool {
        if (!$this->inTransaction) {
            return false;
        }
        
        if ($this->connection->commit()) {
            $this->inTransaction = false;
            return true;
        }
        
        return false;
    }
    
    // Reverte uma transação
    public function rollback(): bool {
        if (!$this->inTransaction) {
            return false;
        }
        
        if ($this->connection->rollback()) {
            $this->inTransaction = false;
            return true;
        }
        
        return false;
    }
    
    // Prepara uma consulta SQL
    public function prepare(string $sql): mysqli_stmt {
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Erro ao preparar query: " . $this->connection->error);
        }
        return $stmt;
    }

    // Executa uma consulta SQL direta
    public function query(string $sql) {
        $result = $this->connection->query($sql);
        if ($result === false) {
            throw new Exception("Erro ao executar query: " . $this->connection->error);
        }
        return $result;
    }

    // Prepara e executa uma query
    public function executeQuery(string $sql, array $params = []): ?mysqli_stmt {
        try {
            $stmt = $this->prepare($sql);
            
            if (!empty($params)) {
                $types = '';
                $bindParams = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_double($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $bindParams[] = $param;
                }
                
                array_unshift($bindParams, $types);
                call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar query: " . $stmt->error);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            logError('Erro na execução da query: ' . $e->getMessage(), [
                'level' => 'error',
                'sql' => $sql,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    // Busca uma única linha
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
    
    // Busca múltiplas linhas
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
    
    // Insere dados e retorna o ID
    public function insert(string $sql, array $params = []): int {
        $stmt = $this->executeQuery($sql, $params);
        $id = $this->connection->insert_id;
        $stmt->close();
        return $id;
    }
    
    // Atualiza dados e retorna número de linhas afetadas
    public function update(string $sql, array $params = []): int {
        $stmt = $this->executeQuery($sql, $params);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }
    
    // Helper para referências em bind_param
    private function refValues(array &$arr): array {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    
    // Fecha a conexão
    public function close(): void {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Escapa string
    public function escape(string $string): string {
        return $this->connection->real_escape_string($string);
    }
    
    // Previne clonagem
    private function __clone() {}
    
    // Destrutor
    public function __destruct() {
        $this->close();
    }
}