<?php
session_start();

// Se usuário já está logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Conexão com banco
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cafe_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

$erro = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Validar dados
    if (empty($email) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        // Buscar usuário
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            // Redirecionar para página anterior ou index
            header('Location: ' . ($_GET['redirect'] ?? 'index.php'));
            exit;
        } else {
            $erro = "Email ou senha incorretos.";
        }
    }
}

// Calcular total do carrinho para navbar
$total_carrinho = 0;
if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $total_carrinho += $item['quantidade'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Café Gourmet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>☕ Café Gourmet</h1>
            <p>Os melhores cafés especiais</p>
        </div>
    </header>

       <nav class="nav-bar">
        <div class="container">
            <a href="index.php" class="nav-link">Início</a>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- Usuário logado -->
                <div class="user-menu">
                    <span class="user-welcome">Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                    <a href="minhaconta.php" class="nav-link">Minha Conta</a>
                    <a href="logout.php" class="nav-link">Sair</a>
                </div>
            <?php else: ?>
                <!-- Usuário não logado -->
                <div class="user-menu">
                    <a href="login.php" class="nav-link">Entrar</a>
                    <a href="cadastro.php" class="nav-link">Cadastrar</a>
                </div>
            <?php endif; ?>
            
            <a href="carrinho.php" class="nav-link cart-link">
                🛒 Carrinho 
                <?php if (isset($_SESSION['carrinho']) && count($_SESSION['carrinho']) > 0): ?>
                    <span class="cart-count"><?php echo $total_carrinho; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <main class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h2>🔐 Fazer Login</h2>
                
                <?php if ($erro): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($erro); ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>

                    <button type="submit" class="btn-auth">Entrar</button>
                </form>

                <div class="auth-links">
                    <p>Não tem conta? <a href="cadastro.php">Cadastre-se aqui</a></p>
                    <p><a href="#">Esqueci minha senha</a></p>
                </div>

                <!-- Usuário de teste -->
                <div class="test-user">
                    <h4>👨‍💻 Usuário de Teste:</h4>
                    <p><strong>Email:</strong> joao@teste.com</p>
                    <p><strong>Senha:</strong> 123456</p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Café Gourmet. Desenvolvido com ☕ e ❤️.</p>
        </div>
    </footer>
</body>
</html>