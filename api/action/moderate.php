<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
if ($user['role'] !== 'moderator' && $user['role'] !== 'super_admin') {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Unauthorized: Moderators only"]);
}

$item_type = $_POST['item_type'] ?? null;
$item_id = $_POST['item_id'] ?? null;
$action = $_POST['action'] ?? null; // 'approve' or 'reject'
$deviceId = $_POST['device_id'] ?? null;

if (!$item_type || !$item_id || !$action) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing parameters"]);
}

try {
    $conn->beginTransaction();

    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // ১. সংশ্লিষ্ট টেবিল আপডেট (e.g., officials, businesses)
    $allowed_tables = [
        'official' => 'officials',
        'institution' => 'institutions',
        'donor' => 'blood_donors',
        'professional' => 'professionals',
        'business' => 'businesses',
        'tourism' => 'tourism_places',
        'emergency' => 'emergency_contacts'
    ];

    $tableName = $allowed_tables[$item_type] ?? null;
    if ($tableName) {
        $stmt = $conn->prepare("UPDATE $tableName SET status = ? WHERE id = ?");
        $stmt->execute([$status, $item_id]);
    }

    // ২. contributions টেবিল আপডেট
    $stmtLog = $conn->prepare("UPDATE contributions SET status = ? WHERE item_type = ? AND item_id = ?");
    $stmtLog->execute([$status, $item_type, $item_id]);

    // ৩. পয়েন্ট যোগ করা (যদি অ্যাপ্রুভ হয়)
    if ($action === 'approve') {
        // কন্ট্রিবিউটর আইডি বের করা
        $stmtContrib = $conn->prepare("SELECT user_id FROM contributions WHERE item_type = ? AND item_id = ?");
        $stmtContrib->execute([$item_type, $item_id]);
        $contributorId = $stmtContrib->fetchColumn();

        if ($contributorId) {
            $points = 10; // ডিফল্ট ১০ পয়েন্ট
            $stmtPoints = $conn->prepare("UPDATE users SET trust_score = trust_score + ? WHERE id = ?");
            $stmtPoints->execute([$points, $contributorId]);
        }
    }

    $conn->commit();
    sendResponse(["status" => "success", "message" => "Item " . $status . " successfully"]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>