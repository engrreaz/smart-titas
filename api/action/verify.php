<?php
require_once '../db.php';
require_once '../jwt_helper.php';

error_log("verify.php accessed");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$user = requireAuth();

// Simple role check, adjust as per your actual role logic
if ($user['role'] !== 'moderator' && $user['role'] !== 'admin') {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Forbidden: Moderators only"]);
}
error_log("User authenticated: ID: " . $user['user_id'] . ", Role: " . $user['role']);
$input = getJsonInput();
$item_type = $input['item_type'] ?? null;
$item_id = $input['item_id'] ?? null;
$verification_level = $input['verification_level'] ?? null;
$d = $item_type . ", " . $item_id . ", " . $verification_level;
//  sendResponse(["status" => "error", "message" => "Invalid or missing parameters"]);
//  exit;

error_log($d );


$allowed_tables = ['officials', 'institutions', 'blood_donors', 'professionals', 'businesses', 'emergency_contacts', 'notices'];

if (!$item_type || !in_array($item_type, $allowed_tables) || !$item_id || $verification_level === null) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Invalid or missing parameters"]);
}

try {
    $conn->beginTransaction();

    $sql = "UPDATE $item_type SET verification_level = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$verification_level, $item_id]);

    $log_stmt = $conn->prepare("INSERT INTO verification_logs (user_id, item_type, item_id, verification_level) VALUES (?, ?, ?, ?)");
    $log_stmt->execute([$user['user_id'], $item_type, $item_id, $verification_level]);

    $conn->commit();

    sendResponse(["status" => "success", "message" => "Verification level updated"]);

} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
