<?php
session_start();

// Limpar sessão admin
unset($_SESSION['admin_logado']);
unset($_SESSION['admin_usuario']);

// Redirecionar para login
header('Location: index.php');
exit;
?>