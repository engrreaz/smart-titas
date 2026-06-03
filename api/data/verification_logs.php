<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$type = $_GET['type'] ?? '';
$item_id = $_GET['item_id'] ?? 0;

if (empty($type) || empty($item_id)) {
    sendResponse(["status" => "error", "message" => "Type and item_id are required"]);
}

$user = verifyJwtToken();
if (!$user || !in_array($user['role'], ['admin', 'super_admin', 'moderator'])) {
    sendResponse(["status" => "error", "message" => "Unauthorized"]);
}

// টাইপ নরমালাইজেশন (App type to DB table mapping)
$type_map = [
    'official' => 'officials',
    'institution' => 'institutions',
    'donor' => 'blood_donors',
    'professional' => 'professionals',
    'business' => 'businesses'
];
$normalized_type = $type_map[$type] ?? $type;

try {
    $stmt = $conn->prepare("
        SELECT vl.*, u.name as user_name 
        FROM verification_logs vl 
        LEFT JOIN users u ON vl.verified_by = u.id 
        WHERE vl.item_type = ? AND vl.item_id = ? 
        ORDER BY vl.id DESC
    ");
    $stmt->execute([$normalized_type, $item_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(["status" => "success", "data" => $logs]);
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
