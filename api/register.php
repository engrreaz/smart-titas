<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(["status" => "error", "message" => "Only POST method is allowed"]);
}

$input = getJsonInput();
$name = $input['name'] ?? '';
$phone = $input['phone'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($name) || empty($phone) || empty($password)) {
    sendResponse(["status" => "error", "message" => "Name, phone, and password are required"]);
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        sendResponse(["status" => "error", "message" => "Phone number already registered"]);
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password, role) VALUES (?, ?, ?, ?, 'contributor')");
    $stmt->execute([$name, $phone, $email, $hashed_password]);

    sendResponse(["status" => "success", "message" => "User registered successfully"]);
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>