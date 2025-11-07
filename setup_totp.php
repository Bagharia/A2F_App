<?php
require_once 'config.php';

if (!isset($_SESSION['temp_username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['temp_username'];
$users = loadUsers();

if (!isset($users[$username]['totp_secret']) || empty($users[$username]['totp_secret'])) {
    $totp_secret = generateTOTPSecret();
    $users[$username]['totp_secret'] = $totp_secret;
    saveUsers($users);
} else {
    $totp_secret = $users[$username]['totp_secret'];
}

$_SESSION['totp_secret'] = $totp_secret;

$issuer = 'A2F_App';
$totp_url = "otpauth://totp/{$issuer}:{$username}?secret={$totp_secret}&issuer={$issuer}";

$msg = '';
$error = '';

if (isset($_POST['verify_totp'])) {
    $entered_code = trim($_POST['code']);

    if (verifyTOTP($totp_secret, $entered_code)) {
        $users = loadUsers();
        $users[$username]['totp_enabled'] = true;
        $users[$username]['totp_secret'] = $totp_secret;
        saveUsers($users);
        
        $_SESSION['totp_enabled'] = true;
        $_SESSION['verification_method'] = 'totp';
        
        $msg = "✅ Authenticator configuré avec succès !";

        header("refresh:2;url=verify_code.php");
    } else {
        $error = "❌ Code incorrect. Réessayez.";
    }
}

function generateTOTPSecret($length = 16) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[random_int(0, 31)];
    }
    return $secret;
}

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

function generateQRCodeURL($data) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
}

$qr_code_url = generateQRCodeURL($totp_url);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration TOTP</title>
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
            max-width: 600px;
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
            margin-bottom: 2rem;
        }

        .success-msg {
            background-color: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: rgba(74, 222, 128, 1);
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            text-align: center;
        }

        .error-msg {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: rgba(248, 113, 113, 1);
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            text-align: center;
        }

        .step {
            background-color: rgba(31, 41, 55, 1);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(55, 65, 81, 1);
        }

        .step-number {
            color: rgba(167, 139, 250, 1);
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .app-list {
            color: rgba(156, 163, 175, 1);
            font-size: 0.875rem;
            margin-top: 0.75rem;
            line-height: 1.75;
        }

        .qr-container {
            text-align: center;
            margin-top: 1rem;
        }

        .qr-code {
            max-width: 300px;
            height: auto;
            border: 2px solid rgba(55, 65, 81, 1);
            padding: 10px;
            background: white;
            border-radius: 0.5rem;
            margin: 0 auto;
            display: block;
        }

        .secret-code {
            font-family: 'Courier New', monospace;
            font-size: 1.125rem;
            font-weight: bold;
            color: rgba(243, 244, 246, 1);
            margin: 1rem 0;
            padding: 1rem;
            background-color: rgba(31, 41, 55, 1);
            border-radius: 0.375rem;
            border: 1px solid rgba(55, 65, 81, 1);
            letter-spacing: 0.1rem;
        }

        .code-input {
            width: 100%;
            border-radius: 0.375rem;
            border: 1px solid rgba(55, 65, 81, 1);
            outline: none;
            background-color: rgba(17, 24, 39, 1);
            padding: 0.75rem 1rem;
            color: rgba(243, 244, 246, 1);
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-family: 'Courier New', monospace;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }

        .code-input:focus {
            border-color: rgba(167, 139, 250, 1);
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
        }

        .submit-btn {
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
        }

        .submit-btn:hover {
            background-color: rgba(147, 119, 230, 1);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .skip-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .skip-link a {
            color: rgba(156, 163, 175, 1);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .skip-link a:hover {
            color: rgba(243, 244, 246, 1);
        }
    </style>
</head>
<body>
    <div class="form-container">
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
                <img 
                    src="<?= htmlspecialchars($qr_code_url) ?>" 
                    alt="QR Code" 
                    class="qr-code"
                >
                <p style="font-size: 0.75rem; color: rgba(156, 163, 175, 1); margin-top: 1rem;">Ou entrez ce code manuellement :</p>
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
                <button type="submit" name="verify_totp" class="submit-btn">Vérifier et activer</button>
            </form>
        </div>
        
        <div class="skip-link">
            <a href="verify_2fa.php">← Utiliser une autre méthode</a>
        </div>
    </div>
</body>
</html>