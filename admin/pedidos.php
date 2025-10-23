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

$mensagem = '';
$erro = '';

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    $pedido_id = $_POST['pedido_id'] ?? '';
    $novo_status = $_POST['novo_status'] ?? '';
    
    if ($pedido_id && $novo_status) {
        $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
        if ($stmt->execute([$novo_status, $pedido_id])) {
            $mensagem = "Status do pedido atualizado com sucesso!";
        } else {
            $erro = "Erro ao atualizar status do pedido!";
        }
    }
}

// Filtros
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_data = $_GET['data'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($filtro_status !== 'todos') {
    $where_conditions[] = "p.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_data) {
    $where_conditions[] = "DATE(p.data_pedido) = ?";
    $params[] = $filtro_data;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Buscar pedidos
$stmt = $pdo->prepare("
    SELECT p.*, u.nome as cliente_nome, u.email as cliente_email,
           COUNT(pi.id) as total_itens,
           SUM(pi.quantidade * pi.preco_unitario) as valor_total
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    LEFT JOIN pedido_itens pi ON p.id = pi.pedido_id 
    $where_sql
    GROUP BY p.id 
    ORDER BY p.data_pedido DESC
");
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar detalhes de um pedido específico
$pedido_detalhes = null;
$itens_pedido = [];
if (isset($_GET['detalhes'])) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome as cliente_nome, u.email, u.telefone 
        FROM pedidos p 
        JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$_GET['detalhes']]);
    $pedido_detalhes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido_detalhes) {
        $stmt_itens = $pdo->prepare("
            SELECT pi.*, pr.nome as produto_nome 
            FROM pedido_itens pi 
            JOIN produtos pr ON pi.produto_id = pr.id 
            WHERE pi.pedido_id = ?
        ");
        $stmt_itens->execute([$_GET['detalhes']]);
        $itens_pedido = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Estatísticas por status
$stmt_stats = $pdo->query("
    SELECT status, COUNT(*) as total 
    FROM pedidos 
    GROUP BY status
");
$stats = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

$stats_array = [
    'pendente' => 0,
    'confirmado' => 0,
    'preparando' => 0,
    'enviado' => 0,
    'entregue' => 0
];

foreach ($stats as $stat) {
    $stats_array[$stat['status']] = $stat['total'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pedidos - Admin</title>
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
        
        .admin-nav-link.active {
            background: #8b4513;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card.active {
            border: 2px solid #4a2c2a;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-pendente { color: #e74c3c; }
        .stat-confirmado { color: #3498db; }
        .stat-preparando { color: #f39c12; }
        .stat-enviado { color: #9b59b6; }
        .stat-entregue { color: #27ae60; }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .filters-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .filters-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            color: #4a2c2a;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .filter-group select, .filter-group input {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .btn-filter {
            background: #4a2c2a;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-filter:hover {
            background: #8b4513;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: <?php echo $pedido_detalhes ? '1fr 1fr' : '1fr'; ?>;
            gap: 2rem;
        }
        
        .orders-card, .details-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .orders-card h3, .details-card h3 {
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
            padding: 1.5rem;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .order-item:hover {
            border-color: #4a2c2a;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .order-info h4 {
            color: #4a2c2a;
            margin-bottom: 0.5rem;
        }
        
        .order-meta {
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
        .status-enviado { background: #cce7ff; color: #004085; }
        .status-entregue { background: #d1f7c4; color: #155724; }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 500;
            color: #4a2c2a;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-details {
            background: #3498db;
            color: white;
        }
        
        .btn-details:hover {
            background: #2980b9;
        }
        
        .btn-update {
            background: #f39c12;
            color: white;
        }
        
        .btn-update:hover {
            background: #e67e22;
        }
        
        .status-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .status-form select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-save {
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-save:hover {
            background: #219652;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .details-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .client-info, .order-summary, .items-list {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th,
        .items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table th {
            background: #e9ecef;
            color: #4a2c2a;
            font-weight: 600;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #4a2c2a;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Cabeçalho -->
        <div class="admin-header">
            <div>
                <h1>📋 Gerenciar Pedidos</h1>
                <p>Acompanhe e atualize o status dos pedidos</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Voltar ao Dashboard</a>
        </div>
        
        <!-- Navegação -->
        <nav class="admin-nav">
            <div class="admin-nav-menu">
                <a href="dashboard.php" class="admin-nav-link">📊 Dashboard</a>
                <a href="produtos.php" class="admin-nav-link">📦 Produtos</a>
                <a href="pedidos.php" class="admin-nav-link active">📋 Pedidos</a>
                <a href="usuarios.php" class="admin-nav-link">👥 Usuários</a>
                <a href="relatorios.php" class="admin-nav-link">📈 Relatórios</a>
            </div>
        </nav>
        
        <!-- Mensagens -->
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
        
        <!-- Estatísticas Rápidas -->
        <div class="stats-grid">
            <a href="pedidos.php?status=pendente" class="stat-card <?php echo $filtro_status === 'pendente' ? 'active' : ''; ?>">
                <span class="stat-number stat-pendente"><?php echo $stats_array['pendente']; ?></span>
                <span class="stat-label">⏳ Pendentes</span>
            </a>
            
            <a href="pedidos.php?status=confirmado" class="stat-card <?php echo $filtro_status === 'confirmado' ? 'active' : ''; ?>">
                <span class="stat-number stat-confirmado"><?php echo $stats_array['confirmado']; ?></span>
                <span class="stat-label">✅ Confirmados</span>
            </a>
            
            <a href="pedidos.php?status=preparando" class="stat-card <?php echo $filtro_status === 'preparando' ? 'active' : ''; ?>">
                <span class="stat-number stat-preparando"><?php echo $stats_array['preparando']; ?></span>
                <span class="stat-label">👨‍🍳 Preparando</span>
            </a>
            
            <a href="pedidos.php?status=enviado" class="stat-card <?php echo $filtro_status === 'enviado' ? 'active' : ''; ?>">
                <span class="stat-number stat-enviado"><?php echo $stats_array['enviado']; ?></span>
                <span class="stat-label">🚚 Enviados</span>
            </a>
            
            <a href="pedidos.php?status=entregue" class="stat-card <?php echo $filtro_status === 'entregue' ? 'active' : ''; ?>">
                <span class="stat-number stat-entregue"><?php echo $stats_array['entregue']; ?></span>
                <span class="stat-label">🎉 Entregues</span>
            </a>
            
            <a href="pedidos.php" class="stat-card <?php echo $filtro_status === 'todos' ? 'active' : ''; ?>">
                <span class="stat-number" style="color: #4a2c2a;"><?php echo array_sum($stats_array); ?></span>
                <span class="stat-label">📋 Todos os Pedidos</span>
            </a>
        </div>
        
        <!-- Filtros -->
        <div class="filters-card">
            <form method="get" class="filters-form">
                <div class="filter-group">
                    <label for="status">Status do Pedido</label>
                    <select id="status" name="status">
                        <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                        <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>⏳ Pendente</option>
                        <option value="confirmado" <?php echo $filtro_status === 'confirmado' ? 'selected' : ''; ?>>✅ Confirmado</option>
                        <option value="preparando" <?php echo $filtro_status === 'preparando' ? 'selected' : ''; ?>>👨‍🍳 Preparando</option>
                        <option value="enviado" <?php echo $filtro_status === 'enviado' ? 'selected' : ''; ?>>🚚 Enviado</option>
                        <option value="entregue" <?php echo $filtro_status === 'entregue' ? 'selected' : ''; ?>>🎉 Entregue</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="data">Data do Pedido</label>
                    <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($filtro_data); ?>">
                </div>
                
                <button type="submit" class="btn-filter">🔍 Filtrar</button>
                <a href="pedidos.php" class="btn-secondary">🔄 Limpar Filtros</a>
            </form>
        </div>
        
        <!-- Grid Principal -->
        <div class="orders-grid">
            <!-- Lista de Pedidos -->
            <div class="orders-card">
                <h3>📦 Pedidos (<?php echo count($pedidos); ?>)</h3>
                
                <?php if (empty($pedidos)): ?>
                    <p>Nenhum pedido encontrado com os filtros selecionados.</p>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($pedidos as $pedido): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <div class="order-info">
                                    <h4>Pedido #<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></h4>
                                    <p class="order-meta">
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?> - 
                                        <?php echo htmlspecialchars($pedido['cliente_nome']); ?>
                                    </p>
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
                            
                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">Itens</span>
                                    <span class="detail-value"><?php echo $pedido['total_itens']; ?> produtos</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Valor Total</span>
                                    <span class="detail-value">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <a href="pedidos.php?detalhes=<?php echo $pedido['id']; ?>" class="btn-action btn-details">📋 Ver Detalhes</a>
                                
                                <form method="post" class="status-form">
                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                    <select name="novo_status">
                                        <option value="pendente" <?php echo $pedido['status'] === 'pendente' ? 'selected' : ''; ?>>⏳ Pendente</option>
                                        <option value="confirmado" <?php echo $pedido['status'] === 'confirmado' ? 'selected' : ''; ?>>✅ Confirmado</option>
                                        <option value="preparando" <?php echo $pedido['status'] === 'preparando' ? 'selected' : ''; ?>>👨‍🍳 Preparando</option>
                                        <option value="enviado" <?php echo $pedido['status'] === 'enviado' ? 'selected' : ''; ?>>🚚 Enviado</option>
                                        <option value="entregue" <?php echo $pedido['status'] === 'entregue' ? 'selected' : ''; ?>>🎉 Entregue</option>
                                    </select>
                                    <button type="submit" name="atualizar_status" class="btn-save">💾</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Detalhes do Pedido -->
            <?php if ($pedido_detalhes): ?>
            <div class="details-card">
                <a href="pedidos.php" class="back-link">← Voltar para lista</a>
                <h3>📄 Detalhes do Pedido #<?php echo str_pad($pedido_detalhes['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                
                <div class="details-content">
                    <!-- Informações do Cliente -->
                    <div class="client-info">
                        <h4>👤 Informações do Cliente</h4>
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($pedido_detalhes['cliente_nome']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido_detalhes['email']); ?></p>
                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($pedido_detalhes['telefone'] ?? 'Não informado'); ?></p>
                    </div>
                    
                    <!-- Resumo do Pedido -->
                    <div class="order-summary">
                        <h4>📦 Resumo do Pedido</h4>
                        <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido_detalhes['data_pedido'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="order-status status-<?php echo $pedido_detalhes['status']; ?>">
                                <?php 
                                $status_text = [
                                    'pendente' => '⏳ Pendente',
                                    'confirmado' => '✅ Confirmado', 
                                    'preparando' => '👨‍🍳 Preparando',
                                    'enviado' => '🚚 Enviado',
                                    'entregue' => '🎉 Entregue'
                                ];
                                echo $status_text[$pedido_detalhes['status']];
                                ?>
                            </span>
                        </p>
                        <p><strong>Total:</strong> R$ <?php echo number_format($pedido_detalhes['total'], 2, ',', '.'); ?></p>
                    </div>
                    
                    <!-- Itens do Pedido -->
                    <div class="items-list">
                        <h4>🛒 Itens do Pedido</h4>
                        <?php if (empty($itens_pedido)): ?>
                            <p>Nenhum item encontrado.</p>
                        <?php else: ?>
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Quantidade</th>
                                        <th>Preço Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens_pedido as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['produto_nome']); ?></td>
                                        <td><?php echo $item['quantidade']; ?></td>
                                        <td>R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>