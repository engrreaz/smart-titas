<?php
require_once '../db.php';
require_once '../jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(["status" => "error", "message" => "Only POST method is allowed"]);
}

$input = getJsonInput();
$phone = $input['phone'] ?? '';
$password = $input['password'] ?? '';
$device_id = $input['device_id'] ?? '';

if (empty($phone) || empty($password)) {
    sendResponse(["status" => "error", "message" => "Phone and password are required"]);
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $token = generateJwtToken($user['id'], $user['role']);
        
        // Remove password from response
        unset($user['password']);
        
        // Map fields to match API spec
        $userResponse = [
            "id" => (int)$user['id'],
            "name" => $user['name'],
            "phone" => $user['phone'],
            "role" => $user['role'],
            "trustScore" => isset($user['trust_score']) ? (int)$user['trust_score'] : 0,
            "levelName" => isset($user['level_name']) ? $user['level_name'] : 'Bronze'
        ];
        
        sendResponse([
            "status" => "success",
            "token" => $token,
            "user" => $userResponse
        ]);
    } else {
        sendResponse(["status" => "error", "message" => "Invalid credentials"]);
    }
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>