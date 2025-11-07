<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    require __DIR__ . '/PHPMailer/src/Exception.php';
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
} else {
    die("PHPMailer n'est pas installé !");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * @param string 
 * @param string 
 * @param string 
 * @return bool|string 
 */
function sendEmailCode($to, $username, $code) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $mail->Timeout = 30;
        
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom(SMTP_USER, 'A2F App');
        
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = 'Code de vérification - A2F App';
        
        $mail->Body = "
        <html>
        <head>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2> Code de vérification</h2>
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
        
        $mail->AltBody = "Bonjour $username,\n\n";
        $mail->AltBody .= "Votre code de vérification est : $code\n\n";
        $mail->AltBody .= "Ce code expire dans 5 minutes.\n\n";
        $mail->AltBody .= "Si vous n'avez pas demandé ce code, ignorez cet email.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        return $mail->ErrorInfo;
    }
}
?>
