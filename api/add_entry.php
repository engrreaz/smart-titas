<?php
require_once 'db.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$user = requireAuth();

$input = getJsonInput();
$table_name = $input['table_name'] ?? null;
$data = $input['data'] ?? null;

// Allowlist of tables
$allowed_tables = ['officials', 'institutions', 'blood_donors', 'professionals', 'businesses', 'emergency_contacts', 'notices'];

if (!$table_name || !in_array($table_name, $allowed_tables)) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Invalid or missing table name"]);
}

if (!is_array($data) || empty($data)) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Invalid or missing data"]);
}

try {
    $conn->beginTransaction();

    $columns = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), "?"));
    $values = array_values($data);

    $sql = "INSERT INTO $table_name ($columns) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($values);
    
    $item_id = $conn->lastInsertId();

    // Log contribution
    $log_stmt = $conn->prepare("INSERT INTO contributions (user_id, item_type, item_id, action) VALUES (?, ?, ?, 'add')");
    $log_stmt->execute([$user['user_id'], $table_name, $item_id]);

    $conn->commit();

    sendResponse(["status" => "success", "message" => "Entry added successfully", "id" => $item_id]);

} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
