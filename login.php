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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - A2F App</title>
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

        .input-group {
            margin-bottom: 1.25rem;
        }

        .input-group label {
            display: block;
            color: rgba(156, 163, 175, 1);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            border-radius: 0.375rem;
            border: 1px solid rgba(55, 65, 81, 1);
            outline: none;
            background-color: rgba(17, 24, 39, 1);
            padding: 0.75rem 1rem;
            color: rgba(243, 244, 246, 1);
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .input-group input:focus {
            border-color: rgba(167, 139, 250, 1);
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
        }

        .input-group input::placeholder {
            color: rgba(107, 114, 128, 1);
        }

        .submit-btn, .github-btn {
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
            text-decoration: none;
        }

        .submit-btn:hover, .github-btn:hover {
            background-color: rgba(147, 119, 230, 1);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:active, .github-btn:active {
            transform: translateY(0);
        }

        .separator {
            text-align: center;
            color: rgba(156, 163, 175, 1);
            margin: 1.5rem 0;
            font-size: 0.875rem;
            position: relative;
        }

        .separator::before,
        .separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background-color: rgba(55, 65, 81, 1);
        }

        .separator::before {
            left: 0;
        }

        .separator::after {
            right: 0;
        }

        .github-btn {
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Connexion</h2>
        
        <?php if(!empty($msg)): ?>
            <div class="error-msg">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="input-group">
                <label for="username">Nom d'utilisateur :</label>
                <input 
                    type="text" 
                    id="username"
                    name="username" 
                    placeholder="Entrez votre nom d'utilisateur"
                    required 
                    autofocus
                >
            </div>
            
            <div class="input-group">
                <label for="password">Mot de passe :</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    placeholder="Entrez votre mot de passe"
                    required
                >
            </div>
            
            <button type="submit" name="login" class="submit-btn">
                Se connecter
            </button>
        </form>

        <div class="separator">OU</div>

        <a href="github_oauth.php" class="github-btn">
            Se connecter avec GitHub
        </a>
    </div>
</body>
</html>