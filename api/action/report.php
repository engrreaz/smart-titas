<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
$userId = $user['user_id'];

// Android sends via FormUrlEncoded
$itemType = $_POST['item_type'] ?? null;
$itemId = $_POST['item_id'] ?? null;
$reason = $_POST['reason'] ?? null;
$deviceId = $_POST['device_id'] ?? null;

if (!$itemType || !$itemId || !$reason) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing required parameters"]);
}

try {
    $stmt = $conn->prepare("INSERT INTO reports (user_id, item_type, item_id, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $itemType, $itemId, $reason]);
    
    sendResponse(["status" => "success", "message" => "রিপোর্ট জমা দেওয়া হয়েছে"]);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>