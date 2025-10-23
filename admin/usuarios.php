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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ativar_usuario'])) {
        $usuario_id = $_POST['usuario_id'] ?? '';
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 WHERE id = ?");
        if ($stmt->execute([$usuario_id])) {
            $mensagem = "Usuário ativado com sucesso!";
        } else {
            $erro = "Erro ao ativar usuário!";
        }
    }
    
    if (isset($_POST['desativar_usuario'])) {
        $usuario_id = $_POST['usuario_id'] ?? '';
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
        if ($stmt->execute([$usuario_id])) {
            $mensagem = "Usuário desativado com sucesso!";
        } else {
            $erro = "Erro ao desativar usuário!";
        }
    }
    
    if (isset($_POST['excluir_usuario'])) {
        $usuario_id = $_POST['usuario_id'] ?? '';
        
        // Verificar se o usuário tem pedidos antes de excluir
        $stmt_pedidos = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ?");
        $stmt_pedidos->execute([$usuario_id]);
        $total_pedidos = $stmt_pedidos->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($total_pedidos > 0) {
            $erro = "Não é possível excluir usuário com pedidos associados!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            if ($stmt->execute([$usuario_id])) {
                $mensagem = "Usuário excluído com sucesso!";
            } else {
                $erro = "Erro ao excluir usuário!";
            }
        }
    }
}

