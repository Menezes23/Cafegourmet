<?php
class Config {
    public static function getPDO() {
        // Tenta MySQL/JawsDB primeiro (se existir)
        $jawsdb_url = getenv("JAWSDB_URL");
        
        if ($jawsdb_url) {
            try {
                $url = parse_url($jawsdb_url);
                $host = $url["host"];
                $dbname = substr($url["path"], 1);
                $username = $url["user"];
                $password = $url["pass"];
                $port = isset($url["port"]) ? $url["port"] : 3306;
                
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
                $pdo = new PDO($dsn, $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Verificar se as tabelas existem no MySQL
                self::createTablesIfNotExists($pdo, 'mysql');
                return $pdo;
                
            } catch (PDOException $e) {
                // Se MySQL falhar, continua para SQLite
                error_log("MySQL falhou, usando SQLite: " . $e->getMessage());
            }
        }
        
        // SQLITE (FALLBACK - SEMPRE FUNCIONA)
        $dbPath = __DIR__ . '/database.sqlite';
        $dsn = "sqlite:" . $dbPath;
        
        try {
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar tabelas no SQLite
            self::createTablesIfNotExists($pdo, 'sqlite');
            
            return $pdo;
        } catch (PDOException $e) {
            die("Erro no banco de dados: " . $e->getMessage());
        }
    }
    
    private static function createTablesIfNotExists($pdo, $dbType) {
        // Criar tabela usuarios
        if ($dbType === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                senha_hash VARCHAR(255) NOT NULL,
                telefone VARCHAR(20),
                data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ativo TINYINT DEFAULT 1
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                senha_hash TEXT NOT NULL,
                telefone TEXT,
                data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
                ativo INTEGER DEFAULT 1
            )");
        }
        
        // Criar tabela produtos
        if ($dbType === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                descricao TEXT,
                preco DECIMAL(10,2) NOT NULL,
                categoria VARCHAR(50),
                imagem VARCHAR(255),
                data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                descricao TEXT,
                preco REAL NOT NULL,
                categoria TEXT,
                imagem TEXT,
                data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Inserir dados iniciais
        self::insertInitialData($pdo);
    }
    
    private static function insertInitialData($pdo) {
        // Inserir usuário de teste
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO usuarios (nome, email, senha_hash, telefone) VALUES (?, ?, ?, ?)");
        $stmt->execute(['João Silva', 'joao@teste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(11) 99999-9999']);
        
        // Inserir produtos
        $produtos = [
            ['Café Expresso', 'Café puro e intenso', 4.90, 'Tradicional', 'expresso.jpg'],
            ['Cappuccino', 'Café com leite vaporizado e espuma', 8.90, 'Especial', 'cappuccino.jpg'],
            ['Latte Macchiato', 'Leite vaporizado com café', 9.90, 'Especial', 'latte.jpg'],
            ['Mocha', 'Chocolate com café e leite', 11.90, 'Especial', 'mocha.jpg'],
            ['Café Gelado', 'Café espresso gelado', 6.90, 'Gelado', 'gelado.jpg']
        ];
        
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO produtos (nome, descricao, preco, categoria, imagem) VALUES (?, ?, ?, ?, ?)");
        foreach ($produtos as $produto) {
            $stmt->execute($produto);
        }
    }
}
?>