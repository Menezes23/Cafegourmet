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

// Verificar se o ID do produto foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$produto_id = $_GET['id'];

// Buscar informações do produto específico
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$produto_id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

// Se produto não existe, redirecionar
if (!$produto) {
    header('Location: index.php');
    exit;
}

// Processar adição ao carrinho
if (isset($_POST['adicionar_ao_carrinho'])) {
    $quantidade = intval($_POST['quantidade']);
    
    // Inicializar carrinho se não existir
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
    
    // Adicionar produto ao carrinho
    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id]['quantidade'] += $quantidade;
    } else {
        $_SESSION['carrinho'][$produto_id] = [
            'nome' => $produto['nome'],
            'preco' => $produto['preco'],
            'quantidade' => $quantidade
        ];
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: carrinho.php');
    exit;
}

// Calcular total de itens no carrinho para a navbar
$total_carrinho = 0;
if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $total_carrinho += $item['quantidade'];
    }
}

// Buscar produtos relacionados (mesma categoria)
$stmt_relacionados = $pdo->prepare("SELECT * FROM produtos WHERE categoria = ? AND id != ? LIMIT 3");
$stmt_relacionados->execute([$produto['categoria'], $produto_id]);
$produtos_relacionados = $stmt_relacionados->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - Café Gourmet</title>
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
            
            <a href="carrinho.php" class="nav-link cart-link">
                🛒 Carrinho 
                <?php if (isset($_SESSION['carrinho']) && count($_SESSION['carrinho']) > 0): ?>
                    <span class="cart-count"><?php echo $total_carrinho; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <main class="container">
        <!-- Navegação breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Início</a> &gt; 
            <a href="index.php?categoria=<?php echo urlencode($produto['categoria']); ?>">
                <?php echo htmlspecialchars($produto['categoria']); ?>
            </a> &gt; 
            <span><?php echo htmlspecialchars($produto['nome']); ?></span>
        </nav>

        <div class="product-detail">
            <div class="product-detail-image">
                <span class="placeholder-image-large">☕</span>
            </div>

            <div class="product-detail-info">
                <h1><?php echo htmlspecialchars($produto['nome']); ?></h1>
                <div class="product-rating">
                    <span class="stars">★★★★★</span>
                    <span class="rating-text">(4.8/5 - 128 avaliações)</span>
                </div>
                
                <p class="product-detail-description"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <strong>Categoria:</strong> <?php echo htmlspecialchars($produto['categoria']); ?>
                    </div>
                    <div class="meta-item">
                        <strong>Disponibilidade:</strong> <span class="in-stock">Em Estoque</span>
                    </div>
                </div>

                <div class="product-detail-price">
                    R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                </div>

                <form method="post" class="add-to-cart-form-detail">
                    <div class="quantity-selector">
                        <label for="quantidade">Quantidade:</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn minus">-</button>
                            <input type="number" id="quantidade" name="quantidade" value="1" min="1" max="10" readonly>
                            <button type="button" class="quantity-btn plus">+</button>
                        </div>
                    </div>
                    
                    <button type="submit" name="adicionar_ao_carrinho" class="add-to-cart-detail">
                        🛒 Adicionar ao Carrinho
                    </button>
                </form>

                <div class="product-features">
                    <h3>🌱 Características do Produto</h3>
                    <ul>
                        <li>✅ Café 100% arábica</li>
                        <li>✅ Torra especial</li>
                        <li>✅ Embalagem à vácuo</li>
                        <li>✅ Pronto para preparo</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Produtos Relacionados -->
        <?php if (!empty($produtos_relacionados)): ?>
        <section class="related-products">
            <h2>🍵 Você também pode gostar</h2>
            <div class="product-grid">
                <?php foreach ($produtos_relacionados as $relacionado): ?>
                <div class="product-card">
                    <a href="produto.php?id=<?php echo $relacionado['id']; ?>" class="product-link">
                        <div class="product-image">
                            <span class="placeholder-image">☕</span>
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($relacionado['nome']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($relacionado['descricao']); ?></p>
                            <p class="product-category"><?php echo htmlspecialchars($relacionado['categoria']); ?></p>
                            <p class="product-price">R$ <?php echo number_format($relacionado['preco'], 2, ',', '.'); ?></p>
                        </div>
                    </a>
                    
                    <!-- Formulário para adicionar ao carrinho -->
                    <form method="post" class="add-to-cart-form">
                        <input type="hidden" name="produto_id" value="<?php echo $relacionado['id']; ?>">
                        <button type="submit" name="adicionar_ao_carrinho" class="add-to-cart">
                            🛒 Adicionar ao Carrinho
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Café Gourmet. Desenvolvido com ☕ e ❤️.</p>
        </div>
    </footer>

    <script>
    // Controles de quantidade
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            let value = parseInt(input.value);
            
            if (this.classList.contains('plus') && value < 10) {
                input.value = value + 1;
            } else if (this.classList.contains('minus') && value > 1) {
                input.value = value - 1;
            }
        });
    });

    // Feedback ao adicionar ao carrinho
    document.querySelector('.add-to-cart-form-detail').addEventListener('submit', function(e) {
        const button = this.querySelector('.add-to-cart-detail');
        const originalText = button.innerHTML;
        
        // Feedback visual
        button.innerHTML = '✅ Adicionado ao Carrinho!';
        button.style.background = '#27ae60';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
        }, 3000);
    });
    </script>
</body>
</html>