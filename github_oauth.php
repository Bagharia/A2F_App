<?php
require_once 'config.php';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'read:user user:email', 
    'state' => $state
];

$auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query($params);

header("Location: $auth_url");
exit();
?>