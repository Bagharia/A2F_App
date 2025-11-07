<?php
session_start();

// Supprimer le cookie JWT
setcookie('auth_token', '', time() - 3600, '/');

// Détruire la session
session_destroy();

// Rediriger
header("Location: login.php");
exit();
?>