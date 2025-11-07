<?php
require_once 'config.php';

// Fonction pour créer un JWT
function createJWT($username, $data = []) {
    $issuedAt = time();
    $expiration = $issuedAt + JWT_EXPIRATION;
    
    $payload = [
        'iss' => 'A2F_App',           // Issuer
        'iat' => $issuedAt,            // Issued at
        'exp' => $expiration,          // Expiration
        'sub' => $username,            // Subject (username)
        'data' => $data                // Données supplémentaires
    ];
    
    return base64UrlEncode(json_encode(['alg' => JWT_ALGORITHM, 'typ' => 'JWT'])) . '.' .
           base64UrlEncode(json_encode($payload)) . '.' .
           base64UrlEncode(hash_hmac('sha256', 
               base64UrlEncode(json_encode(['alg' => JWT_ALGORITHM, 'typ' => 'JWT'])) . '.' .
               base64UrlEncode(json_encode($payload)), 
               JWT_SECRET, true));
}

// Fonction pour vérifier un JWT
function verifyJWT($jwt) {
    $parts = explode('.', $jwt);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Vérifier la signature
    $validSignature = base64UrlEncode(hash_hmac('sha256', 
        $header . '.' . $payload, 
        JWT_SECRET, true));
    
    if ($signature !== $validSignature) {
        return false;
    }
    
    // Décoder le payload
    $payloadData = json_decode(base64UrlDecode($payload), true);
    
    // Vérifier l'expiration
    if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
        return false;
    }
    
    return $payloadData;
}

// Encoder en Base64 URL-safe
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Décoder depuis Base64 URL-safe
function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

// Fonction pour obtenir le JWT depuis les cookies ou headers
function getJWTFromRequest() {
    // Vérifier dans les cookies
    if (isset($_COOKIE['auth_token'])) {
        return $_COOKIE['auth_token'];
    }
    
    // Vérifier dans les headers Authorization
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

// Fonction pour vérifier l'authentification
function requireAuth() {
    $jwt = getJWTFromRequest();
    
    if (!$jwt) {
        header('Location: login.php');
        exit();
    }
    
    $payload = verifyJWT($jwt);
    
    if (!$payload) {
        // Token invalide ou expiré
        setcookie('auth_token', '', time() - 3600, '/');
        header('Location: login.php');
        exit();
    }
    
    return $payload;
}
?>