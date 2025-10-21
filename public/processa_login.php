<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        header("Location: " . url('/public/login.php?error=empty'));
        exit();
    }

    require_once __DIR__ . '/../config/conexao.php';

    $sql = "SELECT id, nome, email, senha_hash, privilegio FROM usuarios WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($senha, $user['senha_hash'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['privilegio'] = $user['privilegio'];
                
                header("Location: " . url('/public/dashboard/painel_pedidos.php'));
                exit();
            }
        }
        $stmt->close();
    }
    
    header("Location: " . url('/public/login.php?error=invalid'));
    exit();
} else {
    header("Location: " . url('/public/login.php'));
    exit();
}