<?php
// Charger PHPMailer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Si installé avec Composer
    require __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    // Si téléchargé manuellement
    require __DIR__ . '/PHPMailer/src/Exception.php';
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
} else {
    die("PHPMailer n'est pas installé !");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoyer un email avec PHPMailer
 */
function sendEmailCode($to, $username, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Désactiver la vérification SSL en développement
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Encodage
        $mail->CharSet = 'UTF-8';
        
        // Expéditeur
        $mail->setFrom(SMTP_USER, 'A2F App');
        
        // Destinataire
        $mail->addAddress($to);
        
        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = 'Code de vérification - A2F App';
        
        // Corps de l'email en HTML
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px; }
                .container { background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 0 auto; }
                .header { text-align: center; color: #4CAF50; margin-bottom: 20px; }
                .code-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
                .code { font-size: 36px; font-weight: bold; letter-spacing: 8px; font-family: 'Courier New', monospace; }
                .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔐 Code de vérification</h2>
                </div>
                <p>Bonjour <strong>" . htmlspecialchars($username) . "</strong>,</p>
                <p>Votre code de vérification pour l'authentification à deux facteurs est :</p>
                <div class='code-box'>
                    <div class='code'>$code</div>
                </div>
                <p>Ce code expire dans <strong>5 minutes</strong>.</p>
                <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                <div class='footer'>
                    <p>A2F App - Système d'authentification sécurisé</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Version texte (pour les clients qui n'affichent pas HTML)
        $mail->AltBody = "Bonjour $username,\n\n";
        $mail->AltBody .= "Votre code de vérification est : $code\n\n";
        $mail->AltBody .= "Ce code expire dans 5 minutes.\n\n";
        $mail->AltBody .= "Si vous n'avez pas demandé ce code, ignorez cet email.";
        
        // Envoyer l'email
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log l'erreur
        error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        return false;
    }
}
?>