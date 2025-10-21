<?php
/**
 * Usuario.php - Modelo de Usuário
 */

require_once __DIR__ . '/../Database/Model.php';

class Usuario extends Model {
    protected $table = 'usuarios';
    
    protected $fillable = [
        'nome',
        'email',
        'senha_hash',
        'privilegio',
        'ativo',
        'ultimo_login',
        'tentativas_login',
        'bloqueado_ate'
    ];
    
    protected $hidden = [
        'senha_hash',
        'tentativas_login',
        'bloqueado_ate'
    ];
    
    protected $casts = [
        'ativo' => 'bool',
        'ultimo_login' => 'datetime',
        'bloqueado_ate' => 'datetime'
    ];
    
    // Encontra usuário pelo email
    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->fetchOne($sql, [$email]);
    }
    
    // Registra tentativa de login
    public function incrementLoginAttempts(int $id): void {
        $sql = "
            UPDATE {$this->table} 
            SET tentativas_login = tentativas_login + 1 
            WHERE id = ?
        ";
        $this->db->executeQuery($sql, [$id]);
    }
    
    // Reseta tentativas de login
    public function resetLoginAttempts(int $id): void {
        $sql = "
            UPDATE {$this->table} 
            SET tentativas_login = 0, 
                bloqueado_ate = NULL,
                ultimo_login = NOW()
            WHERE id = ?
        ";
        $this->db->executeQuery($sql, [$id]);
    }
    
    // Bloqueia a conta
    public function blockAccount(int $id, int $minutes = 15): void {
        $sql = "
            UPDATE {$this->table} 
            SET bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE) 
            WHERE id = ?
        ";
        $this->db->executeQuery($sql, [$minutes, $id]);
    }
    
    // Verifica se a conta está bloqueada
    public function isBlocked(int $id): bool {
        $sql = "
            SELECT bloqueado_ate 
            FROM {$this->table} 
            WHERE id = ? AND bloqueado_ate > NOW()
        ";
        $result = $this->db->fetchOne($sql, [$id]);
        return !empty($result);
    }
    
    // Registra último login
    public function updateLastLogin(int $id): void {
        $sql = "
            UPDATE {$this->table} 
            SET ultimo_login = NOW() 
            WHERE id = ?
        ";
        $this->db->executeQuery($sql, [$id]);
    }
    
    // Cria um novo usuário
    public function createUser(array $data): int {
        // Valida dados obrigatórios
        $requiredFields = ['nome', 'email', 'senha', 'privilegio'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("O campo {$field} é obrigatório");
            }
        }
        
        // Valida email único
        if ($this->findByEmail($data['email'])) {
            throw new Exception("Este email já está em uso");
        }
        
        // Aplica o pepper e gera hash da senha
        $senha_com_pepper = hash_hmac('sha256', $data['senha'], PASSWORD_PEPPER);
        $data['senha_hash'] = password_hash($senha_com_pepper, PASSWORD_DEFAULT);
        unset($data['senha']);
        
        // Define valores padrão
        $data['ativo'] = $data['ativo'] ?? true;
        $data['tentativas_login'] = 0;
        
        return $this->create($data);
    }
    
    // Atualiza a senha do usuário
    public function updatePassword(int $id, string $novaSenha): void {
        $senha_com_pepper = hash_hmac('sha256', $novaSenha, PASSWORD_PEPPER);
        $senha_hash = password_hash($senha_com_pepper, PASSWORD_DEFAULT);
        
        $sql = "UPDATE {$this->table} SET senha_hash = ? WHERE id = ?";
        $this->db->executeQuery($sql, [$senha_hash, $id]);
    }
}