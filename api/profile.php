<?php
require_once 'db.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(["status" => "error", "message" => "Only POST method is allowed"]);
}

// Protected route
$authUser = requireAuth();

$input = getJsonInput();
$user_id = $input['user_id'] ?? $authUser['user_id'];

if (empty($user_id)) {
    sendResponse(["status" => "error", "message" => "User ID is required"]);
}

try {
    $stmt = $conn->prepare("SELECT id, name, phone, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt_stats = $conn->prepare("SELECT COUNT(*) as total_contributions FROM contributions WHERE user_id = ?");
        $stmt_stats->execute([$user_id]);
        $stats = $stmt_stats->fetch();

        $user['total_contributions'] = $stats['total_contributions'] ?? 0;

        sendResponse(["status" => "success", "data" => $user]);
    } else {
        sendResponse(["status" => "error", "message" => "User not found"]);
    }
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>