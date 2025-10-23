<?php
session_start();

// Limpar todas as variáveis de sessão
$_SESSION = [];

// Destruir a sessão
session_destroy();

// Redirecionar para a página inicial
header('Location: index.php');
exit;
?>