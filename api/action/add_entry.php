<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
$userId = $user['user_id'];

// Android sends data via FormUrlEncoded/Post fields
$type = $_POST['type'] ?? null;
$jsonData = $_POST['data'] ?? null;
$deviceId = $_POST['device_id'] ?? null;

if (!$type || !$jsonData) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing type or data"]);
}

try {
    $conn->beginTransaction();

    $data = json_decode($jsonData, true);
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }

    // Insert into specific table based on type
    $allowed_tables = [
        'official' => 'officials',
        'institution' => 'institutions',
        'donor' => 'blood_donors',
        'professional' => 'professionals',
        'business' => 'businesses',
        'tourism' => 'tourism_places',
        'emergency' => 'emergency_contacts'
    ];

    $tableName = $allowed_tables[$type] ?? null;
    if (!$tableName) {
        throw new Exception("Invalid entry type: " . $type);
    }

    // Prepare dynamic query
    $data['status'] = 'pending'; // All new entries are pending
    if ($type == 'official') $data['created_by'] = $userId;
    
    $columns = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), "?"));
    $values = array_values($data);

    $stmt = $conn->prepare("INSERT INTO $tableName ($columns) VALUES ($placeholders)");
    $stmt->execute($values);
    $itemId = $conn->lastInsertId();

    // Log in contributions table
    $stmtLog = $conn->prepare("INSERT INTO contributions (user_id, item_type, item_id, action_type, status) VALUES (?, ?, ?, 'add', 'pending')");
    $stmtLog->execute([$userId, $type, $itemId, 'add']);

    $conn->commit();
    sendResponse(["status" => "success", "message" => "Entry submitted for approval"]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>