<?php
require_once 'config.php';
require_once 'jwt_helper.php';

// Vérifier l'authentification avec JWT
$payload = requireAuth();

$username = $payload['sub'];
$login_method = $payload['data']['login_method'] ?? 'unknown';
$email = $payload['data']['email'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
</head>
<body>
     <div class="profile-card">
        <h2> Tableau de bord</h2>
        <h3>Bienvenue <?= htmlspecialchars($username) ?> !</h3>
        
        <span class="badge"> Connecté avec 2FA</span>
        
        <div class="info-box">
            <strong> Utilisateur :</strong> <?= htmlspecialchars($username) ?><br>
            <strong> Email :</strong> <?= htmlspecialchars($email) ?><br>
            <strong> Méthode :</strong> <?= htmlspecialchars($login_method) ?><br>
            <strong> Token expire :</strong> <?= date('H:i:s', $payload['exp']) ?>
        </div>
        
        <div class="jwt-info">
            <strong>JWT Token :</strong><br>
            <?= htmlspecialchars(substr(getJWTFromRequest(), 0, 50)) ?>...
        </div>
        
        <a href="logout.php" class="logout-btn">Se déconnecter</a>
    </div>
</body>
</html>