// Filtros
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_busca = $_GET['busca'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($filtro_status !== 'todos') {
    if ($filtro_status === 'ativo') {
        $where_conditions[] = "ativo = 1";
    } elseif ($filtro_status === 'inativo') {
        $where_conditions[] = "ativo = 0";
    }
}

if ($filtro_busca) {
    $where_conditions[] = "(nome LIKE ? OR email LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Buscar usuários
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(p.id) as total_pedidos,
           COALESCE(SUM(p.total), 0) as total_gasto
    FROM usuarios u 
    LEFT JOIN pedidos p ON u.id = p.usuario_id 
    $where_sql
    GROUP BY u.id 
    ORDER BY u.data_cadastro DESC
");
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar detalhes de um usuário específico
$usuario_detalhes = null;
$pedidos_usuario = [];
if (isset($_GET['detalhes'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_GET['detalhes']]);
    $usuario_detalhes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario_detalhes) {
        $stmt_pedidos = $pdo->prepare("
            SELECT * FROM pedidos 
            WHERE usuario_id = ? 
            ORDER BY data_pedido DESC
        ");
        $stmt_pedidos->execute([$_GET['detalhes']]);
        $pedidos_usuario = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Estatísticas
$stmt_stats = $pdo->query("
    SELECT 
        COUNT(*) as total_usuarios,
        SUM(ativo = 1) as usuarios_ativos,
        SUM(ativo = 0) as usuarios_inativos,
        (SELECT COUNT(DISTINCT usuario_id) FROM pedidos) as usuarios_com_pedidos
    FROM usuarios
");
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>
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
        
        .users-grid {
            display: grid;
            grid-template-columns: <?php echo $usuario_detalhes ? '2fr 1fr' : '1fr'; ?>;
            gap: 2rem;
        }
        
        .users-card, .details-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .users-card h3, .details-card h3 {
            color: #4a2c2a;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .users-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .user-item {
            padding: 1.5rem;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .user-item:hover {
            border-color: #4a2c2a;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .user-info h4 {
            color: #4a2c2a;
            margin-bottom: 0.5rem;
        }
        
        .user-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .user-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .status-ativo { background: #d4edda; color: #155724; }
        .status-inativo { background: #f8d7da; color: #721c24; }
        
        .user-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        
        .stat-label-small {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-weight: 500;
            color: #4a2c2a;
        }
        
        .user-actions {
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
        
        .btn-ativar {
            background: #27ae60;
            color: white;
        }
        
        .btn-ativar:hover {
            background: #219652;
        }
        
        .btn-desativar {
            background: #e74c3c;
            color: white;
        }
        
        .btn-desativar:hover {
            background: #c0392b;
        }
        
        .btn-excluir {
            background: #95a5a6;
            color: white;
        }
        
        .btn-excluir:hover {
            background: #7f8c8d;
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
        
        .user-info-card, .user-orders {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 500;
            color: #4a2c2a;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
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
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Cabeçalho -->
        <div class="admin-header">
            <div>
                <h1>👥 Gerenciar Usuários</h1>
                <p>Gerencie os clientes cadastrados no sistema</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Voltar ao Dashboard</a>
        </div>
        
        <!-- Navegação -->
        <nav class="admin-nav">
            <div class="admin-nav-menu">
                <a href="dashboard.php" class="admin-nav-link">📊 Dashboard</a>
                <a href="produtos.php" class="admin-nav-link">📦 Produtos</a>
                <a href="pedidos.php" class="admin-nav-link">📋 Pedidos</a>
                <a href="usuarios.php" class="admin-nav-link active">👥 Usuários</a>
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
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <span class="stat-number"><?php echo $stats['total_usuarios']; ?></span>
                <span class="stat-label">Total de Usuários</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <span class="stat-number"><?php echo $stats['usuarios_ativos']; ?></span>
                <span class="stat-label">Usuários Ativos</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏸️</div>
                <span class="stat-number"><?php echo $stats['usuarios_inativos']; ?></span>
                <span class="stat-label">Usuários Inativos</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🛒</div>
                <span class="stat-number"><?php echo $stats['usuarios_com_pedidos']; ?></span>
                <span class="stat-label">Com Pedidos</span>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-card">
            <form method="get" class="filters-form">
                <div class="filter-group">
                    <label for="status">Status do Usuário</label>
                    <select id="status" name="status">
                        <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos os Usuários</option>
                        <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>✅ Ativos</option>
                        <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>⏸️ Inativos</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="busca">Buscar Usuário</label>
                    <input type="text" id="busca" name="busca" placeholder="Nome ou email..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                </div>
                
                <button type="submit" class="btn-filter">🔍 Filtrar</button>
                <a href="usuarios.php" class="btn-secondary">🔄 Limpar Filtros</a>
            </form>
        </div>
        
        <!-- Grid Principal -->
        <div class="users-grid">
            <!-- Lista de Usuários -->
            <div class="users-card">
                <h3>👤 Lista de Usuários (<?php echo count($usuarios); ?>)</h3>
                
                <?php if (empty($usuarios)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>Nenhum usuário encontrado</h3>
                        <p>Tente ajustar os filtros de busca</p>
                    </div>
                <?php else: ?>
                    <div class="users-list">
                        <?php foreach ($usuarios as $usuario): ?>
                        <div class="user-item">
                            <div class="user-header">
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                                    <p class="user-meta">
                                        <?php echo htmlspecialchars($usuario['email']); ?> • 
                                        Cadastro: <?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?>
                                    </p>
                                </div>
                                <span class="user-status status-<?php echo $usuario['ativo'] ? 'ativo' : 'inativo'; ?>">
                                    <?php echo $usuario['ativo'] ? '✅ Ativo' : '⏸️ Inativo'; ?>
                                </span>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <span class="stat-label-small">Total de Pedidos</span>
                                    <span class="stat-value"><?php echo $usuario['total_pedidos']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label-small">Total Gasto</span>
                                    <span class="stat-value">R$ <?php echo number_format($usuario['total_gasto'], 2, ',', '.'); ?></span>
                                </div>
                            </div>
                            
                            <div class="user-actions">
                                <a href="usuarios.php?detalhes=<?php echo $usuario['id']; ?>" class="btn-action btn-details">📋 Ver Detalhes</a>
                                
                                <?php if ($usuario['ativo']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                        <button type="submit" name="desativar_usuario" class="btn-action btn-desativar" 
                                                onclick="return confirm('Tem certeza que deseja desativar este usuário?')">
                                            ⏸️ Desativar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                        <button type="submit" name="ativar_usuario" class="btn-action btn-ativar">
                                            ✅ Ativar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" name="excluir_usuario" class="btn-action btn-excluir" 
                                            onclick="return confirm('Tem certeza que deseja EXCLUIR este usuário? Esta ação não pode ser desfeita!')">
                                        🗑️ Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Detalhes do Usuário -->
            <?php if ($usuario_detalhes): ?>
            <div class="details-card">
                <a href="usuarios.php" class="back-link">← Voltar para lista</a>
                <h3>📄 Detalhes do Usuário</h3>
                
                <div class="details-content">
                    <!-- Informações Pessoais -->
                    <div class="user-info-card">
                        <h4>👤 Informações Pessoais</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Nome Completo</span>
                                <span class="info-value"><?php echo htmlspecialchars($usuario_detalhes['nome']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($usuario_detalhes['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Telefone</span>
                                <span class="info-value"><?php echo htmlspecialchars($usuario_detalhes['telefone'] ?? 'Não informado'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Data de Cadastro</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario_detalhes['data_cadastro'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span class="user-status status-<?php echo $usuario_detalhes['ativo'] ? 'ativo' : 'inativo'; ?>">
                                        <?php echo $usuario_detalhes['ativo'] ? '✅ Ativo' : '⏸️ Inativo'; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Última Atualização</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario_detalhes['data_atualizacao'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pedidos do Usuário -->
                    <div class="user-orders">
                        <h4>🛒 Histórico de Pedidos (<?php echo count($pedidos_usuario); ?>)</h4>
                        
                        <?php if (empty($pedidos_usuario)): ?>
                            <p>Este usuário ainda não realizou nenhum pedido.</p>
                        <?php else: ?>
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Pedido</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos_usuario as $pedido): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                        <td>
                                            <span class="user-status status-<?php echo $pedido['status']; ?>">
                                                <?php 
                                                $status_text = [
                                                    'pendente' => '⏳',
                                                    'confirmado' => '✅', 
                                                    'preparando' => '👨‍🍳',
                                                    'enviado' => '🚚',
                                                    'entregue' => '🎉'
                                                ];
                                                echo $status_text[$pedido['status']];
                                                ?>
                                            </span>
                                        </td>
                                        <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
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