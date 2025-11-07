<?php
require_once 'config.php';
require_once 'jwt_helper.php';

$msg = '';

if (isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $users = loadUsers();
    
    if (array_key_exists($username, $users)) {
        if (password_verify($password, $users[$username]['password'])) {
            // Mot de passe correct → Passer à la 2FA
            $_SESSION['temp_username'] = $username;
            $_SESSION['temp_email'] = $users[$username]['email'];
            $_SESSION['temp_phone'] = $users[$username]['phone'];
            $_SESSION['totp_enabled'] = $users[$username]['totp_enabled'] ?? false;
            $_SESSION['totp_secret'] = $users[$username]['totp_secret'] ?? null;
            
            header("Location: verify_2fa.php");
            exit();
        } else {
            $msg = "Mot de passe incorrect";
        }
    } else {
        $msg = "Nom d'utilisateur incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php if(!empty($msg)): ?>
        <p style="color: red; font-weight: bold;">
            <?= htmlspecialchars($msg) ?>
        </p>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <h2>Connexion</h2>
        <label>Email :</label>
        <input type="text" name="username" required>
        <br><br>

        <label>Mot de passe :</label>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit" name="login">Se connecter</button>
    </form>

     <div class="separator">───── OU ─────</div>

    <a href="github_oauth.php" class="github-btn">
        Se connecter avec GitHub
    </a>
</body>
</html>