<?php
require_once '../db.php';
require_once '../jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$user = requireAuth();
$allowed_roles = ['moderator', 'admin', 'super_admin'];
if (!in_array($user['role'], $allowed_roles)) {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Forbidden: Unauthorized"]);
}

$type = $_POST['type'] ?? '';
$item_id = $_POST['item_id'] ?? 0;
$level = $_POST['level'] ?? '';

$type_map = [
    'official' => 'officials',
    'institution' => 'institutions',
    'donor' => 'blood_donors',
    'professional' => 'professionals',
    'business' => 'businesses'
];
$normalized_type = $type_map[$type] ?? $type;

if (empty($normalized_type) || empty($item_id) || empty($level)) {
    sendResponse(["status" => "error", "message" => "Missing parameters"]);
}

try {
    $conn->beginTransaction();

    // মূল টেবিলে লেভেল আপডেট করা
    $stmt = $conn->prepare("UPDATE $normalized_type SET verification_level = ? WHERE id = ?");
    $stmt->execute([$level, $item_id]);

    // লগ টেবিলে ইনসার্ট করা (ভোট নয়, লেভেল সেটিং)
    $log_stmt = $conn->prepare("INSERT INTO verification_logs (verified_by, item_type, item_id, verification_level, status, verify_val) VALUES (?, ?, ?, ?, 'approved', '')");
    $log_stmt->execute([$user['user_id'], $normalized_type, $item_id, $level]);

    $conn->commit();
    sendResponse(["status" => "success", "message" => "ভেরিফিকেশন লেভেল সফলভাবে আপডেট করা হয়েছে।"]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
