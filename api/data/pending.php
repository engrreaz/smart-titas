<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
if ($user['role'] !== 'moderator' && $user['role'] !== 'super_admin') {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Unauthorized access"]);
}

$type = $_POST['type'] ?? null;

try {
    $sql = "SELECT c.id, c.item_type as type, c.item_id, c.created_at as timestamp, u.name as contributor,
            CASE 
                WHEN c.item_type = 'official' THEN (SELECT name FROM officials WHERE id = c.item_id)
                WHEN c.item_type = 'institution' THEN (SELECT name FROM institutions WHERE id = c.item_id)
                WHEN c.item_type = 'business' THEN (SELECT name FROM businesses WHERE id = c.item_id)
                ELSE 'নতুন তথ্য'
            END as name
            FROM contributions c 
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'pending'";
            
    if ($type) {
        $sql .= " AND c.item_type = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$type]);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    
    $pending = $stmt->fetchAll();
    sendResponse($pending);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>