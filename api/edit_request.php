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
$changes = $input['changes'] ?? null;

if (!$item_type || !$item_id || !is_array($changes) || empty($changes)) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing required parameters"]);
}

try {
    $changes_json = json_encode($changes);
    $stmt = $conn->prepare("INSERT INTO edit_requests (user_id, item_type, item_id, changes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['user_id'], $item_type, $item_id, $changes_json]);
    
    sendResponse(["status" => "success", "message" => "Edit request submitted for approval"]);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
