<?php
// index.php - Ponto de entrada moderno. Redireciona para o Painel de Pedidos se logado.
session_start();

// Definições do Sistema (Ajuste se você tiver um arquivo de constantes)
const APP_NAME = 'Nata do Campo';
const COMPANY_NAME = 'Laticínios AMB';

// Se logado, redireciona para a tela central
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: painel_pedidos.php");
    exit;
}

$page_title = 'Flow';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* Estilos Exclusivos da Landing Page */

        /* Cor de destaque para botões e gradientes */
        :root {
            --landing-primary: var(--primary-color); /* Verde Escuro */
            --landing-secondary: #ffb300; /* Ouro/Amarelo Destaque */
            --landing-background: #e9ecef;
        }

        .landing-full {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* Fundo com textura ou degradê suave para preencher a tela */
            background: var(--landing-background);
            background-image: linear-gradient(135deg, var(--landing-background) 0%, #fff 50%, var(--landing-background) 100%);
            padding: 20px;
        }

        .landing-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
            max-width: 450px;
            width: 100%;
            transition: transform 0.3s ease;
        }

        .landing-card:hover {
            transform: translateY(-5px);
        }

        .landing-logo img {
            height: 70px;
            margin-bottom: 25px;
        }

        .landing-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--landing-primary);
            margin-bottom: 10px;
        }

        .landing-subtitle {
            font-size: 1rem;
            color: var(--text-color);
            margin-bottom: 30px;
        }

        .btn-landing {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            border-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s;
            text-decoration: none;
        }

        .btn-primary-landing {
            background: var(--landing-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 77, 64, 0.3);
        }

        .btn-primary-landing:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary-landing {
            margin-top: 15px;
            background: var(--landing-secondary);
            color: var(--text-color);
        }
        
        .btn-secondary-landing:hover {
            background: #e6a200;
        }

        .landing-footer {
            margin-top: 40px;
            color: #999;
            font-size: 0.85rem;
        }

        @media (max-width: 480px) {
             .landing-card {
                padding: 30px 20px;
             }
             .landing-logo img {
                height: 60px;
             }
        }
    </style>
</head>
<body class="landing-full">
    
    <main class="landing-content">
        <div class="landing-card">
            <div class="landing-logo">
                <img src="nata.png" alt="<?= APP_NAME ?>">
            </div>
            
            <h1 class="landing-title">Flow <?= APP_NAME ?></h1>
            <p class="landing-subtitle">
                Sua plataforma de gestão e lançamento de pedidos.
            </p>
            
            <div class="landing-actions">
                <a href="login.php" class="btn-landing btn-primary-landing">
                    <i class="fas fa-lock"></i> Entrar no Sistema
                </a>
                
                <a href="https://wa.me/5514998559540" class="btn-landing btn-secondary-landing" style="border: 1px solid #e6a200;">
                    <i class="fas fa-headset"></i> Suporte
                </a>
            </div>
        </div>
    </main>
    
    <footer class="landing-footer">
        <p>&copy; 2025 <?= APP_NAME ?> | Desenvolvido com ❤ por Marketing.</p>
    </footer>
</body>
</html>