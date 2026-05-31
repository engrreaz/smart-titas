<?php
require_once 'db.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$user = requireAuth();

$input = getJsonInput();
$item_type = $input['item_type'] ?? null;
$item_id = $input['item_id'] ?? null;
$reason = $input['reason'] ?? null;

if (!$item_type || !$item_id || !$reason) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing required parameters"]);
}

try {
    $stmt = $conn->prepare("INSERT INTO reports (user_id, item_type, item_id, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['user_id'], $item_type, $item_id, $reason]);
    
    sendResponse(["status" => "success", "message" => "Report submitted successfully"]);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
