<?php
require_once '../db.php';
require_once '../jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$user = requireAuth();
$user_id = $user['user_id'];

$type = $_POST['type'] ?? '';
$item_id = $_POST['item_id'] ?? 0;
$vote = $_POST['status'] ?? ''; // অ্যাপ থেকে আসা ভোটের মান (correct/incorrect)

$type_map = [
    'official' => 'officials',
    'institution' => 'institutions',
    'donor' => 'blood_donors',
    'professional' => 'professionals',
    'business' => 'businesses'
];
$normalized_type = $type_map[$type] ?? $type;

if (empty($normalized_type) || empty($item_id) || empty($vote)) {
    sendResponse(["status" => "error", "message" => "Missing parameters"]);
}

try {
    // একই ইউজারের আগের ভোট থাকলে সেটি আপডেট হবে, অন্যথায় নতুন ইনসার্ট হবে
    $stmt = $conn->prepare("SELECT id FROM verification_logs WHERE item_type = ? AND item_id = ? AND verified_by = ? AND verify_val != ''");
    $stmt->execute([$normalized_type, $item_id, $user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $conn->prepare("UPDATE verification_logs SET verify_val = ?, status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$vote, $existing['id']]);
    } else {
        $stmt = $conn->prepare("INSERT INTO verification_logs (verified_by, item_type, item_id, verify_val, status) VALUES (?, ?, ?, ?, 'approved')");
        $stmt->execute([$user_id, $normalized_type, $item_id, $vote]);
    }

    sendResponse(["status" => "success", "message" => "ধন্যবাদ! আপনার মতামত গ্রহণ করা হয়েছে।"]);
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
