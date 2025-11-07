<?php
require_once 'config.php';

// Vérifier que l'utilisateur a passé l'étape de login
if (!isset($_SESSION['temp_username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['temp_username'];
$email = $_SESSION['temp_email'];
$phone = $_SESSION['temp_phone'];
$totp_enabled = $_SESSION['totp_enabled'] ?? false;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA</title>
</head>
<body>
    <div class="container">
        <h2>Vérification en deux étapes</h2>
        <p class="subtitle">Bonjour <strong><?= htmlspecialchars($username) ?></strong><br>
        Choisissez comment vérifier votre identité :</p>
        
        <!-- Méthode 1 : Email -->
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
        
        <!-- Méthode 2 : SMS -->
        <div class="method-card">
            <div class="method-title">SMS</div>
            <div class="method-desc">
                Recevoir un code au : <?= substr($phone, 0, 6) ?>***
            </div>
            <form method="POST" action="send_verification.php">
                <input type="hidden" name="method" value="sms">
                <button type="submit" class="method-btn secondary">Envoyer par SMS</button>
            </form>
        </div>
        
        <!-- Méthode 3 : TOTP (Authenticator) -->
        <div class="method-card">
            <div class="method-icon"></div>
            <div class="method-title">Application d'authentification</div>
            <div class="method-desc">
                Utiliser Google Authenticator, Microsoft Authenticator, etc.
            </div>
            
            <?php if ($totp_enabled): ?>
                <form method="POST" action="verify_code.php">
                    <input type="hidden" name="method" value="totp">
                    <button type="submit" class="method-btn warning">Utiliser l'Authenticator</button>
                </form>
            <?php else: ?>
                <a href="setup_totp.php" class="method-btn warning">
                    Configurer l'Authenticator
                </a>
                <p class="info-text">Première utilisation ? Scannez un QR Code</p>
            <?php endif; ?>
        </div>
        
        <div class="cancel-link">
            <a href="logout.php">Annuler</a>
        </div>
    </div>
</body>
</html>