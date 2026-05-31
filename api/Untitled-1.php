<?php
// jwt_helper.php
// Note: This is a basic JWT implementation for demonstration. 
// For production, use a robust library like firebase/php-jwt.

define('JWT_SECRET', 'your_super_secret_key_here');

function generateJwtToken($userId, $role) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode(['user_id' => $userId, 'role' => $role, 'exp' => time() + 86400]); // 1 day
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verifyJwtToken() {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null);
    
    if ($authHeader) {
        $matches = array();
        preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
        if (isset($matches[1])) {
            $token = $matches[1];
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                // In a real scenario, you would verify the signature here.
                $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET, true);
                $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
                
                if (hash_equals($expectedSignature, $parts[2])) {
                     $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                     if ($payload['exp'] >= time()) {
                         return $payload; 
                     }
                }
            }
        }
    }
    return false;
}

function requireAuth() {
    $user = verifyJwtToken();
    if (!$user) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access. Invalid or missing token."]);
        exit();
    }
    return $user;
}
?>