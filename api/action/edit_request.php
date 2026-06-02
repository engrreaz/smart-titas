<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
$userId = $user['user_id'];

// Android sends data via FormUrlEncoded
$type = $_POST['type'] ?? null;
$itemId = $_POST['item_id'] ?? null;
$changesJson = $_POST['changes'] ?? null;
$deviceId = $_POST['device_id'] ?? null;

if (!$type || !$itemId || !$changesJson) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing required parameters"]);
}

$dd = $type . ' | ' . $itemId . ' | ' . $changesJson;
error_log("Edit Request Data: " . $dd);

try {
    // Validate if JSON is valid
    if (!json_decode($changesJson)) {
        throw new Exception("Invalid changes JSON format");
    }

    $stmt = $conn->prepare("INSERT INTO edit_requests (item_type, item_id, requested_by, changes, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$type, $itemId, $userId, $changesJson]);
    
    // Log in contributions
    $stmtLog = $conn->prepare("INSERT INTO contributions (user_id, item_type, item_id, action_type, status) VALUES (?, ?, ?, 'edit', 'pending')");
    $stmtLog->execute([$userId, $type, $itemId]);

    sendResponse(["status" => "success", "message" => "এডিট রিকোয়েস্ট জমা দেওয়া হয়েছে"]);

} catch (Exception $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>