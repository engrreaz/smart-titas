<?php
require_once '../db.php';
require_once '../jwt_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit;
}

// JSON or POST input handle
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Input sanitize & validation
$type = trim($input['type'] ?? '');
$item_id = isset($input['item_id']) ? (int)$input['item_id'] : 0;
$status = trim($input['status'] ?? '');
$device_id = trim($input['device_id'] ?? '');

error_log("Received verify_log: Type: $type, Item ID: $item_id, Status: $status, Device ID: $device_id");

// basic validation
if ($type === '' || $item_id <= 0 || $status === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Required fields missing"
    ]);
    exit;
}

// JWT verify
$user = verifyJwtToken();
$user_id = isset($user['user_id']) ? (int)$user['user_id'] : 0;

// status whitelist (security)
$allowed_status = ['correct', 'incorrect'];
if (!in_array($status, $allowed_status)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid status value"
    ]);
    exit;
}

// SQL
$sql = "INSERT INTO verification_logs 
        (user_id, item_type, item_id, verify_val, device_id) 
        VALUES (?, ?, ?, ?, ?)";

try {
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$user_id, $type, $item_id, $status, $device_id])) {
        echo json_encode([
            "status" => "success",
            "message" => "ধন্যবাদ! আপনার মতামত গ্রহণ করা হয়েছে।"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Database error",
            "debug" => $stmt->errorInfo()
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error",
        "debug" => $e->getMessage()
    ]);
}
?>