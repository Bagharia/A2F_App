<?php
require_once 'config.php';

// Générer un état aléatoire pour sécuriser la requête (protection CSRF)
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Paramètres de l'autorisation
$params = [
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'read:user user:email',  // Permissions demandées
    'state' => $state
];

// URL d'autorisation GitHub
$auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query($params);

// Rediriger vers GitHub
header("Location: $auth_url");
exit();
?>