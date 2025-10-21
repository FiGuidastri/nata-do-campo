<?php
// relatorio_resgates.php - Relatório de resgates do Clube Nata (Ação do Gestor)
require_once 'verifica_sessao.php'; 
require_once 'conexao.php'; 

// RESTRITO APENAS AO ADMIN E GESTOR
if ($usuario_logado['privilegio'] !== 'Admin') {
    header("Location: painel_pedidos.php?error=acesso_negado");
    exit();
}

$historico_resgates = [];

$sql_resgates = "SELECT 
    cr.id,
    cr.pontos_utilizados,
    cr.data_resgate,
    c.nome_cliente,
    crp.nome AS nome_recompensa
    FROM clube_nata_resgates cr
    JOIN clientes c ON cr.cliente_id = c.id
    JOIN clube_nata_recompensas crp ON cr.recompensa_id = crp.id
    ORDER BY cr.data_resgate DESC";

$result_resgates = $conn->query($sql_resgates);
if ($result_resgates) {
    while ($row = $result_resgates->fetch_assoc()) {
        $historico_resgates[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Resgates | Clube Nata</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilo específico do relatório */
        .custo-pontos { 
            font-weight: bold; 
            color: var(--danger-color); 
            text-align: center;
            white-space: nowrap; /* Não quebra linha nos pontos */
        }
        .data-table th, .data-table td:nth-child(4), .data-table td:nth-child(5) {
             text-align: center; /* Centraliza as colunas de ID, Pontos e Data */
        }
    </style>
</head>
<body>
    
    <header class="top-header">
        <div class="logo"><img src="logo.png" alt="Logo Nata do Campo"></div>
        <div class="user-info">
            <span class="user-name">Olá, <span><?= htmlspecialchars($usuario_logado['nome']) ?></span> (<?= $usuario_logado['privilegio'] ?>)</span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </header>

    <div class="main-layout">
        <?php include 'sidebar.php'; ?> 

        <div class="main-content">
            <div class="container">
                
                <h1><i class="fas fa-gift"></i> Histórico de Resgates do Clube Nata</h1>
                <p>Visão completa das recompensas trocadas por pontos.</p>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">ID Resgate</th>
                                <th style="width: 25%;">Cliente</th>
                                <th style="width: 35%;">Recompensa Resgatada</th>
                                <th style="width: 15%;">Pontos Utilizados</th>
                                <th style="width: 15%;">Data do Resgate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico_resgates)): ?>
                                <tr><td colspan="5" style="text-align: center;">Nenhum resgate registrado até o momento.</td></tr>
                            <?php else: ?>
                                <?php foreach ($historico_resgates as $resgate): ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $resgate['id'] ?></td>
                                        <td><?= htmlspecialchars($resgate['nome_cliente']) ?></td>
                                        <td><?= htmlspecialchars($resgate['nome_recompensa']) ?></td>
                                        <td class="custo-pontos">-<?= number_format($resgate['pontos_utilizados'], 0, ',', '.') ?> pts</td>
                                        <td><?= (new DateTime($resgate['data_resgate']))->format('d/m/Y H:i:s') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    </body>
</html>