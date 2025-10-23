<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_test.php');
    exit;
}

// Redirecionar para o dashboard completo
header('Location: admin_dashboard.php');
exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Café Gourmet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            border: 1px solid #c3e6cb;
        }
        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .menu-item {
            background: #4a2c2a;
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
        }
        .menu-item:hover {
            background: #8b4513;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">
            <h1>🎉 PAINEL ADMINISTRATIVO</h1>
            <p><strong>Bem-vindo, <?php echo $_SESSION['admin_nome']; ?>!</strong></p>
        </div>
        
        <h2>Menu de Navegação:</h2>
        <div class="menu">
            <a href="admin_dashboard.php" class="menu-item">
                📊 Dashboard Principal
            </a>
            <a href="admin_produtos.php" class="menu-item">
                📦 Gerenciar Produtos
            </a>
            <a href="admin_pedidos.php" class="menu-item">
                📋 Gerenciar Pedidos
            </a>
            <a href="admin_usuarios.php" class="menu-item">
                👥 Gerenciar Usuários
            </a>
            <a href="admin_test.php" class="menu-item">
                🔐 Voltar ao Login
            </a>
            <a href="admin_logout.php" class="menu-item">
                🚪 Sair
            </a>
        </div>
    </div>
</body>
</html>