<?php
session_start();

// Redirecionar se já estiver logado como admin
if (isset($_SESSION['admin_logado'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

// Processar login admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Credenciais fixas para demonstração (em produção usar banco)
    $admin_usuario = 'admin';
    $admin_senha = 'admin123';
      
    
    if ($usuario === $admin_usuario && $senha === $admin_senha) {
        $_SESSION['admin_logado'] = true;
        $_SESSION['admin_usuario'] = $usuario;
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = "Usuário ou senha incorretos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Café Gourmet</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4a2c2a, #8b4513);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-login-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: #4a2c2a;
            margin-bottom: 0.5rem;
        }
        
        .admin-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a2c2a;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #4a2c2a;
            outline: none;
        }
        
        .btn-admin-login {
            width: 100%;
            padding: 1rem;
            background: #4a2c2a;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-admin-login:hover {
            background: #8b4513;
        }
        
        .alert-error {
            background: #fee;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .admin-credentials {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #4a2c2a;
        }
        
        .admin-credentials h4 {
            color: #4a2c2a;
            margin-bottom: 0.5rem;
        }
        
        .admin-credentials p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-header">
            <h1>⚙️ Painel Admin</h1>
            <p>Café Gourmet - Controle Total</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="usuario">Usuário Admin:</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn-admin-login">🔐 Entrar no Painel</button>
        </form>
        
        <div class="admin-credentials">
            <h4>👨‍💻 Credenciais de Teste:</h4>
            <p><strong>Usuário:</strong> admin</p>
            <p><strong>Senha:</strong> admin123</p>
        </div>
    </div>
</body>
</html>