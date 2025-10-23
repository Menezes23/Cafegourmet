<?php
class Config {
    public static function getPDO() {
        // SEMPRE usar o banco do JawsDB quando JAWSDB_URL existir
        $jawsdb_url = getenv("JAWSDB_URL");
        
        if ($jawsdb_url) {
            // PRODUÇÃO - Heroku + JawsDB
            $url = parse_url($jawsdb_url);
            $host = $url["host"];
            $dbname = substr($url["path"], 1); // ayoejwl46jt74b86
            $username = $url["user"];
            $password = $url["pass"];
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
        } else {
            // DESENVOLVIMENTO - Local
            $host = 'localhost';
            $dbname = 'cafe_db';
            $username = 'root';
            $password = '';
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
        }
        
        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erro no banco de dados: " . $e->getMessage());
        }
    }
}
?>