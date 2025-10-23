<?php
class Config {
    public static function getPDO() {
        // CONEXÃO SIMPLES E DIRETA
        $host = 'qf5dic2wzyjf1x5x.cbetxkdyhwsb.us-east-1.rds.amazonaws.com';
        $dbname = 'ayoejwl46jt74b86';
        $username = 'iu3kw1zuhvu4jz3z';
        $password = 'ik7r445n7y4kn34b';
        $port = 3306;
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        
        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }
}
?>