<?php
require_once 'config.php';
require_once 'jwt_helper.php';

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .profile-card {
            width: 100%;
            max-width: 500px;
            border-radius: 0.75rem;
            background-color: rgba(17, 24, 39, 1);
            padding: 2rem;
            color: rgba(243, 244, 246, 1);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
            font-size: 1.5rem;
            line-height: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        h3 {
            text-align: center;
            font-size: 1.125rem;
            color: rgba(156, 163, 175, 1);
            margin-bottom: 1.5rem;
            font-weight: 400;
        }

        .badge {
            display: inline-block;
            background-color: rgba(31, 41, 55, 1);
            color: rgba(156, 163, 175, 1);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            width: 100%;
            text-align: center;
        }

        .info-box {
            background-color: rgba(31, 41, 55, 1);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(55, 65, 81, 1);
            line-height: 2;
            font-size: 0.875rem;
        }

        .info-box strong {
            color: rgba(167, 139, 250, 1);
        }

        .jwt-info {
            background-color: rgba(31, 41, 55, 1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(55, 65, 81, 1);
            font-size: 0.75rem;
            font-family: 'Courier New', monospace;
            color: rgba(156, 163, 175, 1);
            word-break: break-all;
        }

        .logout-btn {
            display: block;
            width: 100%;
            background-color: rgba(31, 41, 55, 1);
            padding: 0.75rem;
            text-align: center;
            color: rgba(243, 244, 246, 1);
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: rgba(220, 38, 38, 1);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="profile-card">
        <h2>Tableau de bord</h2>
        <h3>Bienvenue <?= htmlspecialchars($username) ?> !</h3>
        
        <div class="badge">Connecté avec 2FA</div>
        
        <div class="info-box">
            <strong>Utilisateur :</strong> <?= htmlspecialchars($username) ?><br>
            <strong>Email :</strong> <?= htmlspecialchars($email) ?><br>
            <strong>Méthode :</strong> <?= htmlspecialchars($login_method) ?><br>
            <strong>Token expire :</strong> <?= date('H:i:s', $payload['exp']) ?>
        </div>
        
        <div class="jwt-info">
            <strong>JWT Token :</strong><br>
            <?= htmlspecialchars(substr(getJWTFromRequest(), 0, 50)) ?>...
        </div>
        
        <a href="logout.php" class="logout-btn">Se déconnecter</a>
    </div>
</body>
</html>