<?php
require_once 'config.php';
require_once 'jwt_helper.php';

// Vérifier que l'utilisateur a passé les étapes précédentes
if (!isset($_SESSION['temp_username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['temp_username'];
$method = $_SESSION['verification_method'] ?? 'email';
$msg = $_SESSION['verification_message'] ?? '';
unset($_SESSION['verification_message']);

$error = '';
$attempts = $_SESSION['verification_attempts'] ?? 0;

// Traitement de la vérification
if (isset($_POST['verify'])) {
    $entered_code = trim($_POST['code']);
    
    // Vérifier selon la méthode
    if ($method === 'totp') {
        // Vérification TOTP (Google Authenticator)
        $users = loadUsers();
        $totp_secret = $users[$username]['totp_secret'] ?? null;
        
        if (!$totp_secret) {
            $error = "❌ TOTP non configuré";
        } else {
            // Vérifier le code TOTP
            if (verifyTOTP($totp_secret, $entered_code)) {
                // Code correct !
                createSessionAndRedirect($username);
            } else {
                $attempts++;
                $_SESSION['verification_attempts'] = $attempts;
                $error = "❌ Code incorrect. Tentatives : $attempts/3";
            }
        }
        
    } else {
        // Vérification Email/SMS
        
        // Vérifier l'expiration
        if (time() > $_SESSION['code_expiry']) {
            $error = "❌ Le code a expiré. Veuillez recommencer.";
            session_destroy();
        }
        // Vérifier le code
        elseif ($entered_code == $_SESSION['verification_code']) {
            // Code correct !
            createSessionAndRedirect($username);
        } else {
            $attempts++;
            $_SESSION['verification_attempts'] = $attempts;
            
            if ($attempts >= 3) {
                $error = "❌ Trop de tentatives. Veuillez recommencer.";
                session_destroy();
            } else {
                $error = "❌ Code incorrect. Tentatives restantes : " . (3 - $attempts);
            }
        }
    }
}

// Fonction pour créer la session et rediriger
function createSessionAndRedirect($username) {
    global $users;
    
    // Créer le JWT
    $jwt = createJWT($username, [
        'email' => $_SESSION['temp_email'],
        'login_method' => '2fa',
        'verified_at' => time()
    ]);
    
    // Stocker le JWT dans un cookie sécurisé
    setcookie('auth_token', $jwt, [
        'expires' => time() + JWT_EXPIRATION,
        'path' => '/',
        'secure' => false, // Mettre true en production avec HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Nettoyer les variables temporaires
    unset($_SESSION['temp_username']);
    unset($_SESSION['temp_email']);
    unset($_SESSION['temp_phone']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_method']);
    unset($_SESSION['code_expiry']);
    unset($_SESSION['verification_attempts']);
    unset($_SESSION['totp_enabled']);
    unset($_SESSION['totp_secret']);
    
    // Créer une session classique aussi (pour compatibilité)
    $_SESSION['valid'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['login_method'] = '2fa';
    
    header("Location: dashboard.php");
    exit();
}

// Fonction pour vérifier un code TOTP
function verifyTOTP($secret, $code) {
    // Décoder le secret Base32
    $key = base32Decode($secret);
    
    // Obtenir le timestamp actuel (en périodes de 30 secondes)
    $time = floor(time() / 30);
    
    // Vérifier le code actuel et les codes adjacents (pour tenir compte du décalage de temps)
    for ($i = -1; $i <= 1; $i++) {
        $calculatedCode = generateTOTP($key, $time + $i);
        if ($calculatedCode === $code) {
            return true;
        }
    }
    
    return false;
}

// Fonction pour générer un code TOTP
function generateTOTP($key, $time) {
    // Convertir le temps en binaire (8 octets, big-endian)
    $timeHex = pack('N*', 0) . pack('N*', $time);
    
    // HMAC-SHA1
    $hash = hash_hmac('sha1', $timeHex, $key, true);
    
    // Extraction dynamique (Dynamic Truncation)
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    
    // Retourner le code à 6 chiffres
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

// Fonction pour décoder Base32
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du code</title>
</head>
<body>
    <div class="container">
        <h2> Entrez le code de vérification</h2>
        
        <?php if ($method === 'email'): ?>
            <span class="method-badge">📧 Email</span>
        <?php elseif ($method === 'sms'): ?>
            <span class="method-badge">📱 SMS</span>
        <?php elseif ($method === 'totp'): ?>
            <span class="method-badge">🔐 Authenticator</span>
        <?php endif; ?>
        
        <p class="subtitle">
            <?php if ($method === 'totp'): ?>
                Ouvrez votre application d'authentification et entrez le code à 6 chiffres
            <?php else: ?>
                Un code à 6 chiffres vous a été envoyé
            <?php endif; ?>
        </p>
        
        <?php if (!empty($msg)): ?>
            <div class="success-msg"><?= $msg ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>
        
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
            <button type="submit" name="verify">Vérifier</button>
        </form>
        
        <div class="links">
            <?php if ($method !== 'totp'): ?>
                <a href="verify_2fa.php">Renvoyer le code</a> |
            <?php endif; ?>
            <a href="verify_2fa.php">Changer de méthode</a> |
            <a href="logout.php">Annuler</a>
        </div>
    </div>
</body>
</html>