<?php
session_start();

setcookie('auth_token', '', time() - 3600, '/');

session_destroy();

header("Location: login.php");
exit();
?>