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

// Período padrão (últimos 30 dias)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Estatísticas Gerais
$stmt_geral = $pdo->prepare("
    SELECT 
        COUNT(*) as total_pedidos,
        COALESCE(SUM(total), 0) as faturamento_total,
        AVG(total) as ticket_medio,
        COUNT(DISTINCT usuario_id) as clientes_ativos
    FROM pedidos 
    WHERE DATE(data_pedido) BETWEEN ? AND ?
");
$stmt_geral->execute([$data_inicio, $data_fim]);
$estatisticas = $stmt_geral->fetch(PDO::FETCH_ASSOC);

// Vendas por Dia (para gráfico)
$stmt_vendas_dia = $pdo->prepare("
    SELECT 
        DATE(data_pedido) as data,
        COUNT(*) as total_pedidos,
        COALESCE(SUM(total), 0) as faturamento
    FROM pedidos 
    WHERE DATE(data_pedido) BETWEEN ? AND ?
    GROUP BY DATE(data_pedido)
    ORDER BY data
");
$stmt_vendas_dia->execute([$data_inicio, $data_fim]);
$vendas_por_dia = $stmt_vendas_dia->fetchAll(PDO::FETCH_ASSOC);

// Produtos Mais Vendidos
$stmt_produtos_vendidos = $pdo->prepare("
    SELECT 
        p.nome as produto_nome,
        p.categoria,
        SUM(pi.quantidade) as total_vendido,
        SUM(pi.quantidade * pi.preco_unitario) as faturamento
    FROM pedido_itens pi
    JOIN produtos p ON pi.produto_id = p.id
    JOIN pedidos pd ON pi.pedido_id = pd.id
    WHERE DATE(pd.data_pedido) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 10
");
$stmt_produtos_vendidos->execute([$data_inicio, $data_fim]);
$produtos_mais_vendidos = $stmt_produtos_vendidos->fetchAll(PDO::FETCH_ASSOC);

// Status dos Pedidos
$stmt_status_pedidos = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as total
    FROM pedidos 
    WHERE DATE(data_pedido) BETWEEN ? AND ?
    GROUP BY status
");
$stmt_status_pedidos->execute([$data_inicio, $data_fim]);
$status_pedidos = $stmt_status_pedidos->fetchAll(PDO::FETCH_ASSOC);

// Clientes que Mais Compram
$stmt_melhores_clientes = $pdo->prepare("
    SELECT 
        u.nome as cliente_nome,
        u.email,
        COUNT(p.id) as total_pedidos,
        COALESCE(SUM(p.total), 0) as total_gasto
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE DATE(p.data_pedido) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_gasto DESC
    LIMIT 10
");
$stmt_melhores_clientes->execute([$data_inicio, $data_fim]);
$melhores_clientes = $stmt_melhores_clientes->fetchAll(PDO::FETCH_ASSOC);

// Preparar dados para gráficos
$dias = [];
$vendas_dia = [];
$pedidos_dia = [];

foreach ($vendas_por_dia as $venda) {
    $dias[] = date('d/m', strtotime($venda['data']));
    $vendas_dia[] = floatval($venda['faturamento']);
    $pedidos_dia[] = intval($venda['total_pedidos']);
}

$status_labels = [];
$status_values = [];

foreach ($status_pedidos as $status) {
    $status_labels[] = ucfirst($status['status']);
    $status_values[] = intval($status['total']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .filter-group input {
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
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .chart-card h3 {
            color: #4a2c2a;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .table-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .table-card h3 {
            color: #4a2c2a;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .data-table th {
            background: #f8f9fa;
            color: #4a2c2a;
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #4a2c2a;
            border-radius: 4px;
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
        
        .period-info {
            background: #e8f5e8;
            border: 1px solid #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Cabeçalho -->
        <div class="admin-header">
            <div>
                <h1>📈 Relatórios e Estatísticas</h1>
                <p>Análises detalhadas do desempenho da loja</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Voltar ao Dashboard</a>
        </div>
        
        <!-- Navegação -->
        <nav class="admin-nav">
            <div class="admin-nav-menu">
                <a href="dashboard.php" class="admin-nav-link">📊 Dashboard</a>
                <a href="produtos.php" class="admin-nav-link">📦 Produtos</a>
                <a href="pedidos.php" class="admin-nav-link">📋 Pedidos</a>
                <a href="usuarios.php" class="admin-nav-link">👥 Usuários</a>
                <a href="relatorios.php" class="admin-nav-link active">📈 Relatórios</a>
            </div>
        </nav>
        
        <!-- Filtros de Período -->
        <div class="filters-card">
            <form method="get" class="filters-form">
                <div class="filter-group">
                    <label for="data_inicio">Data Início</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="data_fim">Data Fim</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
                </div>
                
                <button type="submit" class="btn-filter">🔍 Atualizar Relatórios</button>
                <a href="relatorios.php" class="btn-secondary">🔄 Últimos 30 Dias</a>
            </form>
        </div>
        
        <!-- Informação do Período -->
        <div class="period-info">
            📊 Relatório do período: 
            <strong><?php echo date('d/m/Y', strtotime($data_inicio)); ?></strong> a 
            <strong><?php echo date('d/m/Y', strtotime($data_fim)); ?></strong>
        </div>
        
        <!-- Estatísticas Principais -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <span class="stat-number"><?php echo $estatisticas['total_pedidos']; ?></span>
                <span class="stat-label">Total de Pedidos</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <span class="stat-number">R$ <?php echo number_format($estatisticas['faturamento_total'], 2, ',', '.'); ?></span>
                <span class="stat-label">Faturamento Total</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🎫</div>
                <span class="stat-number">R$ <?php echo number_format($estatisticas['ticket_medio'], 2, ',', '.'); ?></span>
                <span class="stat-label">Ticket Médio</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <span class="stat-number"><?php echo $estatisticas['clientes_ativos']; ?></span>
                <span class="stat-label">Clientes Ativos</span>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="charts-grid">
            <!-- Gráfico de Vendas por Dia -->
            <div class="chart-card">
                <h3>📈 Vendas por Dia</h3>
                <div class="chart-container">
                    <canvas id="vendasChart"></canvas>
                </div>
            </div>
            
            <!-- Gráfico de Status dos Pedidos -->
            <div class="chart-card">
                <h3>📊 Status dos Pedidos</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabelas de Dados -->
        <div class="tables-grid">
            <!-- Produtos Mais Vendidos -->
            <div class="table-card">
                <h3>🏆 Produtos Mais Vendidos</h3>
                
                <?php if (empty($produtos_mais_vendidos)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <p>Nenhum dado de vendas no período</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Vendidos</th>
                                <th>Faturamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos_mais_vendidos as $produto): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($produto['produto_nome']); ?></strong>
                                </td>
                                <td>
                                    <span style="padding: 0.25rem 0.5rem; background: #e9ecef; border-radius: 4px; font-size: 0.8rem;">
                                        <?php echo htmlspecialchars($produto['categoria']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span><?php echo $produto['total_vendido']; ?></span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min(($produto['total_vendido'] / $produtos_mais_vendidos[0]['total_vendido']) * 100, 100); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>R$ <?php echo number_format($produto['faturamento'], 2, ',', '.'); ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Melhores Clientes -->
            <div class="table-card">
                <h3>⭐ Melhores Clientes</h3>
                
                <?php if (empty($melhores_clientes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <p>Nenhum dado de clientes no período</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Pedidos</th>
                                <th>Total Gasto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($melhores_clientes as $cliente): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($cliente['cliente_nome']); ?></strong>
                                        <br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($cliente['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo $cliente['total_pedidos']; ?></td>
                                <td>
                                    <strong>R$ <?php echo number_format($cliente['total_gasto'], 2, ',', '.'); ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Gráfico de Vendas por Dia
    const vendasCtx = document.getElementById('vendasChart').getContext('2d');
    const vendasChart = new Chart(vendasCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dias); ?>,
            datasets: [
                {
                    label: 'Faturamento (R$)',
                    data: <?php echo json_encode($vendas_dia); ?>,
                    borderColor: '#4a2c2a',
                    backgroundColor: 'rgba(74, 44, 42, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Pedidos',
                    data: <?php echo json_encode($pedidos_dia); ?>,
                    borderColor: '#8b4513',
                    backgroundColor: 'rgba(139, 69, 19, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Status dos Pedidos
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_values); ?>,
                backgroundColor: [
                    '#ffc107', // pendente - amarelo
                    '#17a2b8', // confirmado - azul
                    '#28a745', // preparando - verde
                    '#6f42c1', // enviado - roxo
                    '#20c997'  // entregue - verde água
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>
</html>