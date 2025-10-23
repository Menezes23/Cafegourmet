<?php
// Arquivo: config.php
// Configurações do banco de dados Café Gourmet

class Config {
    const DB_HOST = 'localhost';
    const DB_NAME = 'cafe_db';
    const DB_USER = 'root';
    const DB_PASS = ''; // Deixe vazio se não tiver senha
    
    public static function getPDO() {
        try {
            $pdo = new PDO(
                'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME . ';charset=utf8',
                self::DB_USER,
                self::DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erro na conexão com o banco: " . $e->getMessage());
        }
    }
}
?>