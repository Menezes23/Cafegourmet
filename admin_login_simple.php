<?php
session_start();

// Conexão simples com banco
$host = 'localhost';
$dbname = 'cafe_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$erro = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // DEBUG: Mostrar o que está sendo recebido
    echo "<!-- DEBUG: Email: $email, Senha: $senha -->";
    
    if (empty($email) || empty($senha)) {
        $erro = "Preencha email e senha";
    } else {
        // Buscar admin
        $stmt = $pdo->prepare("SELECT * FROM administradores WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // DEBUG: Mostrar resultado da busca
        echo "<!-- DEBUG: Admin encontrado: " . ($admin ? 'SIM' : 'NÃO') . " -->";
        if ($admin) {
            echo "<!-- DEBUG: Hash no banco: " . $admin['senha_hash'] . " -->";
            echo "<!-- DEBUG: Verificação: " . (password_verify($senha, $admin['senha_hash']) ? 'OK' : 'FALHA') . " -->";
        }
        
        if ($admin && password_verify($senha, $admin['senha_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $erro = "Email ou senha inválidos!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Café Gourmet</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #4a2c2a, #8b4513);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        h1 { 
            text-align: center; 
            color: #4a2c2a; 
            margin-bottom: 1rem;
        }
        .error { 
            background: #fee; 
            color: #c33; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: bold; }
        input { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px;
        }
        input:focus { border-color: #4a2c2a; outline: none; }
        button { 
            width: 100%; 
            padding: 12px; 
            background: #4a2c2a; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            font-size: 16px; 
            cursor: pointer;
        }
        button:hover { background: #8b4513; }
        .debug-info { 
            background: #f0f0f0; 
            padding: 10px; 
            margin-top: 1rem; 
            border-radius: 5px; 
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>☕ Admin Café Gourmet</h1>
        
        <?php if ($erro): ?>
            <div class="error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="admin@cafegourmet.com" required>
            </div>
            
            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="senha" value="admin123" required>
            </div>
            
            <button type="submit">🔐 Entrar</button>
        </form>
        
        <div class="debug-info">
            <strong>Credenciais de teste:</strong><br>
            Email: admin@cafegourmet.com<br>
            Senha: admin123
        </div>
    </div>
</body>
</html>