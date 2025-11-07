<?php
require_once 'config.php';
require_once 'email_helper.php';

// Vérifier que l'utilisateur a passé l'étape de login
if (!isset($_SESSION['temp_username']) || !isset($_POST['method'])) {
    header("Location: login.php");
    exit();
}

$method = $_POST['method'];
$username = $_SESSION['temp_username'];
$email = $_SESSION['temp_email'];
$phone = $_SESSION['temp_phone'];

// Générer un code à 6 chiffres
$code = rand(100000, 999999);
$_SESSION['verification_code'] = $code;
$_SESSION['verification_method'] = $method;
$_SESSION['code_expiry'] = time() + 300; // Expire dans 5 minutes

$msg = '';
$success = false;
$show_code = false; // Nouvelle variable pour contrôler l'affichage

// ========================================
// ENVOI PAR EMAIL
// ========================================

if ($method === 'email') {
    // Vérifier si Email est configuré
    if (empty(SMTP_USER) || empty(SMTP_PASS)) {
        // Mode développement
        $msg = "✅ <strong>Mode développement</strong><br>";
        $msg .= "📧 Email : " . htmlspecialchars($email) . "<br><br>";
        $msg .= "Votre code de vérification est :<br>";
        $msg .= "<strong style='font-size:32px; color:#4CAF50;'>$code</strong>";
        $show_code = true; // Afficher en mode dev
        $success = true;
    } else {
        // Essayer d'envoyer l'email
        if (sendEmailCode($email, $username, $code)) {
            $msg = "✅ <strong>Email envoyé !</strong><br>";
            $msg .= "📧 Un code a été envoyé à : " . substr($email, 0, 3) . "***@***<br>";
            $msg .= "<small>Vérifiez votre boîte de réception</small>";
            $show_code = false; // NE PAS afficher si l'email est envoyé
            $success = true;
        } else {
            // Erreur d'envoi - afficher le code quand même
            $msg = "⚠️ <strong>Erreur d'envoi de l'email</strong><br>";
            $msg .= "<small>Vérifiez votre configuration SMTP</small><br><br>";
            $msg .= "📧 Votre code de vérification est :<br>";
            $msg .= "<strong style='font-size:32px; color:#FF9800;'>$code</strong>";
            $show_code = true; // Afficher en cas d'erreur
            $success = true;
        }
    }
}


// Redirection automatique après 3 secondes
header("refresh:10;url=verify_code.php");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification</title>
</head>
<body>
    <div class="container">
        <span class="dev-badge"> MODE DÉVELOPPEMENT</span>
        
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
            <div style="background: #4CAF50; color: white; padding: 15px; border-radius: 10px; margin: 20px 0;">
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