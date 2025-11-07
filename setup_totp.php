<?php
require_once 'config.php';

// Vérifier que l'utilisateur a passé l'étape de login
if (!isset($_SESSION['temp_username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['temp_username'];
$users = loadUsers();

// Générer un nouveau secret TOTP s'il n'existe pas
if (!isset($users[$username]['totp_secret']) || empty($users[$username]['totp_secret'])) {
    $totp_secret = generateTOTPSecret();
    $users[$username]['totp_secret'] = $totp_secret;
    saveUsers($users);
} else {
    $totp_secret = $users[$username]['totp_secret'];
}

// Stocker temporairement dans la session
$_SESSION['totp_secret'] = $totp_secret;

// Créer l'URL TOTP pour le QR Code
$issuer = 'A2F_App';
$totp_url = "otpauth://totp/{$issuer}:{$username}?secret={$totp_secret}&issuer={$issuer}";

// Vérification de l'activation
$msg = '';
$error = '';

if (isset($_POST['verify_totp'])) {
    $entered_code = trim($_POST['code']);
    
    // Vérifier le code TOTP
    if (verifyTOTP($totp_secret, $entered_code)) {
        // Activer TOTP pour cet utilisateur
        $users = loadUsers();
        $users[$username]['totp_enabled'] = true;
        $users[$username]['totp_secret'] = $totp_secret;
        saveUsers($users);
        
        $_SESSION['totp_enabled'] = true;
        $_SESSION['verification_method'] = 'totp';
        
        $msg = "✅ Authenticator configuré avec succès !";
        
        // Rediriger après 2 secondes
        header("refresh:2;url=verify_code.php");
    } else {
        $error = "❌ Code incorrect. Réessayez.";
    }
}

// Fonction pour générer un secret TOTP (Base32)
function generateTOTPSecret($length = 16) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[random_int(0, 31)];
    }
    return $secret;
}

// Fonction pour vérifier un code TOTP (copie de verify_code.php)
function verifyTOTP($secret, $code) {
    $key = base32Decode($secret);
    $time = floor(time() / 30);
    
    for ($i = -1; $i <= 1; $i++) {
        $calculatedCode = generateTOTP($key, $time + $i);
        if ($calculatedCode === $code) {
            return true;
        }
    }
    return false;
}

function generateTOTP($key, $time) {
    $timeHex = pack('N*', 0) . pack('N*', $time);
    $hash = hash_hmac('sha1', $timeHex, $key, true);
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

function base32Decode($input) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $v = 0;
    $vbits = 0;
    
    for ($i = 0, $j = strlen($input); $i < $j; $i++) {
        $v <<= 5;
        $v += stripos($alphabet, $input[$i]);
        $vbits += 5;
        
        while ($vbits >= 8) {
            $vbits -= 8;
            $output .= chr($v >> $vbits);
            $v &= ((1 << $vbits) - 1);
        }
    }
    return $output;
}

// Générer le QR Code (simple, sans bibliothèque externe)
function generateQRCodeURL($data) {
    // Utiliser l'API Google Chart (simple mais dépendant d'un service externe)
    return 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($data);
}

$qr_code_url = generateQRCodeURL($totp_url);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration TOTP</title>
</head>
<body>
    <div class="container">
        <h2>Configuration de l'Authenticator</h2>
        
        <?php if (!empty($msg)): ?>
            <div class="success-msg"><?= $msg ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="step">
            <span class="step-number">Étape 1 :</span> Téléchargez une application d'authentification
            <div class="app-list">
                • Google Authenticator<br>
                • Microsoft Authenticator<br>
                • Authy<br>
                • FreeOTP
            </div>
        </div>
        
        <div class="step">
            <span class="step-number">Étape 2 :</span> Scannez ce QR Code avec votre application
            <div class="qr-container">
                <img src="<?= htmlspecialchars($qr_code_url) ?>" alt="QR Code" class="qr-code">
                <p style="font-size: 12px; color: #666;">Ou entrez ce code manuellement :</p>
                <div class="secret-code"><?= $totp_secret ?></div>
            </div>
        </div>
        
        <div class="step">
            <span class="step-number">Étape 3 :</span> Entrez le code à 6 chiffres généré par l'application
            <form method="POST">
                <input 
                    type="text" 
                    name="code" 
                    class="code-input" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    placeholder="000000" 
                    required 
                    autofocus
                    autocomplete="off"
                >
                <button type="submit" name="verify_totp">Vérifier et activer</button>
            </form>
        </div>
        
        <div class="skip-link">
            <a href="verify_2fa.php">← Utiliser une autre méthode</a>
        </div>
    </div>
</body>
</html>