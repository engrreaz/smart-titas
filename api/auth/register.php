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

if (empty($name) || empty($phone) || empty($password) || empty($device_id)) {
    sendResponse(["status" => "error", "message" => "Name, phone, password, and device_id are required"]);
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        sendResponse(["status" => "error", "message" => "Phone number already registered"]);
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert without device_id as it's not in the Instruction.md SQL structure
    $stmt = $conn->prepare("INSERT INTO users (name, phone, password, email, role, trust_score, level_name, status, device_id) VALUES (?, ?, ?, ?, 'contributor', 0, 'Bronze', 'active', ?)");
    $stmt->execute([$name, $phone, $hashed_password, $email, $device_id]);

    sendResponse(["status" => "success", "message" => "User registered successfully"]);
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>