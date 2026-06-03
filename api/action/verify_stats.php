<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$type = $_GET['type'] ?? '';
$item_id = $_GET['item_id'] ?? 0;

if (empty($type) || empty($item_id)) {
    sendResponse(["status" => "error", "message" => "Type and item_id are required"]);
}

// App types to DB table names mapping
$type_map = [
    'official' => 'officials',
    'institution' => 'institutions',
    'donor' => 'blood_donors',
    'professional' => 'professionals',
    'business' => 'businesses'
];
$normalized_type = $type_map[$type] ?? $type;

$user = verifyJwtToken();
$user_id = $user ? $user['user_id'] : null;

try {
    // Count votes from verify_val field
    $stmt = $conn->prepare("SELECT COUNT(*) FROM verification_logs WHERE item_type = ? AND item_id = ? AND verify_val = 'correct'");
    $stmt->execute([$normalized_type, $item_id]);
    $correct_count = (int) $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM verification_logs WHERE item_type = ? AND item_id = ? AND verify_val = 'incorrect'");
    $stmt->execute([$normalized_type, $item_id]);
    $incorrect_count = (int) $stmt->fetchColumn();

    // User's own vote
    $user_vote = null;
    if ($user_id) {
        $stmt = $conn->prepare("SELECT verify_val FROM verification_logs WHERE item_type = ? AND item_id = ? AND verified_by = ? AND verify_val != '' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$normalized_type, $item_id, $user_id]);
        $user_vote = $stmt->fetchColumn();
    }

    sendResponse([
        "status" => "success",
        "correctCount" => $correct_count,
        "incorrectCount" => $incorrect_count,
        "userVote" => $user_vote ?: null
    ]);

} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
