<?php
require_once '../db.php';
require_once '../jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(["status" => "error", "message" => "Only POST method is allowed"]);
}

$input = getJsonInput();
$phone = $input['phone'] ?? '';
$password = $input['password'] ?? '';
$device_id = $input['device_id'] ?? '';

if (empty($phone) || empty($password)) {
    sendResponse(["status" => "error", "message" => "Phone and password are required"]);
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $token = generateJwtToken($user['id'], $user['role']);
        
        $userId = $user['id'];
        
        // Fetch stats
        $stmt_total = $conn->prepare("SELECT COUNT(*) FROM contributions WHERE user_id = ?");
        $stmt_total->execute([$userId]);
        $total_contributions = $stmt_total->fetchColumn();

        // Assuming approved entries are those that resulted in points > 0 or we can check the status of items?
        // For now, let's just use placeholder or count from contributions if it means successful actions.
        $stmt_verified = $conn->prepare("SELECT COUNT(*) FROM contributions WHERE user_id = ? AND action_type = 'verify'");
        $stmt_verified->execute([$userId]);
        $verified_entries = $stmt_verified->fetchColumn();

        $userResponse = [
            "id" => (int)$user['id'],
            "name" => $user['name'],
            "phone" => $user['phone'],
            "email" => $user['email'],
            "image" => $user['image'],
            "role" => $user['role'],
            "trust_score" => (int)$user['trust_score'],
            "level_name" => $user['level_name'],
            "total_contributions" => (int)$total_contributions,
            "approved_entries" => (int)$total_contributions, // Placeholder logic
            "verified_entries" => (int)$verified_entries,
            "status" => $user['status'],
            "created_at" => $user['created_at']
        ];
        
        sendResponse([
            "status" => "success",
            "token" => $token,
            "user" => $userResponse
        ]);
    } else {
        sendResponse(["status" => "error", "message" => "Invalid credentials"]);
    }
} catch (PDOException $e) {
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>