<?php
session_start();

// Fonction pour charger les variables d'environnement depuis .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Erreur : Le fichier .env n'existe pas à l'emplacement : $path");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Séparer nom=valeur
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

// Charger le fichier .env
loadEnv(__DIR__ . '/.env');

// Configuration GitHub OAuth
define('GITHUB_CLIENT_ID', $_ENV['GITHUB_CLIENT_ID'] ?? '');
define('GITHUB_CLIENT_SECRET', $_ENV['GITHUB_CLIENT_SECRET'] ?? '');
define('GITHUB_REDIRECT_URI', 'http://localhost/A2F%20App/github_callback.php'); // ← Attention à l'espace encodé !

// Vérifier que les constantes sont bien définies
if (empty(GITHUB_CLIENT_ID) || empty(GITHUB_CLIENT_SECRET)) {
    die("Erreur : GITHUB_CLIENT_ID ou GITHUB_CLIENT_SECRET non défini dans le fichier .env");
}

// Utilisateurs locaux (optionnel)
$users = [
    'test' => [
        'password' => password_hash('test', PASSWORD_DEFAULT),
        'email' => 'test@example.com',
        'phone' => '+33612345678'
    ],
    'admin' => [
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'email' => 'admin@example.com',
        'phone' => '+33687654321'
    ]
];
?>