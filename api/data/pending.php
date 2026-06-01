<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
if ($user['role'] !== 'moderator' && $user['role'] !== 'super_admin') {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Unauthorized access"]);
}

try {
    // ১. নতুন এন্ট্রিগুলো খুঁজুন (Status: pending)
    $sql = "SELECT c.id as contribution_id, c.item_type as type, c.item_id, c.action_type, c.created_at as timestamp, u.name as contributor,
            CASE 
                WHEN c.item_type = 'official' THEN (SELECT name FROM officials WHERE id = c.item_id)
                WHEN c.item_type = 'institution' THEN (SELECT name FROM institutions WHERE id = c.item_id)
                WHEN c.item_type = 'business' THEN (SELECT name FROM businesses WHERE id = c.item_id)
                WHEN c.item_type = 'donor' THEN (SELECT name FROM blood_donors WHERE id = c.item_id)
                ELSE 'নতুন তথ্য'
            END as name,
            CASE 
                WHEN c.action_type = 'edit' THEN (SELECT changes FROM edit_requests WHERE item_type = c.item_type AND item_id = c.item_id AND status = 'pending' LIMIT 1)
                ELSE 'New Addition'
            END as info
            FROM contributions c 
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'pending'
            ORDER BY c.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();

    $pendingItems = [];
    foreach ($results as $row) {
        $pendingItems[] = [
            "id" => (int)$row['item_id'],
            "type" => $row['type'],
            "name" => $row['name'] ?: "তথ্য পাওয়া যায়নি",
            "info" => $row['action_type'] == 'edit' ? "সংশোধনের অনুরোধ" : "নতুন তথ্য যোগ",
            "contributor" => $row['contributor'],
            "timestamp" => $row['timestamp'],
            "data" => $row['info'] // This will contain changes JSON or description
        ];
    }
    
    sendResponse($pendingItems);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>