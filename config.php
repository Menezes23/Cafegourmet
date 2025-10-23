<?php
class Config {
    public static function getPDO() {
        // DEBUG - Mostrar qual banco está tentando conectar
        $jawsdb_url = getenv("JAWSDB_URL");
        
        if ($jawsdb_url) {
            $url = parse_url($jawsdb_url);
            $dbname = substr($url["path"], 1);
            echo "DEBUG: Tentando conectar no banco: " . $dbname . "<br>";
            echo "DEBUG: JAWSDB_URL existe: SIM<br>";
        } else {
            echo "DEBUG: JAWSDB_URL existe: NÃO - Usando local<br>";
            $dbname = 'cafe_db';
        }
        
        // Resto do código continua igual...
        if ($jawsdb_url) {
            $url = parse_url($jawsdb_url);
            $host = $url["host"];
            $dbname = substr($url["path"], 1);
            $username = $url["user"];
            $password = $url["pass"];
            $port = isset($url["port"]) ? $url["port"] : 3306;
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        } else {
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
            die("Erro na conexão: " . $e->getMessage() . " | Banco: " . $dbname);
        }
    }
}
?>