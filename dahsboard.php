<?php
require_once 'config.php';

if (!isset($_SESSION['valid']) || $_SESSION['valid'] !== true) {
    header("Location: login.php");
    exit();
}

$login_method = $_SESSION['login_method'] ?? 'local';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
</head>
<body>
    <h2> Tableau de bord</h2>
    
    <div class="profile-card">
        <?php if ($login_method === 'github'): ?>
            <h3><?= htmlspecialchars($_SESSION['username']) ?></h3>
            <p>Email : <?= htmlspecialchars($_SESSION['email']) ?></p>
            <span class="badge"> Connecté via GitHub</span>
            <p><a href="<?= htmlspecialchars($_SESSION['github_profile']) ?>" target="_blank">Voir le profil GitHub</a></p>
        <?php else: ?>
            <h3>Bienvenue <?= htmlspecialchars($_SESSION['username']) ?> !</h3>
            <span class="badge"> Connexion locale</span>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center;">
        <a href="logout.php" class="logout-btn">Se déconnecter</a>
    </div>
</body>
</html>