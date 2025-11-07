<?php
session_start();

// Charger .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Erreur : Le fichier .env n'existe pas");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

loadEnv(__DIR__ . '/.env');

// Configuration JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'votre_secret_super_securise_changez_moi');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 3600); // 1 heure

// Configuration Twilio
define('TWILIO_SID', $_ENV['TWILIO_SID'] ?? '');
define('TWILIO_TOKEN', $_ENV['TWILIO_TOKEN'] ?? '');
define('TWILIO_PHONE', $_ENV['TWILIO_PHONE'] ?? '');

// Configuration Email (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');

// Configuration GitHub OAuth
define('GITHUB_CLIENT_ID', $_ENV['GITHUB_CLIENT_ID'] ?? '');
define('GITHUB_CLIENT_SECRET', $_ENV['GITHUB_CLIENT_SECRET'] ?? '');
define('GITHUB_REDIRECT_URI', 'http://localhost:8000/github_callback.php');

// Fichier utilisateurs (base de données JSON temporaire)
define('USERS_FILE', __DIR__ . '/users.json');

// Fonction pour charger les utilisateurs
function loadUsers() {
    if (!file_exists(USERS_FILE)) {
        // Créer le fichier avec des utilisateurs par défaut
        $defaultUsers = [
            'test' => [
                'password' => password_hash('test', PASSWORD_DEFAULT),
                'email' => 'test@example.com',
                'phone' => '+33612345678',
                'totp_secret' => null,
                'totp_enabled' => false
            ],
            'admin' => [
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'email' => 'admin@example.com',
                'phone' => '+33687654321',
                'totp_secret' => null,
                'totp_enabled' => false
            ]
        ];
        file_put_contents(USERS_FILE, json_encode($defaultUsers, JSON_PRETTY_PRINT));
    }
    
    return json_decode(file_get_contents(USERS_FILE), true);
}

// Fonction pour sauvegarder les utilisateurs
function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

// Charger les utilisateurs
$users = loadUsers();
?>