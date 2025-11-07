<?php
require_once 'config.php';

function createJWT($username, $data = []) {
    $issuedAt = time();
    $expiration = $issuedAt + JWT_EXPIRATION;
    
    $payload = [
        'iss' => 'A2F_App',           
        'iat' => $issuedAt,           
        'exp' => $expiration,          
        'sub' => $username,          
        'data' => $data               
    ];
    
    return base64UrlEncode(json_encode(['alg' => JWT_ALGORITHM, 'typ' => 'JWT'])) . '.' .
           base64UrlEncode(json_encode($payload)) . '.' .
           base64UrlEncode(hash_hmac('sha256', 
               base64UrlEncode(json_encode(['alg' => JWT_ALGORITHM, 'typ' => 'JWT'])) . '.' .
               base64UrlEncode(json_encode($payload)), 
               JWT_SECRET, true));
}

function verifyJWT($jwt) {
    $parts = explode('.', $jwt);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    $validSignature = base64UrlEncode(hash_hmac('sha256', 
        $header . '.' . $payload, 
        JWT_SECRET, true));
    
    if ($signature !== $validSignature) {
        return false;
    }

    $payloadData = json_decode(base64UrlDecode($payload), true);
    
    if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
        return false;
    }
    
    return $payloadData;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function getJWTFromRequest() {
    // Vérifier dans les cookies
    if (isset($_COOKIE['auth_token'])) {
        return $_COOKIE['auth_token'];
    }
    
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

function requireAuth() {
    $jwt = getJWTFromRequest();
    
    if (!$jwt) {
        header('Location: login.php');
        exit();
    }
    
    $payload = verifyJWT($jwt);
    
    if (!$payload) {
        setcookie('auth_token', '', time() - 3600, '/');
        header('Location: login.php');
        exit();
    }
    
    return $payload;
}
?>