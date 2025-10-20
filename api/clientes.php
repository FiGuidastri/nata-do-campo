<?php
// api_clientes.php - Script para fornecer dados de clientes via AJAX (Autocomplete)
require_once 'conexao.php'; // Certifique-se de que este caminho está correto

// 1. Configura o cabeçalho para indicar que a resposta é JSON
header('Content-Type: application/json');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$data = [];

// Garante que o termo de busca não está vazio e tem pelo menos 2 caracteres
if (strlen($term) >= 2) {
    
    // 1. Prepara o termo de busca para ser insensível a maiúsculas/minúsculas (LIKE)
    $lowerTerm = strtolower($term); 
    $searchTerm = "%" . $lowerTerm . "%"; 
    
    // 2. Tenta obter o ID (código). Se não for um número válido, usa 0.
    $idTerm = filter_var($term, FILTER_VALIDATE_INT);
    if ($idTerm === false) {
        $idTerm = 0; 
    }
    
    try {
        // CORREÇÃO CRÍTICA: As colunas nome_cliente, cnpj e codigo_cliente estão sendo usadas.
        // A busca é realizada em nome_cliente, cnpj, codigo_cliente (LIKE) e id (MATCH EXATO).
        $sql = "SELECT id, nome_cliente, cnpj, tipo_cliente_id, codigo_cliente 
                FROM clientes 
                WHERE LOWER(nome_cliente) LIKE ? 
                   OR LOWER(cnpj) LIKE ? 
                   OR LOWER(codigo_cliente) LIKE ? 
                   OR id = ?
                ORDER BY nome_cliente ASC
                LIMIT 15";

        $stmt = $conn->prepare($sql);
        
        // Associa os parâmetros: sss (3 strings para os 3 LIKEs), i (1 integer para o ID)
        $stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $idTerm); 
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Mapeia os nomes das colunas do banco para os nomes esperados pelo JavaScript:
            $data[] = [
                'id' => $row['id'],
                'nome' => $row['nome_cliente'],       // Mapeia nome_cliente para o valor de busca (ui.item.value)
                'cnpj_cpf' => $row['cnpj'],           // Mapeia cnpj para o label de exibição no JS
                'tipo_cliente_id' => $row['tipo_cliente_id']
                // codigo_cliente não precisa ser retornado explicitamente se não for usado no JS
            ];
        }
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Erro crítico ao buscar clientes (SQL): " . $e->getMessage());
        // Em caso de erro, retorna um JSON vazio
    }
}

// 3. Imprime o array de dados como JSON
echo json_encode($data);

// 4. Fecha a conexão
if (isset($conn)) {
    $conn->close();
}
?>