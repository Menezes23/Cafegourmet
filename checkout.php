<?php
session_start();

// Redirecionar se não estiver logado ou carrinho vazio
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
    header('Location: carrinho.php');
    exit;
}

// Conexão com banco
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cafe_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Calcular totais do carrinho
$total_itens = 0;
$subtotal = 0;
$frete = 8.00; // Frete fixo por enquanto
$total_geral = 0;

if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $total_itens += $item['quantidade'];
        $subtotal += $item['preco'] * $item['quantidade'];
    }
    $total_geral = $subtotal + $frete;
}

// Processar finalização do pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_pedido'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Criar o pedido
        $stmt_pedido = $pdo->prepare("
            INSERT INTO pedidos (usuario_id, total, status, endereco_entrega) 
            VALUES (?, ?, 'pendente', ?)
        ");
        
        $endereco_entrega = "Entrega padrão - Confirmar com cliente";
        $stmt_pedido->execute([$_SESSION['usuario_id'], $total_geral, $endereco_entrega]);
        $pedido_id = $pdo->lastInsertId();
        
        // 2. Adicionar itens ao pedido
        foreach ($_SESSION['carrinho'] as $produto_id => $item) {
            $stmt_item = $pdo->prepare("
                INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt_item->execute([$pedido_id, $produto_id, $item['quantidade'], $item['preco']]);
        }
        
        $pdo->commit();
        
        // Limpar carrinho e redirecionar para confirmação
        unset($_SESSION['carrinho']);
        $_SESSION['ultimo_pedido'] = $pedido_id;
        
        header('Location: confirmacao.php?pedido=' . $pedido_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao processar pedido: " . $e->getMessage();
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
    <title>Checkout - Café Gourmet</title>
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
        <div class="checkout-container">
            <!-- Progresso do Checkout -->
            <div class="checkout-progress">
                <div class="progress-step active">
                    <span class="step-number">1</span>
                    <span class="step-label">Carrinho</span>
                </div>
                <div class="progress-step active">
                    <span class="step-number">2</span>
                    <span class="step-label">Checkout</span>
                </div>
                <div class="progress-step">
                    <span class="step-number">3</span>
                    <span class="step-label">Confirmação</span>
                </div>
            </div>

            <h1>✅ Finalizar Pedido</h1>

            <?php if (isset($erro)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <div class="checkout-layout">
                <!-- Coluna da Esquerda - Formulário -->
                <div class="checkout-form-column">
                    <!-- Seção de Entrega -->
                    <section class="checkout-section">
                        <h2>🚚 Informações de Entrega</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome Completo *</label>
                                <input type="text" id="nome" name="nome" required 
                                       value="<?php echo htmlspecialchars($usuario['nome']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="telefone">Telefone *</label>
                                <input type="tel" id="telefone" name="telefone" required
                                       value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="endereco">Endereço Completo *</label>
                            <input type="text" id="endereco" name="endereco" required
                                   placeholder="Rua, número, bairro, complemento">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cidade">Cidade *</label>
                                <input type="text" id="cidade" name="cidade" required value="São Paulo">
                            </div>
                            
                            <div class="form-group">
                                <label for="estado">Estado *</label>
                                <select id="estado" name="estado" required>
                                    <option value="SP" selected>São Paulo</option>
                                    <option value="RJ">Rio de Janeiro</option>
                                    <option value="MG">Minas Gerais</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="cep">CEP *</label>
                                <input type="text" id="cep" name="cep" required placeholder="00000-000">
                            </div>
                        </div>
                    </section>

                    <!-- Seção de Pagamento -->
                    <section class="checkout-section">
                        <h2>💳 Forma de Pagamento</h2>
                        
                        <div class="payment-methods">
                            <div class="payment-method selected">
                                <input type="radio" id="pix" name="metodo_pagamento" value="pix" checked>
                                <label for="pix">
                                    <span class="payment-icon">📱</span>
                                    <span class="payment-info">
                                        <strong>PIX</strong>
                                        <small>Pagamento instantâneo</small>
                                    </span>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" id="cartao" name="metodo_pagamento" value="cartao">
                                <label for="cartao">
                                    <span class="payment-icon">💳</span>
                                    <span class="payment-info">
                                        <strong>Cartão de Crédito</strong>
                                        <small>Em breve</small>
                                    </span>
                                </label>
                            </div>
                            
                            <div class="payment-method disabled">
                                <input type="radio" id="boleto" name="metodo_pagamento" value="boleto" disabled>
                                <label for="boleto">
                                    <span class="payment-icon">📄</span>
                                    <span class="payment-info">
                                        <strong>Boleto</strong>
                                        <small>Em breve</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Informações do PIX -->
                        <div id="pix-info" class="payment-details active">
                            <div class="pix-instructions">
                                <h4>Como pagar com PIX:</h4>
                                <ol>
                                    <li>Finalize o pedido</li>
                                    <li>Você receberá um QR Code para pagamento</li>
                                    <li>Escaneie com seu app bancário</li>
                                    <li>Pagamento confirmado automaticamente</li>
                                </ol>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Coluna da Direita - Resumo -->
                <div class="checkout-summary-column">
                    <div class="order-summary">
                        <h3>📦 Resumo do Pedido</h3>
                        
                        <div class="summary-items">
                            <?php foreach ($_SESSION['carrinho'] as $id => $item): ?>
                            <div class="summary-item">
                                <div class="item-info">
                                    <span class="item-name"><?php echo htmlspecialchars($item['nome']); ?></span>
                                    <span class="item-quantity"><?php echo $item['quantidade']; ?>x</span>
                                </div>
                                <span class="item-price">R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="summary-totals">
                            <div class="total-line">
                                <span>Subtotal:</span>
                                <span>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                            </div>
                            <div class="total-line">
                                <span>Frete:</span>
                                <span>R$ <?php echo number_format($frete, 2, ',', '.'); ?></span>
                            </div>
                            <div class="total-line grand-total">
                                <span><strong>Total:</strong></span>
                                <span><strong>R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></strong></span>
                            </div>
                        </div>
                        
                        <!-- Formulário de Finalização -->
                        <form method="post" class="checkout-form">
                            <div class="form-group">
                                <label for="observacoes">Observações do pedido (opcional)</label>
                                <textarea id="observacoes" name="observacoes" placeholder="Alguma observação especial?"></textarea>
                            </div>
                            
                            <div class="terms-agreement">
                                <input type="checkbox" id="terms" name="terms" required>
                                <label for="terms">
                                    Concordo com os <a href="#" onclick="alert('Termos em desenvolvimento! 🚧')">termos de serviço</a> 
                                    e <a href="#" onclick="alert('Política em desenvolvimento! 🚧')">política de privacidade</a>
                                </label>
                            </div>
                            
                            <button type="submit" name="finalizar_pedido" class="btn-checkout">
                                🎉 Finalizar Pedido - R$ <?php echo number_format($total_geral, 2, ',', '.'); ?>
                            </button>
                            
                            <p class="security-note">
                                🔒 Suas informações estão seguras conosco
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Café Gourmet. Desenvolvido com ☕ e ❤️.</p>
        </div>
    </footer>

    <script>
    // Alternar entre métodos de pagamento
    document.querySelectorAll('input[name="metodo_pagamento"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Atualizar visual dos métodos
            document.querySelectorAll('.payment-method').forEach(method => {
                method.classList.remove('selected');
            });
            this.closest('.payment-method').classList.add('selected');
            
            // Mostrar detalhes do método selecionado
            document.querySelectorAll('.payment-details').forEach(detail => {
                detail.classList.remove('active');
            });
            
            const methodId = this.value + '-info';
            const detailsElement = document.getElementById(methodId);
            if (detailsElement) {
                detailsElement.classList.add('active');
            }
        });
    });

    // Validação do formulário
    document.querySelector('.checkout-form').addEventListener('submit', function(e) {
        const terms = document.getElementById('terms');
        if (!terms.checked) {
            e.preventDefault();
            alert('Por favor, aceite os termos de serviço para continuar.');
            terms.focus();
            return;
        }
        
        // Validação básica dos campos obrigatórios
        const requiredFields = this.querySelectorAll('[required]');
        let valid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = '#e74c3c';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
        }
    });

    // Máscara para CEP
    document.getElementById('cep').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 5) {
            value = value.substring(0, 5) + '-' + value.substring(5, 8);
        }
        e.target.value = value;
    });

    // Máscara para telefone
    document.getElementById('telefone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) {
            value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
        }
        if (value.length > 10) {
            value = value.substring(0, 10) + '-' + value.substring(10, 14);
        }
        e.target.value = value;
    });
    </script>
</body>
</html>