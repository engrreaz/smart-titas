<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
$userId = $user['user_id'];

try {
    // Join logic to get names from different tables based on item_type
    $sql = "SELECT c.id, c.item_type as type, c.status, c.created_at as timestamp,
            CASE 
                WHEN c.item_type = 'official' THEN (SELECT name FROM officials WHERE id = c.item_id)
                WHEN c.item_type = 'institution' THEN (SELECT name FROM institutions WHERE id = c.item_id)
                WHEN c.item_type = 'donor' THEN (SELECT name FROM blood_donors WHERE id = c.item_id)
                WHEN c.item_type = 'professional' THEN (SELECT name FROM professionals WHERE id = c.item_id)
                WHEN c.item_type = 'business' THEN (SELECT name FROM businesses WHERE id = c.item_id)
                WHEN c.item_type = 'tourism' THEN (SELECT name FROM tourism_places WHERE id = c.item_id)
                WHEN c.item_type = 'emergency' THEN (SELECT service_name FROM emergency_contacts WHERE id = c.item_id)
                ELSE 'নতুন তথ্য'
            END as name
            FROM contributions c 
            WHERE c.user_id = ? 
            ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    $contributions = $stmt->fetchAll();

    sendResponse($contributions);
} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>