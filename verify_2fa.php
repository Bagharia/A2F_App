<?php
require_once 'config.php';

if (!isset($_SESSION['temp_username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['temp_username'];
$email = $_SESSION['temp_email'];
$phone = $_SESSION['temp_phone'];

$users = loadUsers();
$totp_enabled = isset($users[$username]['totp_enabled']) && $users[$username]['totp_enabled'] === true;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA</title>
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

        .form-container {
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

        .subtitle {
            text-align: center;
            color: rgba(156, 163, 175, 1);
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }

        .method-card {
            background-color: rgba(31, 41, 55, 1);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(55, 65, 81, 1);
        }

        .method-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: rgba(243, 244, 246, 1);
        }

        .method-desc {
            color: rgba(156, 163, 175, 1);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .method-btn {
            display: block;
            width: 100%;
            background-color: rgba(167, 139, 250, 1);
            padding: 0.75rem;
            text-align: center;
            color: rgba(17, 24, 39, 1);
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .method-btn:hover {
            background-color: rgba(147, 119, 230, 1);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .info-text {
            color: rgba(156, 163, 175, 1);
            font-size: 0.75rem;
            margin-top: 0.5rem;
            text-align: center;
        }

        .cancel-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .cancel-link a {
            color: rgba(156, 163, 175, 1);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .cancel-link a:hover {
            color: rgba(243, 244, 246, 1);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Vérification en deux étapes</h2>
        <p class="subtitle">Bonjour <strong><?= htmlspecialchars($username) ?></strong><br>
        Choisissez comment vérifier votre identité :</p>
        
        <div class="method-card">
            <div class="method-title">Email</div>
            <div class="method-desc">
                Recevoir un code à : <?= substr($email, 0, 3) ?>***@***
            </div>
            <form method="POST" action="send_verification.php">
                <input type="hidden" name="method" value="email">
                <button type="submit" class="method-btn">Envoyer par Email</button>
            </form>
        </div>
        
        <div class="method-card">
            <div class="method-title">SMS</div>
            <div class="method-desc">
                Recevoir un code au : <?= substr($phone, 0, 6) ?>***
            </div>
            <form method="POST" action="send_verification.php">
                <input type="hidden" name="method" value="sms">
                <button type="submit" class="method-btn">Envoyer par SMS</button>
            </form>
        </div>
        
        <div class="method-card">
            <div class="method-title">Application d'authentification</div>
            <div class="method-desc">
                Utiliser Google Authenticator, Microsoft Authenticator, etc.
            </div>
            
            <a href="setup_totp.php" class="method-btn">
                Scanner QR Code
            </a>
            <p class="info-text">Scannez le QR Code avec votre application d'authentification</p>
        </div>
        
        <div class="cancel-link">
            <a href="logout.php">Annuler</a>
        </div>
    </div>
</body>
</html>