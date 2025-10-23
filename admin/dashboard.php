<?php
session_start();

// Verificar se está logado como admin
if (!isset($_SESSION['admin_logado'])) {
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

// Buscar estatísticas
// Total de pedidos
$stmt_pedidos = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
$total_pedidos = $stmt_pedidos->fetch(PDO::FETCH_ASSOC)['total'];

// Pedidos pendentes
$stmt_pendentes = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE status = 'pendente'");
$pedidos_pendentes = $stmt_pendentes->fetch(PDO::FETCH_ASSOC)['total'];

// Total de usuários
$stmt_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
$total_usuarios = $stmt_usuarios->fetch(PDO::FETCH_ASSOC)['total'];

// Total de produtos
$stmt_produtos = $pdo->query("SELECT COUNT(*) as total FROM produtos");
$total_produtos = $stmt_produtos->fetch(PDO::FETCH_ASSOC)['total'];

// Faturamento total
$stmt_faturamento = $pdo->query("SELECT SUM(total) as total FROM pedidos WHERE status != 'pendente'");
$faturamento_total = $stmt_faturamento->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Pedidos recentes
$stmt_recentes = $pdo->query("
    SELECT p.*, u.nome as cliente 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    ORDER BY p.data_pedido DESC 
    LIMIT 5
");
$pedidos_recentes = $stmt_recentes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Café Gourmet</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-nav {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .admin-nav-menu {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .admin-nav-link {
            padding: 1rem 1.5rem;
            background: #4a2c2a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-nav-link:hover {
            background: #8b4513;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4a2c2a;
            display: block;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .recent-orders, .quick-actions {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .recent-orders h3, .quick-actions h3 {
            color: #4a2c2a;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .order-info h4 {
            color: #4a2c2a;
            margin-bottom: 0.25rem;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-confirmado { background: #d1ecf1; color: #0c5460; }
        .status-preparando { background: #d4edda; color: #155724; }
        
        .quick-actions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .quick-action {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #4a2c2a;
            transition: all 0.3s;
        }
        
        .quick-action:hover {
            background: #4a2c2a;
            color: white;
            transform: translateY(-2px);
        }
        
        .action-icon {
            font-size: 2rem;
        }
        
        .action-text {
            flex: 1;
        }
        
        .action-text strong {
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .action-text span {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Cabeçalho -->
        <div class="admin-header">
            <div>
                <h1>⚙️ Painel Administrativo</h1>
                <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_usuario']); ?>! 👋</p>
            </div>
            <a href="logout.php" class="btn-logout">🚪 Sair</a>
        </div>
        
        <!-- Navegação -->
        <nav class="admin-nav">
            <div class="admin-nav-menu">
                <a href="dashboard.php" class="admin-nav-link">📊 Dashboard</a>
                <a href="produtos.php" class="admin-nav-link">📦 Produtos</a>
                <a href="pedidos.php" class="admin-nav-link">📋 Pedidos</a>
                <a href="usuarios.php" class="admin-nav-link">👥 Usuários</a>
                <a href="relatorios.php" class="admin-nav-link">📈 Relatórios</a>
            </div>
        </nav>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <span class="stat-number"><?php echo $total_pedidos; ?></span>
                <span class="stat-label">Total de Pedidos</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <span class="stat-number"><?php echo $pedidos_pendentes; ?></span>
                <span class="stat-label">Pedidos Pendentes</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <span class="stat-number"><?php echo $total_usuarios; ?></span>
                <span class="stat-label">Usuários Cadastrados</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <span class="stat-number"><?php echo $total_produtos; ?></span>
                <span class="stat-label">Produtos</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <span class="stat-number">R$ <?php echo number_format($faturamento_total, 2, ',', '.'); ?></span>
                <span class="stat-label">Faturamento Total</span>
            </div>
        </div>
        
        <!-- Grid Principal -->
        <div class="dashboard-grid">
            <!-- Pedidos Recentes -->
            <div class="recent-orders">
                <h3>📋 Pedidos Recentes</h3>
                <div class="orders-list">
                    <?php if (empty($pedidos_recentes)): ?>
                        <p>Nenhum pedido recente.</p>
                    <?php else: ?>
                        <?php foreach ($pedidos_recentes as $pedido): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h4>Pedido #<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></h4>
                                <p class="order-date"><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?> - <?php echo htmlspecialchars($pedido['cliente']); ?></p>
                            </div>
                            <span class="order-status status-<?php echo $pedido['status']; ?>">
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Ações Rápidas -->
            <div class="quick-actions">
                <h3>⚡ Ações Rápidas</h3>
                <div class="quick-actions-grid">
                    <a href="produtos.php?action=novo" class="quick-action">
                        <span class="action-icon">➕</span>
                        <div class="action-text">
                            <strong>Novo Produto</strong>
                            <span>Adicionar novo café</span>
                        </div>
                    </a>
                    
                    <a href="pedidos.php?filter=pending" class="quick-action">
                        <span class="action-icon">⏳</span>
                        <div class="action-text">
                            <strong>Pedidos Pendentes</strong>
                            <span>Ver pedidos aguardando</span>
                        </div>
                    </a>
                    
                    <a href="usuarios.php" class="quick-action">
                        <span class="action-icon">👥</span>
                        <div class="action-text">
                            <strong>Gerenciar Usuários</strong>
                            <span>Ver todos os clientes</span>
                        </div>
                    </a>
                    
                    <a href="relatorios.php" class="quick-action">
                        <span class="action-icon">📈</span>
                        <div class="action-text">
                            <strong>Relatórios</strong>
                            <span>Ver relatórios de vendas</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>