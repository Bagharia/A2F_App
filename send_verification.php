<?php
require_once 'config.php';
require_once 'email_helper.php';

if (!isset($_SESSION['temp_username']) || !isset($_POST['method'])) {
    header("Location: login.php");
    exit();
}

$method = $_POST['method'];
$username = $_SESSION['temp_username'];
$email = $_SESSION['temp_email'];
$phone = $_SESSION['temp_phone'];

$code = rand(100000, 999999);
$_SESSION['verification_code'] = $code;
$_SESSION['verification_method'] = $method;
$_SESSION['code_expiry'] = time() + 300;

$msg = '';
$success = false;
$show_code = false;
$is_dev_mode = false;

if ($method === 'email') {
    if (empty(SMTP_USER) || empty(SMTP_PASS)) {
        $is_dev_mode = true;
        $msg = "<strong>Mode développement</strong><br>";
        $msg .= "Email : " . htmlspecialchars($email) . "<br><br>";
        $msg .= "Votre code de vérification est :<br>";
        $msg .= "<strong style='font-size:32px; color:#4CAF50;'>$code</strong>";
        $show_code = true;
        $success = true;
    } else {
        $email_result = sendEmailCode($email, $username, $code);
        if ($email_result === true) {
            $is_dev_mode = false;
            $msg = "<strong>Email envoyé avec succès !</strong><br>";
            $msg .= "Un code de vérification a été envoyé à : " . htmlspecialchars($email) . "<br>";
            $msg .= "<small>Vérifiez votre boîte de réception (et les spams si nécessaire)</small>";
            $show_code = false; 
            $success = true;
        } else {
            $is_dev_mode = true;
            $error_msg = is_string($email_result) ? $email_result : 'Erreur inconnue';
            $msg = "<strong>Erreur d'envoi de l'email</strong><br>";
            $msg .= "<small style='color: #ff6b6b;'>Erreur : " . htmlspecialchars($error_msg) . "</small><br>";
            $msg .= "<small>Vérifiez votre configuration SMTP</small><br><br>";
            $msg .= "Votre code de vérification est :<br>";
            $msg .= "<strong style='font-size:32px; color:#FF9800;'>$code</strong>";
            $show_code = true; 
            $success = true;
        }
    }
}

if ($method === 'sms') {
    $is_dev_mode = true;
    $msg = "<strong>Mode développement</strong><br>";
    $msg .= "SMS : " . htmlspecialchars($phone) . "<br><br>";
    $msg .= "Votre code de vérification est :<br>";
    $msg .= "<strong style='font-size:32px; color: rgba(156, 163, 175, 1);;'>$code</strong>";
    $show_code = true;
    $success = true;
}

$_SESSION['verification_message'] = $msg;

header("refresh:10;url=verify_code.php");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification</title>
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
            text-align: center;
        }

        .dev-badge {
            display: inline-block;
            background-color: rgba(167, 139, 250, 0.2);
            color: rgba(156, 163, 175, 1);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
        }

        .icon {
            font-size: 4rem;
            margin: 1rem 0;
        }

        .method-info {
            color: rgba(156, 163, 175, 1);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .code-box {
            background: linear-gradient(135deg, rgba(167, 139, 250, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border: 2px solid rgba(167, 139, 250, 0.3);
            color: rgba(243, 244, 246, 1);
            padding: 2rem;
            border-radius: 0.75rem;
            margin: 1.5rem 0;
        }

        .code-label {
            font-size: 0.875rem;
            color: rgba(156, 163, 175, 1);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
        }

        .code {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            font-family: 'Courier New', monospace;
            color: rgba(167, 139, 250, 1);
        }

        .success-msg {
            rgba(167, 139, 250, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: color: rgba(156, 163, 175, 1);
            padding: 1rem;
            border-radius: 0.375rem;
            margin: 1.5rem 0;
            font-size: 0.875rem;
            text-align: left;
        }

        .error-msg {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: rgba(248, 113, 113, 1);
            padding: 1rem;
            border-radius: 0.375rem;
            margin: 1.5rem 0;
            font-size: 0.875rem;
            text-align: left;
        }

        .redirect-msg {
            color: rgba(156, 163, 175, 1);
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }

        .manual-link {
            margin-top: 1rem;
        }

        .manual-link a {
            color: rgba(167, 139, 250, 1);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .manual-link a:hover {
            color: rgba(147, 119, 230, 1);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <?php if ($is_dev_mode): ?>
            <span class="dev-badge">MODE DÉVELOPPEMENT</span>
        <?php endif; ?>
        
        <div class="icon">
            <?php if ($method === 'email'): ?>
            <?php elseif ($method === 'sms'): ?>
            <?php endif; ?>
        </div>
        
        <div class="method-info">
            <?php if ($method === 'email'): ?>
                Email : <?= htmlspecialchars($email) ?>
            <?php elseif ($method === 'sms'): ?>
                Téléphone : <?= htmlspecialchars($phone) ?>
            <?php endif; ?>
        </div>
        
        <?php if ($show_code): ?>
            <div class="code-box">
                <div class="code-label">Votre code de vérification</div>
                <div class="code"><?= $code ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($success && !$show_code): ?>
            <div class="success-msg">
                <?= $msg ?>
            </div>
        <?php elseif ($success && $show_code && $is_dev_mode && $method === 'email' && !empty(SMTP_USER) && !empty(SMTP_PASS)): ?>
            <div class="error-msg">
                <?= $msg ?>
            </div>
        <?php elseif ($success && $show_code): ?>
            <div class="success-msg">
                <?= $msg ?>
            </div>
        <?php endif; ?>
        
        <div class="redirect-msg">
            Redirection automatique dans 10 secondes...
        </div>
        
        <div class="manual-link">
            <a href="verify_code.php">Continuer maintenant →</a>
        </div>
    </div>
</body>
</html>