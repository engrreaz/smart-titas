<?php
require_once '../db.php';
require_once '../jwt_helper.php';

// লগ যাতে দেখা যায় ফাইলটি আদৌ হিট করছে কি না

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}


$user = requireAuth();

// Role check - allow admin, super_admin, and moderator
$allowed_roles = ['moderator', 'admin', 'super_admin'];
if (!in_array($user['role'], $allowed_roles)) {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Forbidden: Unauthorized role"]);
}

$input = getJsonInput() ?: [];

// অ্যাপের প্যারামিটার অনুযায়ী ডেটা রিসিভ
$item_type = $_POST['type'] ?? $input['type'] ?? $input['item_type'] ?? null;
$item_id = $_POST['item_id'] ?? $input['item_id'] ?? null;
$verification_level = $_POST['level'] ?? $input['level'] ?? $input['verification_level'] ?? null;



// অ্যাপের সিঙ্গুলার টাইপ থেকে ডাটাবেস টেবিল ম্যাপিং
$type_map = [
    'official' => 'officials',
    'institution' => 'institutions',
    'donor' => 'blood_donors',
    'professional' => 'professionals',
    'business' => 'businesses'
];

if (isset($type_map[$item_type])) {
    $item_type = $type_map[$item_type];
}

$allowed_tables = ['officials', 'institutions', 'blood_donors', 'professionals', 'businesses', 'emergency_contacts', 'notices'];

if (!$item_type || !in_array($item_type, $allowed_tables) || !$item_id || $verification_level === null) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Invalid parameters. Type: $item_type, ID: $item_id, Level: $verification_level"]);
}

try {
    $conn->beginTransaction();

    $sql = "UPDATE $item_type SET verification_level = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$verification_level, $item_id]);

    // Insert into verification_logs for history
    $log_stmt = $conn->prepare("INSERT INTO verification_logs (user_id, item_type, item_id, verification_level) VALUES (?, ?, ?, ?)");
    $log_stmt->execute([$user['user_id'], $item_type, $item_id, $verification_level]);
error_log("Verification updated: User ID: {$user['user_id']}, Item Type: $item_type, Item ID: $item_id, Level: $verification_level");
    $conn->commit();
    sendResponse(["status" => "success", "message" => "ভেরিফিকেশন লেভেল সফলভাবে আপডেট করা হয়েছে।"]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}