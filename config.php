<?php
class Config {
    public static function getPDO() {
        // Configuração para Heroku + JawsDB
        if (getenv("JAWSDB_URL")) {
            // Produção no Heroku
            $url = parse_url(getenv("JAWSDB_URL"));
            $host = $url["host"];
            $db   = substr($url["path"], 1);
            $user = $url["user"]; 
            $pass = $url["pass"];
        } else {
            // Desenvolvimento local
            $host = 'localhost';
            $db   = 'cafe_db';
            $user = 'root';
            $pass = '';
        }
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erro na conexão com o banco: " . $e->getMessage());
        }
    }
}
?>