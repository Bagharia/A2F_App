<?php
require_once 'config.php';

$msg = '';

if (isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (array_key_exists($username, $users)) {
        if (password_verify($password, $users[$username]['password'])) {
            $_SESSION['valid'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['login_method'] = 'local';
            header("Location: dashboard.php");
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