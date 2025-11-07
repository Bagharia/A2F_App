# A2F App - Application d'Authentification à Deux Facteurs


##  Description

A2F App est une application PHP d'authentification sécurisée avec support de l'authentification à deux facteurs (2FA). Elle permet aux utilisateurs de s'identifier avec plusieurs méthodes de vérification :

- **Email** : Réception d'un code de vérification par email
- **SMS** : Réception d'un code de vérification par SMS (via Twilio)
- **TOTP/QR Code** : Authentification via applications d'authentification (Google Authenticator, Microsoft Authenticator, etc.)
- **GitHub OAuth** : Connexion via compte GitHub

L'application utilise des **jetons JWT** pour maintenir les sessions utilisateurs de manière sécurisée.

## Fonctionnalités

-  Authentification par mot de passe
-  Authentification à deux facteurs (2FA) avec 3 méthodes :
  - Email (via PHPMailer/Gmail)
  - SMS (via Twilio)
  - TOTP/QR Code (Google Authenticator, etc.)
-  Connexion via GitHub OAuth
-  Gestion de sessions avec JWT (JSON Web Tokens)

##  Prérequis

- **PHP** 7.4 ou supérieur
- **Composer** (gestionnaire de dépendances PHP)
- **Serveur web** (Apache/Nginx) ou serveur de développement PHP intégré
- **Extension PHP** : `curl`, `json`, `mbstring`, `openssl`

## 🔧 Installation

### 1. Cloner ou télécharger le projet

```bash
cd /chemin/vers/votre/projet
```

### 2. Installer les dépendances avec Composer

```bash
composer install
```

Cela installera automatiquement :
- `firebase/php-jwt` : Pour la gestion des JWT
- `phpmailer/phpmailer` : Pour l'envoi d'emails
- `twilio/sdk` : Pour l'envoi de SMS
- `endroid/qr-code` : Pour la génération de QR codes
- `robthree/twofactorauth` : Pour la génération TOTP

### 3. Créer le fichier `.env`

Créez un fichier `.env` à la racine du projet avec le contenu suivant :

```env
# JWT - Secret pour signer les tokens (CHANGEZ-LE !)
JWT_SECRET=votre_secret_super_securise_changez_moi_par_une_chaine_aleatoire_longue

# Email (Gmail)
SMTP_USER=votre_email@gmail.com
SMTP_PASS=votre_mot_de_passe_application_gmail

# Twilio (optionnel - pour SMS)
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_PHONE=

# GitHub OAuth (optionnel)
GITHUB_CLIENT_ID=ton_client_id_github
GITHUB_CLIENT_SECRET=ton_client_secret_github
```

## Configuration

### Configuration Email (Gmail)

Pour utiliser l'envoi d'emails avec Gmail :

1. **Activer la validation en deux étapes** sur votre compte Gmail :
   - Allez sur https://myaccount.google.com/security
   - Activez la "Validation en deux étapes"

2. **Créer un mot de passe d'application** :
   - Allez sur https://myaccount.google.com/apppasswords
   - Sélectionnez "Mail" comme application
   - Sélectionnez "Autre (nom personnalisé)" → entrez "A2F App"
   - Cliquez sur "Générer"
   - **Copiez le mot de passe à 16 caractères** (sans espaces, sans tirets)

3. **Mettre à jour le fichier `.env`** :
   ```env
   SMTP_USER=votre_email@gmail.com
   SMTP_PASS=votre_mot_de_passe_application_16_caracteres
   ```

 **Important** : Utilisez le **mot de passe d'application** (16 caractères), pas votre mot de passe Gmail habituel !

### Configuration GitHub OAuth

Pour activer la connexion via GitHub :

1. **Créer une OAuth App sur GitHub** :
   - Allez sur https://github.com/settings/developers
   - Cliquez sur "New OAuth App"
   - Remplissez le formulaire :
     - **Application name** : A2F App
     - **Homepage URL** : `http://localhost:8000/login.php` (ou votre URL)
     - **Authorization callback URL** : `http://localhost:8000/github_callback.php`
   - Cliquez sur "Register application"

2. **Récupérer les identifiants** :
   - Copiez le **Client ID**
   - Cliquez sur "Generate a new client secret" et copiez le **Client Secret**

3. **Mettre à jour le fichier `.env`** :
   ```env
   GITHUB_CLIENT_ID=votre_client_id
   GITHUB_CLIENT_SECRET=votre_client_secret
   ```

4. **Mettre à jour l'URL de redirection dans `config.php`** :
   Si votre application n'est pas sur `http://localhost:8000`, modifiez la ligne 41 de `config.php` :
   ```php
   define('GITHUB_REDIRECT_URI', 'http://votre-domaine.com/github_callback.php');
   ```

### Configuration Twilio (SMS - Optionnel)

Pour activer l'envoi de SMS :

1. **Créer un compte Twilio** : https://www.twilio.com/
2. **Récupérer les identifiants** depuis le dashboard Twilio :
   - Account SID
   - Auth Token
   - Numéro de téléphone Twilio

3. **Mettre à jour le fichier `.env`** :
   ```env
   TWILIO_SID=votre_account_sid
   TWILIO_TOKEN=votre_auth_token
   TWILIO_PHONE=+1234567890
   ```

##  Utilisation

### Démarrer le serveur de développement

```bash
php -S localhost:8000
```

Puis ouvrez votre navigateur sur : `http://localhost:8000`

### Comptes utilisateurs par défaut

L'application crée automatiquement deux comptes de test :

- **Utilisateur** : `test` / **Mot de passe** : `test`
- **Utilisateur** : `admin` / **Mot de passe** : `admin123`

### Flux d'authentification

1. **Connexion** (`login.php`) :
   - Entrez votre nom d'utilisateur et mot de passe
   - Ou cliquez sur "Se connecter avec GitHub"

2. **Choix de la méthode 2FA** (`verify_2fa.php`) :
   - **Email** : Recevez un code par email
   - **SMS** : Recevez un code par SMS (si configuré)
   - **Authenticator** : Scannez un QR Code ou utilisez votre application d'authentification

3. **Vérification du code** (`verify_code.php`) :
   - Entrez le code reçu ou généré par votre application

4. **Dashboard** (`dashboard.php`) :
   - Vous êtes connecté ! Votre session est gérée par un JWT

### Configuration TOTP (QR Code)

1. Lors de la première utilisation, choisissez "Application d'authentification"
2. Scannez le QR Code avec :
   - Google Authenticator
   - Microsoft Authenticator
   - Authy
   - FreeOTP
   - Ou toute autre application TOTP
3. Entrez le code à 6 chiffres généré par l'application pour activer TOTP

## Auteurs

- Alexis HU
- Mehdi BENCHRIF
- Ayoub Chleh
- Hugo DA ROCHA
- Mickaël LAHLOU



