<?php
// Iniciar sessão
session_start();

// Conexão com banco (caso precise de mais informações)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cafe_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

// Processar remoção do carrinho
if (isset($_POST['remover_do_carrinho'])) {
    $produto_id = $_POST['produto_id'];
    if (isset($_SESSION['carrinho'][$produto_id])) {
        unset($_SESSION['carrinho'][$produto_id]);
    }
}

// Processar atualização de quantidade
if (isset($_POST['atualizar_quantidade'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = intval($_POST['quantidade']);
    
    if ($quantidade <= 0) {
        unset($_SESSION['carrinho'][$produto_id]);
    } else {
        $_SESSION['carrinho'][$produto_id]['quantidade'] = $quantidade;
    }
}

// Calcular totais
$total_itens = 0;
$total_valor = 0;

if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $total_itens += $item['quantidade'];
        $total_valor += $item['preco'] * $item['quantidade'];
    }
}

// Calcular total para navbar
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
    <title>Carrinho de Compras - Café Gourmet</title>
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
            <a href="index.php" class="nav-link">← Voltar às Compras</a>
            
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
            
            <span class="nav-link">🛒 Meu Carrinho (<?php echo $total_itens; ?>)</span>
        </div>
    </nav>

    <main class="container">
        <h2>Meu Carrinho de Compras</h2>

        <?php if (empty($_SESSION['carrinho'])): ?>
            <div class="empty-cart">
                <div class="empty-icon">🛒</div>
                <h3>Seu carrinho está vazio</h3>
                <p>Que tal explorar nossos deliciosos cafés?</p>
                <a href="index.php" class="btn-primary">Ver Produtos</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($_SESSION['carrinho'] as $id => $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <span class="placeholder-image">☕</span>
                        </div>
                        
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p class="item-price">R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?> cada</p>
                            
                            <form method="post" class="quantity-form">
                                <input type="hidden" name="produto_id" value="<?php echo $id; ?>">
                                <label>Quantidade:</label>
                                <input type="number" name="quantidade" value="<?php echo $item['quantidade']; ?>" min="1" max="10">
                                <button type="submit" name="atualizar_quantidade" class="btn-update">Atualizar</button>
                            </form>
                        </div>
                        
                        <div class="cart-item-total">
                            <p class="subtotal">R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></p>
                            
                            <form method="post">
                                <input type="hidden" name="produto_id" value="<?php echo $id; ?>">
                                <button type="submit" name="remover_do_carrinho" class="btn-remove">🗑️ Remover</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Resumo do Pedido</h3>
                        
                        <div class="summary-line">
                            <span>Itens no carrinho:</span>
                            <span><?php echo $total_itens; ?></span>
                        </div>
                        
                        <div class="summary-line">
                            <span>Subtotal:</span>
                            <span>R$ <?php echo number_format($total_valor, 2, ',', '.'); ?></span>
                        </div>
                        
                        <div class="summary-line">
                            <span>Frete:</span>
                            <span>Grátis</span>
                        </div>
                        
                        <div class="summary-line total">
                            <span><strong>Total:</strong></span>
                            <span><strong>R$ <?php echo number_format($total_valor, 2, ',', '.'); ?></strong></span>
                        </div>
                        
                        <div class="summary-actions">
                            <a href="index.php" class="btn-secondary">Continuar Comprando</a>
                            
                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <a href="checkout.php" class="btn-primary">Finalizar Compra</a>
                            <?php else: ?>
                                <a href="login.php?redirect=carrinho.php" class="btn-primary">Fazer Login para Finalizar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Café Gourmet. Desenvolvido com ☕ e ❤️.</p>
        </div>
    </footer>

    <script>
    // Feedback ao atualizar quantidade
    document.querySelectorAll('.quantity-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('.btn-update');
            const originalText = button.innerHTML;
            
            button.innerHTML = '✓ Atualizado';
            button.style.background = '#27ae60';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
            }, 2000);
        });
    });

    // Feedback ao remover item
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja remover este item do carrinho?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>