-- BANCO DE DADOS CAFÉ GOURMET
-- Arquivo: database.sql

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS cafe_db;
USE cafe_db;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(50),
    imagem VARCHAR(255),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pendente', 'confirmado', 'preparando', 'enviado', 'entregue') DEFAULT 'pendente',
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    endereco_entrega TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de itens do pedido
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    produto_id INT,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dados iniciais
INSERT IGNORE INTO usuarios (nome, email, senha_hash, telefone) VALUES 
('João Silva', 'joao@teste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(11) 99999-9999');

INSERT IGNORE INTO produtos (nome, descricao, preco, categoria, imagem) VALUES
('Café Expresso', 'Café puro e intenso', 4.90, 'Tradicional', 'expresso.jpg'),
('Cappuccino', 'Café com leite vaporizado e espuma', 8.90, 'Especial', 'cappuccino.jpg'),
('Latte Macchiato', 'Leite vaporizado com café', 9.90, 'Especial', 'latte.jpg'),
('Mocha', 'Chocolate com café e leite', 11.90, 'Especial', 'mocha.jpg'),
('Café Gelado', 'Café espresso gelado', 6.90, 'Gelado', 'gelado.jpg');