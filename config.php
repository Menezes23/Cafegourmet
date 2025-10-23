<?php
class Config {
    public static function getPDO() {
        // Verificar se estamos no Heroku com JawsDB
        $jawsdb_url = getenv("JAWSDB_URL");
        
        if ($jawsdb_url) {
            // PRODUÇÃO (Heroku + JawsDB) - USA O BANCO DO JAWSDB
            $url = parse_url($jawsdb_url);
            $host = $url["host"];
            $dbname = substr($url["path"], 1); // Isso pega 'ayoejwl46jt74b86'
            $username = $url["user"];
            $password = $url["pass"];
            $port = isset($url["port"]) ? $url["port"] : 3306;
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        } else {
            // DESENVOLVIMENTO (Local) - USA cafe_db
            $host = 'localhost';
            $dbname = 'cafe_db';
            $username = 'root';
            $password = '';
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
        }
        
        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            $error_msg = "Erro na conexão com o banco: " . $e->getMessage();
            $error_msg .= " | Database: " . $dbname;
            die($error_msg);
        }
    }
}
?>