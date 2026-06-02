<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
if ($user['role'] !== 'moderator' && $user['role'] !== 'super_admin') {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Unauthorized access"]);
}

$allowed_tables = [
    'official' => 'officials',
    'institution' => 'institutions',
    'donor' => 'blood_donors',
    'professional' => 'professionals',
    'business' => 'businesses',
    'tourism' => 'tourism_places',
    'emergency' => 'emergency_contacts'
];

try {
    $sql = "SELECT c.id as contribution_id, c.item_type as type, c.item_id, c.action_type, c.created_at as timestamp, u.name as contributor,
            CASE 
                WHEN c.item_type = 'official' THEN (SELECT name FROM officials WHERE id = c.item_id)
                WHEN c.item_type = 'institution' THEN (SELECT name FROM institutions WHERE id = c.item_id)
                WHEN c.item_type = 'business' THEN (SELECT name FROM businesses WHERE id = c.item_id)
                WHEN c.item_type = 'donor' THEN (SELECT name FROM blood_donors WHERE id = c.item_id)
                WHEN c.item_type = 'professional' THEN (SELECT name FROM professionals WHERE id = c.item_id)
                WHEN c.item_type = 'tourism' THEN (SELECT name FROM tourism_places WHERE id = c.item_id)
                WHEN c.item_type = 'emergency' THEN (SELECT name FROM emergency_contacts WHERE id = c.item_id)
                ELSE 'নতুন তথ্য'
            END as name,
            CASE 
                WHEN c.action_type = 'edit' THEN (SELECT changes FROM edit_requests WHERE item_type = c.item_type AND item_id = c.item_id AND status = 'pending' LIMIT 1)
                ELSE NULL
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
        $itemType = $row['type'];
        $itemId = $row['item_id'];
        $actionType = $row['action_type'];
        $rawInfo = $row['info'];
        $formattedData = [];

        $tableName = $allowed_tables[$itemType] ?? null;

        if ($actionType == 'add' && $tableName) {
            $stmtData = $conn->prepare("SELECT * FROM $tableName WHERE id = ?");
            $stmtData->execute([$itemId]);
            $itemData = $stmtData->fetch(PDO::FETCH_ASSOC);
            if ($itemData) {
                unset($itemData['id'], $itemData['status'], $itemData['created_at'], $itemData['updated_at'], $itemData['created_by'], $itemData['image']);
                foreach ($itemData as $key => $val) {
                    if ($val) $formattedData[$key] = ["val" => (string)$val, "changed" => false];
                }
            }
        } elseif ($actionType == 'edit' && $tableName) {
            $stmtOld = $conn->prepare("SELECT * FROM $tableName WHERE id = ?");
            $stmtOld->execute([$itemId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);
            
            $newData = json_decode($rawInfo, true);
            if ($oldData && $newData) {
                foreach ($newData as $key => $newVal) {
                    if ($key == 'image' || $key == 'id') continue;
                    
                    $oldVal = $oldData[$key] ?? '';
                    if ($oldVal === null) $oldVal = '';
                    if ($newVal === null) $newVal = '';
                    
                    $isChanged = trim((string)$oldVal) !== trim((string)$newVal);
                    if ($isChanged) {
                        $formattedData[$key] = ["old" => (string)$oldVal, "new" => (string)$newVal, "changed" => true];
                    } else {
                        if ($newVal) $formattedData[$key] = ["val" => (string)$newVal, "changed" => false];
                    }
                }
            }
        }

        $pendingItems[] = [
            "id" => (int)$itemId,
            "type" => $itemType,
            "name" => $row['name'] ?: "নাম পাওয়া যায়নি",
            "info" => $actionType == 'edit' ? "সংশোধনের অনুরোধ" : "নতুন তথ্য যোগ",
            "contributor" => $row['contributor'],
            "timestamp" => $row['timestamp'],
            "data" => json_encode($formattedData, JSON_UNESCAPED_UNICODE)
        ];
    }
    
    sendResponse($pendingItems);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>