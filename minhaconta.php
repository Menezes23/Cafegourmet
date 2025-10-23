<?php
session_start();

// Redirecionar se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
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

// Buscar pedidos do usuário
$stmt_pedidos = $pdo->prepare("
    SELECT p.*, 
           COUNT(pi.id) as total_itens,
           SUM(pi.quantidade * pi.preco_unitario) as valor_total
    FROM pedidos p 
    LEFT JOIN pedido_itens pi ON p.id = pi.pedido_id 
    WHERE p.usuario_id = ? 
    GROUP BY p.id 
    ORDER BY p.data_pedido DESC
");
$stmt_pedidos->execute([$_SESSION['usuario_id']]);
$pedidos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);

// Calcular total do carrinho para navbar
$total_carrinho = 0;
if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $total_carrinho += $item['quantidade'];
    }
}

$mensagem = '';
$erro = '';

// Processar atualização de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_dados'])) {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    
    if (empty($nome)) {
        $erro = "O nome é obrigatório.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, telefone = ? WHERE id = ?");
        if ($stmt->execute([$nome, $telefone, $_SESSION['usuario_id']])) {
            $_SESSION['usuario_nome'] = $nome;
            $mensagem = "Dados atualizados com sucesso!";
            // Recarregar dados do usuário
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $erro = "Erro ao atualizar dados. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Café Gourmet</title>
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
        <div class="account-container">
            <!-- Cabeçalho da Conta -->
            <div class="account-header">
                <h1>👤 Minha Conta</h1>
                <p>Gerencie seus dados e acompanhe seus pedidos</p>
            </div>

            <div class="account-layout">
                <!-- Menu Lateral -->
                <aside class="account-sidebar">
                    <div class="sidebar-menu">
                        <a href="#dados-pessoais" class="sidebar-item active">📝 Dados Pessoais</a>
                        <a href="#meus-pedidos" class="sidebar-item">📦 Meus Pedidos</a>
                        <a href="#enderecos" class="sidebar-item">🏠 Endereços</a>
                    </div>
                    
                    <div class="account-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($pedidos); ?></span>
                            <span class="stat-label">Pedidos Realizados</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?></span>
                            <span class="stat-label">Cliente desde</span>
                        </div>
                    </div>
                </aside>

                <!-- Conteúdo Principal -->
                <div class="account-content">
                    <!-- Seção: Dados Pessoais -->
                    <section id="dados-pessoais" class="content-section active">
                        <h2>📝 Meus Dados Pessoais</h2>
                        
                        <?php if ($mensagem): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($mensagem); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($erro): ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($erro); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="account-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nome">Nome Completo *</label>
                                    <input type="text" id="nome" name="nome" required 
                                           value="<?php echo htmlspecialchars($usuario['nome']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>
                                    <small>O email não pode ser alterado</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="telefone">Telefone</label>
                                    <input type="tel" id="telefone" name="telefone" 
                                           value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="data_cadastro">Data de Cadastro</label>
                                    <input type="text" id="data_cadastro" 
                                           value="<?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?>" disabled>
                                </div>
                            </div>
                            
                            <button type="submit" name="atualizar_dados" class="btn-primary">
                                💾 Salvar Alterações
                            </button>
                        </form>
                    </section>

                    <!-- Seção: Meus Pedidos -->
                    <section id="meus-pedidos" class="content-section">
                        <h2>📦 Meus Pedidos</h2>
                        
                        <?php if (empty($pedidos)): ?>
                            <div class="empty-orders">
                                <div class="empty-icon">📦</div>
                                <h3>Nenhum pedido encontrado</h3>
                                <p>Que tal fazer seu primeiro pedido?</p>
                                <a href="index.php" class="btn-primary">Fazer Meu Primeiro Pedido</a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($pedidos as $pedido): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <h4>Pedido #<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></h4>
                                            <p class="order-date">Realizado em: <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
                                        </div>
                                        <div class="order-status">
                                            <span class="status-badge status-<?php echo $pedido['status']; ?>">
                                                <?php 
                                                $status_text = [
                                                    'pendente' => '⏳ Pendente',
                                                    'confirmado' => '✅ Confirmado', 
                                                    'preparando' => '👨‍🍳 Preparando',
                                                    'enviado' => '🚚 Enviado',
                                                    'entregue' => '🎉 Entregue'
                                                ];
                                                echo $status_text[$pedido['status']];
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details">
                                        <div class="order-items">
                                            <span class="detail-label">Itens:</span>
                                            <span class="detail-value"><?php echo $pedido['total_itens']; ?> produtos</span>
                                        </div>
                                        <div class="order-total">
                                            <span class="detail-label">Total:</span>
                                            <span class="detail-value">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <button class="btn-outline" onclick="verDetalhesPedido(<?php echo $pedido['id']; ?>)">
                                            📋 Ver Detalhes
                                        </button>
                                        <?php if ($pedido['status'] === 'pendente'): ?>
                                            <button class="btn-outline" onclick="cancelarPedido(<?php echo $pedido['id']; ?>)">
                                                ❌ Cancelar Pedido
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Seção: Endereços -->
                    <section id="enderecos" class="content-section">
                        <h2>🏠 Meus Endereços</h2>
                        
                        <div class="addresses-list">
                            <div class="address-card">
                                <div class="address-header">
                                    <h4>📍 Endereço Principal</h4>
                                    <span class="default-badge">Padrão</span>
                                </div>
                                <div class="address-details">
                                    <p>Rua das Flores, 123</p>
                                    <p>Centro - São Paulo/SP</p>
                                    <p>CEP: 01234-567</p>
                                </div>
                                <div class="address-actions">
                                    <button class="btn-outline">✏️ Editar</button>
                                    <button class="btn-outline">🗑️ Remover</button>
                                </div>
                            </div>
                            
                            <div class="add-address">
                                <button class="btn-add-address" onclick="adicionarEndereco()">
                                    ➕ Adicionar Novo Endereço
                                </button>
                            </div>
                        </div>
                    </section>
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
    // Navegação entre seções
    document.querySelectorAll('.sidebar-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover active de todos
            document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            
            // Adicionar active ao clicado
            this.classList.add('active');
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });

    function verDetalhesPedido(pedidoId) {
        alert(`Detalhes do pedido #${pedidoId} em desenvolvimento! 🚧`);
    }

    function cancelarPedido(pedidoId) {
        if (confirm('Tem certeza que deseja cancelar este pedido?')) {
            alert(`Pedido #${pedidoId} cancelado! 🚧`);
        }
    }

    function adicionarEndereco() {
        alert('Funcionalidade de adicionar endereço em desenvolvimento! 🚧');
    }

    // Validação do formulário
    document.querySelector('.account-form').addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value.trim();
        if (!nome) {
            e.preventDefault();
            alert('Por favor, preencha seu nome.');
            document.getElementById('nome').focus();
        }
    });
    </script>
</body>
</html>