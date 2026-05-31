<?php
require_once 'db.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(["status" => "error", "message" => "Only POST method is allowed"]);
}

$input = getJsonInput();
$phone = $input['phone'] ?? '';
$password = $input['password'] ?? '';

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
        
        sendResponse([
            "status" => "success",
            "token" => $token,
            "user" => $user
        ]);
    } else {
        sendResponse(["status" => "error", "message" => "Invalid credentials"]);
    }
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>