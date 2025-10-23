<?php
// Iniciar sessão para o carrinho
session_start();

// Conexão com banco de dados
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cafe_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

// Processar adição ao carrinho
if (isset($_POST['adicionar_ao_carrinho'])) {
    $produto_id = $_POST['produto_id'];
    
    // Inicializar carrinho se não existir
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
    
    // Adicionar produto ao carrinho
    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id]['quantidade']++;
    } else {
        // Buscar informações do produto
        $stmt = $pdo->prepare("SELECT nome, preco FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($produto) {
            $_SESSION['carrinho'][$produto_id] = [
                'nome' => $produto['nome'],
                'preco' => $produto['preco'],
                'quantidade' => 1
            ];
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Buscar produtos com filtros
$busca = $_GET['busca'] ?? '';
$categoria = $_GET['categoria'] ?? '';

if ($busca) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ? OR descricao LIKE ?");
    $stmt->execute(["%$busca%", "%$busca%"]);
} elseif ($categoria) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria = ?");
    $stmt->execute([$categoria]);
} else {
    $stmt = $pdo->query("SELECT * FROM produtos");
}
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total de itens no carrinho para navbar
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
    <title>Café Gourmet - Loja Oficial</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>☕ Café Gourmet</h1>
            <p>Os melhores cafés especiais</p>
        </div>
    </header>

       <!-- Barra de Navegação ATUALIZADA -->
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
            
            <!-- Link para Admin (sempre visível) -->
            <a href="admin/" class="nav-link admin-link" title="Acesso Administrativo">⚙️ Admin</a>
            
            <a href="carrinho.php" class="nav-link cart-link">
                🛒 Carrinho 
                <?php if (isset($_SESSION['carrinho']) && count($_SESSION['carrinho']) > 0): ?>
                    <span class="cart-count"><?php echo $total_carrinho; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <main class="container">
        <!-- Barra de Busca -->
        <section class="search-section">
            <div class="search-container">
                <form method="get" action="index.php" class="search-form">
                    <input type="text" name="busca" placeholder="🔍 Buscar cafés..." 
                           value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
                    <button type="submit">Buscar</button>
                </form>
                
                <!-- Filtros por Categoria -->
                <div class="category-filters">
                    <a href="index.php" class="category-filter <?php echo empty($_GET['categoria']) ? 'active' : ''; ?>">
                        Todos
                    </a>
                    <a href="index.php?categoria=Tradicional" class="category-filter <?php echo ($_GET['categoria'] ?? '') == 'Tradicional' ? 'active' : ''; ?>">
                        Tradicional
                    </a>
                    <a href="index.php?categoria=Especial" class="category-filter <?php echo ($_GET['categoria'] ?? '') == 'Especial' ? 'active' : ''; ?>">
                        Especial
                    </a>
                    <a href="index.php?categoria=Gelado" class="category-filter <?php echo ($_GET['categoria'] ?? '') == 'Gelado' ? 'active' : ''; ?>">
                        Gelado
                    </a>
                </div>
            </div>
        </section>

        <div class="status-bar">
            <div class="status-item">
                <span class="status-icon">📦</span>
                <span><?php echo count($produtos); ?> Produtos</span>
            </div>
            <div class="status-item">
                <span class="status-icon">🛒</span>
                <span><?php echo $total_carrinho; ?> Itens no Carrinho</span>
            </div>
        </div>

        <section class="products">
            <h2>Nossos Cafés</h2>
            
            <?php if (empty($produtos)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>Nenhum produto encontrado</h3>
                    <p>Tente ajustar sua busca ou filtro</p>
                    <a href="index.php" class="btn-primary">Ver Todos os Produtos</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($produtos as $produto): ?>
                    <div class="product-card">
                        <a href="produto.php?id=<?php echo $produto['id']; ?>" class="product-link">
                            <div class="product-image">
                                <span class="placeholder-image">☕</span>
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($produto['nome']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                                <p class="product-category"><?php echo htmlspecialchars($produto['categoria']); ?></p>
                                <p class="product-price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                            </div>
                        </a>
                        
                        <!-- Formulário para adicionar ao carrinho -->
                        <form method="post" class="add-to-cart-form">
                            <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                            <button type="submit" name="adicionar_ao_carrinho" class="add-to-cart">
                                🛒 Adicionar ao Carrinho
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Café Gourmet. Desenvolvido com ☕ e ❤️.</p>
        </div>
    </footer>

    <script>
    // Feedback visual ao adicionar ao carrinho
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('.add-to-cart');
            const originalText = button.innerHTML;
            
            // Feedback visual
            button.innerHTML = '✅ Adicionado!';
            button.style.background = '#27ae60';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
            }, 2000);
        });
    });

    // Feedback para busca vazia
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('busca') && <?php echo count($produtos) === 0 ? 'true' : 'false'; ?>) {
        setTimeout(() => {
            const searchInput = document.querySelector('input[name="busca"]');
            if (searchInput) {
                searchInput.focus();
            }
        }, 500);
    }
    </script>
</body>
</html>