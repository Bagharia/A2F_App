<?php
require_once 'config.php';

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>✅ Le fichier github_callback.php fonctionne !</h2>";

// Vérifier si on a reçu un code
if (!isset($_GET['code'])) {
    die("<p style='color:red;'>❌ Erreur : Aucun code reçu</p>");
}

// Vérifier le state (protection CSRF)
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die("<p style='color:red;'>❌ Erreur : State invalide</p>");
}

// Supprimer le state
unset($_SESSION['oauth_state']);

$code = $_GET['code'];

echo "<p>✅ Code reçu : " . htmlspecialchars($code) . "</p>";

// ==========================================
// ÉTAPE 1 : Échanger le code contre un token
// ==========================================

$token_url = 'https://github.com/login/oauth/access_token';
$token_params = [
    'client_id' => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => GITHUB_REDIRECT_URI
];

echo "<p>🔄 Échange du code contre un token...</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die("<p style='color:red;'>❌ Erreur cURL : " . curl_error($ch) . "</p>");
}

curl_close($ch);

if ($http_code !== 200) {
    die("<p style='color:red;'>❌ Erreur HTTP $http_code : $response</p>");
}

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    echo "<pre>❌ Erreur lors de l'obtention du token :\n";
    print_r($token_data);
    echo "</pre>";
    exit();
}

$access_token = $token_data['access_token'];
echo "<p>✅ Token d'accès reçu !</p>";

// ==========================================
// ÉTAPE 2 : Récupérer les infos utilisateur
// ==========================================

echo "<p>🔄 Récupération des infos utilisateur...</p>";

$user_url = 'https://api.github.com/user';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'User-Agent: PHP-OAuth-App'
]);

$user_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die("<p style='color:red;'>❌ Erreur cURL : " . curl_error($ch) . "</p>");
}

curl_close($ch);

if ($http_code !== 200) {
    die("<p style='color:red;'>❌ Erreur HTTP $http_code lors de la récupération des données</p>");
}

$user_data = json_decode($user_response, true);

if (!isset($user_data['login'])) {
    echo "<pre>❌ Erreur lors de la récupération des données :\n";
    print_r($user_data);
    echo "</pre>";
    exit();
}

echo "<p>✅ Données utilisateur récupérées !</p>";
echo "<pre>";
print_r($user_data);
echo "</pre>";

// ==========================================
// ÉTAPE 3 : Créer la session utilisateur
// ==========================================

$_SESSION['valid'] = true;
$_SESSION['username'] = $user_data['login'];
$_SESSION['github_id'] = $user_data['id'];
$_SESSION['avatar'] = $user_data['avatar_url'];
$_SESSION['email'] = $user_data['email'] ?? 'Non public';
$_SESSION['github_profile'] = $user_data['html_url'];
$_SESSION['login_method'] = 'github';
$_SESSION['access_token'] = $access_token;

echo "<p>✅ Session créée ! Redirection vers dashboard...</p>";

// Rediriger vers le dashboard après 2 secondes
header("refresh:2;url=dashboard.php");

echo "<p><a href='dashboard.php'>Cliquez ici si la redirection ne fonctionne pas</a></p>";
?>