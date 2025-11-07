<?php
require_once 'config.php';
require_once 'jwt_helper.php';

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

if (isset($_POST['verify'])) {
    $entered_code = trim($_POST['code']);
    
    if ($method === 'totp') {
        $users = loadUsers();
        $totp_secret = $users[$username]['totp_secret'] ?? null;
        
        if (!$totp_secret) {
            $error = "TOTP non configuré";
        } else {
            if (verifyTOTP($totp_secret, $entered_code)) {
                createSessionAndRedirect($username);
            } else {
                $attempts++;
                $_SESSION['verification_attempts'] = $attempts;
                $error = "Code incorrect. Tentatives : $attempts/3";
            }
        }
        
    } else {
        if (time() > $_SESSION['code_expiry']) {
            $error = "Le code a expiré. Veuillez recommencer.";
            session_destroy();
        }
        elseif ($entered_code == $_SESSION['verification_code']) {
            createSessionAndRedirect($username);
        } else {
            $attempts++;
            $_SESSION['verification_attempts'] = $attempts;
            
            if ($attempts >= 3) {
                $error = "Trop de tentatives. Veuillez recommencer.";
                session_destroy();
            } else {
                $error = "Code incorrect. Tentatives restantes : " . (3 - $attempts);
            }
        }
    }
}

function createSessionAndRedirect($username) {
    global $users;
    
    $jwt = createJWT($username, [
        'email' => $_SESSION['temp_email'],
        'login_method' => '2fa',
        'verified_at' => time()
    ]);
    
    setcookie('auth_token', $jwt, [
        'expires' => time() + JWT_EXPIRATION,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    unset($_SESSION['temp_username']);
    unset($_SESSION['temp_email']);
    unset($_SESSION['temp_phone']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_method']);
    unset($_SESSION['code_expiry']);
    unset($_SESSION['verification_attempts']);
    unset($_SESSION['totp_enabled']);
    unset($_SESSION['totp_secret']);
    
    $_SESSION['valid'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['login_method'] = '2fa';
    
    header("Location: dashboard.php");
    exit();
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du code</title>
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
            max-width: 400px;
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

        .method-badge {
            display: inline-block;
            background-color: rgba(167, 139, 250, 0.2);
            color: rgba(167, 139, 250, 1);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .subtitle {
            text-align: center;
            color: rgba(156, 163, 175, 1);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .success-msg {
            background-color: rgba(167, 139, 250, 0.2);
            border: 1px solid rgba(167, 139, 250, 0.2);
            color: rgba(156, 163, 175, 1);
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

        form {
            margin-top: 1.5rem;
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

        .links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }

        .links a {
            color: rgba(156, 163, 175, 1);
            text-decoration: none;
            transition: color 0.2s;
        }

        .links a:hover {
            color: rgba(243, 244, 246, 1);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Entrez le code de vérification</h2>
        
        <div style="text-align: center; margin-bottom: 1rem;">
            <?php if ($method === 'email'): ?>
                <span class="method-badge">Email</span>
            <?php elseif ($method === 'sms'): ?>
                <span class="method-badge">SMS</span>
            <?php elseif ($method === 'totp'): ?>
                <span class="method-badge">Authenticator</span>
            <?php endif; ?>
        </div>
        
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
            <button type="submit" name="verify" class="submit-btn">Vérifier</button>
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