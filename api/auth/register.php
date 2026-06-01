<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(["status" => "error", "message" => "Only POST method is allowed"]);
}

$input = getJsonInput();
$name = $input['name'] ?? '';
$phone = $input['phone'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$device_id = $input['device_id'] ?? '';

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
    
    // As per spec: INSERT INTO users (name, phone, password, email, role, device_id) VALUES (?, ?, ?, ?, 'contributor', ?)
    $stmt = $conn->prepare("INSERT INTO users (name, phone, password, email, role, device_id) VALUES (?, ?, ?, ?, 'contributor', ?)");
    $stmt->execute([$name, $phone, $hashed_password, $email, $device_id]);

    sendResponse(["status" => "success", "message" => "User registered successfully"]);
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>