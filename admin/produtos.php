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

// Processar ações (adicionar, editar, excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar_produto'])) {
        // Adicionar novo produto
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['preco'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        $imagem = 'placeholder.jpg'; // Por enquanto usamos placeholder
        
        if (empty($nome) || empty($preco) || empty($categoria)) {
            $erro = "Preencha todos os campos obrigatórios!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, categoria, imagem) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nome, $descricao, $preco, $categoria, $imagem])) {
                $mensagem = "Produto adicionado com sucesso!";
            } else {
                $erro = "Erro ao adicionar produto!";
            }
        }
    }
    
    if (isset($_POST['editar_produto'])) {
        // Editar produto existente
        $id = $_POST['produto_id'] ?? '';
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['preco'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        
        if (empty($nome) || empty($preco) || empty($categoria)) {
            $erro = "Preencha todos os campos obrigatórios!";
        } else {
            $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, categoria = ? WHERE id = ?");
            if ($stmt->execute([$nome, $descricao, $preco, $categoria, $id])) {
                $mensagem = "Produto atualizado com sucesso!";
            } else {
                $erro = "Erro ao atualizar produto!";
            }
        }
    }
    
    if (isset($_POST['excluir_produto'])) {
        // Excluir produto
        $id = $_POST['produto_id'] ?? '';
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        if ($stmt->execute([$id])) {
            $mensagem = "Produto excluído com sucesso!";
        } else {
            $erro = "Erro ao excluir produto!";
        }
    }
}

// Buscar todos os produtos
$stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar produto específico para edição
$produto_edicao = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $produto_edicao = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - Admin</title>
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
        
        .admin-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .form-card, .list-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-card h3, .list-card h3 {
            color: #4a2c2a;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a2c2a;
            font-weight: 500;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #4a2c2a;
            outline: none;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-primary {
            background: #4a2c2a;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #8b4513;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .products-table th {
            background: #f8f9fa;
            color: #4a2c2a;
            font-weight: 600;
        }
        
        .products-table tr:hover {
            background: #f8f9fa;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #8b4513, #d2691e);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .category-tradicional { background: #e3f2fd; color: #1565c0; }
        .category-especial { background: #f3e5f5; color: #7b1fa2; }
        .category-gelado { background: #e8f5e8; color: #2e7d32; }
        
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
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Cabeçalho -->
        <div class="admin-header">
            <div>
                <h1>📦 Gerenciar Produtos</h1>
                <p>Adicione, edite ou remove produtos do catálogo</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Voltar ao Dashboard</a>
        </div>
        
        <!-- Navegação -->
        <nav class="admin-nav">
            <div class="admin-nav-menu">
                <a href="dashboard.php" class="admin-nav-link">📊 Dashboard</a>
                <a href="produtos.php" class="admin-nav-link active">📦 Produtos</a>
                <a href="pedidos.php" class="admin-nav-link">📋 Pedidos</a>
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
        
        <!-- Conteúdo Principal -->
        <div class="admin-content">
            <!-- Formulário -->
            <div class="form-card">
                <h3><?php echo $produto_edicao ? '✏️ Editar Produto' : '➕ Adicionar Produto'; ?></h3>
                
                <form method="post">
                    <?php if ($produto_edicao): ?>
                        <input type="hidden" name="produto_id" value="<?php echo $produto_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="nome">Nome do Produto *</label>
                        <input type="text" id="nome" name="nome" required 
                               value="<?php echo htmlspecialchars($produto_edicao['nome'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($produto_edicao['descricao'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="preco">Preço (R$) *</label>
                        <input type="text" id="preco" name="preco" required 
                               value="<?php echo isset($produto_edicao['preco']) ? number_format($produto_edicao['preco'], 2, ',', '.') : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoria *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecione uma categoria</option>
                            <option value="Tradicional" <?php echo ($produto_edicao['categoria'] ?? '') === 'Tradicional' ? 'selected' : ''; ?>>Tradicional</option>
                            <option value="Especial" <?php echo ($produto_edicao['categoria'] ?? '') === 'Especial' ? 'selected' : ''; ?>>Especial</option>
                            <option value="Gelado" <?php echo ($produto_edicao['categoria'] ?? '') === 'Gelado' ? 'selected' : ''; ?>>Gelado</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <?php if ($produto_edicao): ?>
                            <button type="submit" name="editar_produto" class="btn-primary">💾 Atualizar Produto</button>
                            <a href="produtos.php" class="btn-secondary">❌ Cancelar</a>
                        <?php else: ?>
                            <button type="submit" name="adicionar_produto" class="btn-primary">➕ Adicionar Produto</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Lista de Produtos -->
            <div class="list-card">
                <h3>📋 Todos os Produtos (<?php echo count($produtos); ?>)</h3>
                
                <?php if (empty($produtos)): ?>
                    <p>Nenhum produto cadastrado.</p>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div class="product-image">☕</div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($produto['nome']); ?></strong>
                                            <br>
                                            <small style="color: #666;"><?php echo htmlspecialchars($produto['descricao']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-badge category-<?php echo strtolower($produto['categoria']); ?>">
                                        <?php echo htmlspecialchars($produto['categoria']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></strong>
                                </td>
                                <td>
                                    <div class="product-actions">
                                        <a href="produtos.php?editar=<?php echo $produto['id']; ?>" class="btn-edit">✏️ Editar</a>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                                            <button type="submit" name="excluir_produto" class="btn-danger" 
                                                    onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                🗑️ Excluir
                                            </button>
                                        </form>
                                    </div>
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
    // Formatação do preço
    document.getElementById('preco')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (value / 100).toFixed(2);
        value = value.replace('.', ',');
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        e.target.value = value ? 'R$ ' + value : '';
    });
    </script>
</body>
</html>