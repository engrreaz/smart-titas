<?php
require_once '../db.php';
require_once '../jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(["status" => "error", "message" => "Method not allowed"]);
}

// Protected route
$authUser = requireAuth();

$input = getJsonInput();
$user_id = $input['user_id'] ?? $authUser['user_id'];

if (empty($user_id)) {
    sendResponse(["status" => "error", "message" => "User ID is required"]);
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        unset($user['password']); // Safety
        
        // Fetch stats from contributions table
        $stmt_total = $conn->prepare("SELECT COUNT(*) FROM contributions WHERE user_id = ?");
        $stmt_total->execute([$user_id]);
        $total_contributions = $stmt_total->fetchColumn();

        $stmt_verified = $conn->prepare("SELECT COUNT(*) FROM contributions WHERE user_id = ? AND action_type = 'verify'");
        $stmt_verified->execute([$user_id]);
        $verified_entries = $stmt_verified->fetchColumn();

        $user['total_contributions'] = (int)$total_contributions;
        $user['approved_entries'] = (int)$total_contributions; // Simplified logic
        $user['verified_entries'] = (int)$verified_entries;
        
        // Map fields to match UserEntity.kt
        $user['id'] = (int)$user['id'];
        $user['trust_score'] = (int)$user['trust_score'];

        sendResponse(["status" => "success", "data" => $user]);
    } else {
        sendResponse(["status" => "error", "message" => "User not found"]);
    }
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>