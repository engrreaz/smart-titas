<?php
require_once '../db.php';
require_once '../jwt_helper.php';

// GET রিকোয়েস্ট থেকে প্যারামিটার নেওয়া
$type = $_GET['type'] ?? '';
$item_id = $_GET['item_id'] ?? 0;

error_log("Received request for type: $type, item_id: $item_id");

if (empty($type) || empty($item_id)) {
    sendResponse(["status" => "error", "message" => "Type and item_id are required"]);
}

// ইউজার লগইন করা থাকলে তার ভোট চেক করার জন্য টোকেন ভেরিফাই করা (অপশনাল)
$user = verifyJwtToken();
$user_id = $user ? $user['user_id'] : null;

try {
    // সঠিক (Correct) ভোটের সংখ্যা গণনা
    $stmt = $conn->prepare("SELECT COUNT(*) FROM verification_logs WHERE item_type = ? AND item_id = ? AND verify_val = 'correct'");
    $stmt->execute([$type, $item_id]);
    $correct_count = (int)$stmt->fetchColumn();

    // ভুল (Incorrect) ভোটের সংখ্যা গণনা
    $stmt = $conn->prepare("SELECT COUNT(*) FROM verification_logs WHERE item_type = ? AND item_id = ? AND verify_val = 'incorrect'");
    $stmt->execute([$type, $item_id]);
    $incorrect_count = (int)$stmt->fetchColumn();

    // বর্তমান ইউজার যদি ভোট দিয়ে থাকেন তবে তার ভোট স্ট্যাটাস বের করা
    $user_vote = null;
    if ($user_id) {
        $stmt = $conn->prepare("SELECT verify_val FROM verification_logs WHERE item_type = ? AND item_id = ? AND verified_by = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$type, $item_id, $user_id]);
        $user_vote = $stmt->fetchColumn();
    }

    // JSON রেসপন্স পাঠানো
    sendResponse([
        "status" => "success",
        "correctCount" => $correct_count,
        "incorrectCount" => $incorrect_count,
        "userVote" => $user_vote ?: null
    ]);
    error_log("Sent response for type: $type, item_id: $item_id, correct: $correct_count, incorrect: $incorrect_count, user_vote: " . ($user_vote ?? 'null'));

} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
