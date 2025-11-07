<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
```

---

## 🔒 Sécurité : Protection CSRF avec `state`

Le paramètre `state` protège contre les attaques CSRF :

1. **Génération** : On crée un token aléatoire dans `github_oauth.php`
2. **Stockage** : On le sauvegarde dans `$_SESSION['oauth_state']`
3. **Vérification** : GitHub le renvoie dans le callback, on vérifie qu'il correspond

---

## 🧪 Tester l'application

1. Lance ton serveur : `php -S localhost:8000`
2. Va sur : `http://localhost:8000/login.php`
3. Clique sur **"Se connecter avec GitHub"**
4. Autorise l'application
5. Tu es redirigé vers le dashboard avec tes infos GitHub ! 🎉

---

## 📝 Récapitulatif du flux OAuth2
```
1. User clique "Se connecter avec GitHub"
   ↓
2. github_oauth.php → Redirige vers GitHub
   ↓
3. User autorise l'application
   ↓
4. GitHub redirige vers github_callback.php avec un CODE
   ↓
5. github_callback.php échange le CODE contre un ACCESS_TOKEN
   ↓
6. Utilise le token pour récupérer les infos user (API GitHub)
   ↓
7. Crée la session et redirige vers dashboard.php