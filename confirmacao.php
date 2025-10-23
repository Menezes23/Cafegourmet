<?php
session_start();

// Redirecionar se não houver pedido recente
if (!isset($_SESSION['ultimo_pedido']) && !isset($_GET['pedido'])) {
    header('Location: index.php');
    exit;
}

$pedido_id = $_GET['pedido'] ?? $_SESSION['ultimo_pedido'];

// Conexão com banco
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cafe_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

// Buscar informações do pedido
$stmt = $pdo->prepare("
    SELECT p.*, u.nome as usuario_nome, u.email 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar itens do pedido
$stmt_itens = $pdo->prepare("
    SELECT pi.*, pr.nome as produto_nome 
    FROM pedido_itens pi 
    JOIN produtos pr ON pi.produto_id = pr.id 
    WHERE pi.pedido_id = ?
");
$stmt_itens->execute([$pedido_id]);
$itens_pedido = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Pedido Confirmado - Café Gourmet</title>
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
        <div class="confirmation-container">
            <!-- Cabeçalho de Confirmação -->
            <div class="confirmation-header">
                <div class="confirmation-icon">🎉</div>
                <h1>Pedido Confirmado!</h1>
                <p>Obrigado por comprar conosco, <?php echo htmlspecialchars($pedido['usuario_nome']); ?>!</p>
            </div>

            <!-- Resumo do Pedido -->
            <div class="confirmation-details">
                <div class="detail-card">
                    <h3>📦 Resumo do Pedido</h3>
                    <div class="detail-row">
                        <span>Número do Pedido:</span>
                        <strong>#<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Data do Pedido:</span>
                        <span><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Status:</span>
                        <span class="status-badge status-<?php echo $pedido['status']; ?>">
                            <?php 
                            $status_text = [
                                'pendente' => '⏳ Aguardando Pagamento',
                                'confirmado' => '✅ Confirmado', 
                                'preparando' => '👨‍🍳 Preparando',
                                'enviado' => '🚚 Enviado',
                                'entregue' => '🎉 Entregue'
                            ];
                            echo $status_text[$pedido['status']];
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span>Total:</span>
                        <strong>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong>
                    </div>
                </div>

                <!-- Itens do Pedido -->
                <div class="items-card">
                    <h3>🛒 Itens do Pedido</h3>
                    <div class="confirmation-items">
                        <?php foreach ($itens_pedido as $item): ?>
                        <div class="confirmation-item">
                            <span class="item-name"><?php echo htmlspecialchars($item['produto_nome']); ?></span>
                            <span class="item-quantity"><?php echo $item['quantidade']; ?>x</span>
                            <span class="item-price">R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Instruções de Pagamento PIX -->
                <div class="pix-card">
                    <h3>📱 Pagamento via PIX</h3>
                    <div class="pix-instructions">
                        <div class="pix-qr-placeholder">
                            <div class="qr-code">[QR CODE]</div>
                            <p>Escaneie este QR Code com seu app bancário</p>
                        </div>
                        
                        <div class="pix-details">
                            <h4>Chave PIX:</h4>
                            <div class="pix-key">
                                <code>cafe.gourmet@pagamento.com</code>
                                <button class="btn-copy" onclick="copiarChavePIX()">📋 Copiar</button>
                            </div>
                            
                            <div class="pix-info">
                                <p><strong>Valor:</strong> R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></p>
                                <p><strong>Válido por:</strong> 30 minutos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Próximos Passos -->
                <div class="next-steps">
                    <h3>📋 Próximos Passos</h3>
                    <div class="steps-timeline">
                        <div class="step <?php echo $pedido['status'] === 'pendente' ? 'current' : ''; ?>">
                            <span class="step-number">1</span>
                            <div class="step-content">
                                <strong>Pagamento PIX</strong>
                                <p>Aguardando confirmação do pagamento</p>
                            </div>
                        </div>
                        <div class="step">
                            <span class="step-number">2</span>
                            <div class="step-content">
                                <strong>Preparo do Pedido</strong>
                                <p>Seus cafés serão preparados com cuidado</p>
                            </div>
                        </div>
                        <div class="step">
                            <span class="step-number">3</span>
                            <div class="step-content">
                                <strong>Envio</strong>
                                <p>Seu pedido será enviado para entrega</p>
                            </div>
                        </div>
                        <div class="step">
                            <span class="step-number">4</span>
                            <div class="step-content">
                                <strong>Entrega</strong>
                                <p>Seus cafés chegarão até você!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="confirmation-actions">
                <a href="minhaconta.php" class="btn-primary">👤 Acompanhar Meus Pedidos</a>
                <a href="index.php" class="btn-secondary">🛒 Continuar Comprando</a>
                <button class="btn-outline" onclick="window.print()">🖨️ Imprimir Recibo</button>
            </div>

            <!-- Informações de Contato -->
            <div class="contact-info">
                <h3>📞 Precisa de Ajuda?</h3>
                <p>Entre em contato conosco: <strong>contato@cafegourmet.com</strong> ou <strong>(11) 99999-9999</strong></p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Café Gourmet. Desenvolvido com ☕ e ❤️.</p>
        </div>
    </footer>

    <script>
    function copiarChavePIX() {
        const chave = 'cafe.gourmet@pagamento.com';
        navigator.clipboard.writeText(chave).then(() => {
            alert('Chave PIX copiada! ✅');
        });
    }

    // Simular atualização de status (apenas demonstração)
    setTimeout(() => {
        const statusElement = document.querySelector('.status-badge');
        if (statusElement && statusElement.classList.contains('status-pendente')) {
            // Em um sistema real, isso viria do servidor
            console.log('Verificando status do pagamento...');
        }
    }, 5000);
    </script>
</body>
</html>