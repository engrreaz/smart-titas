<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
if ($user['role'] !== 'moderator' && $user['role'] !== 'super_admin') {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Unauthorized access"]);
}

$item_type = $_POST['item_type'] ?? null;
$item_id = $_POST['item_id'] ?? null;
$action = $_POST['action'] ?? null; // 'approve' or 'reject'
$deviceId = $_POST['device_id'] ?? null;

if (!$item_type || !$item_id || !$action) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing parameters"]);
}

$dt = $item_type . ": " . $item_id . ", Action: " . $action . ", Device ID: " . $deviceId;


try {
    $conn->beginTransaction();

    $status = ($action === 'approve') ? 'approved' : 'rejected';

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

    // Check if this is an Edit Request or a New Entry
    $stmtCheck = $conn->prepare("SELECT action_type, user_id FROM contributions WHERE item_type = ? AND item_id = ? AND status = 'pending' LIMIT 1");
    $stmtCheck->execute([$item_type, $item_id]);
    $contribution = $stmtCheck->fetch();

    if (!$contribution) {
        throw new Exception("Contribution record not found or already processed.");
    }

    $actionType = $contribution['action_type'];
    $contributorId = $contribution['user_id'];

    if ($actionType === 'edit') {
        // Handle Edit Request
        if ($action === 'approve') {
            $stmtEdit = $conn->prepare("SELECT changes FROM edit_requests WHERE item_type = ? AND id = ? AND status = 'pending' LIMIT 1");
            $stmtEdit->execute([$item_type, $item_id]);
            $editRequest = $stmtEdit->fetch();

            if ($editRequest) {
                $changes = json_decode($editRequest['changes'], true);
                if ($changes && $tableName) {
                    $setClauses = [];
                    $params = [];
                    foreach ($changes as $key => $value) {
                        $setClauses[] = "$key = ?";
                        $params[] = $value;
                    }
                    $params[] = $item_id;
                    $updateSql = "UPDATE $tableName SET " . implode(", ", $setClauses) . " WHERE id = ?";
                    $stmtUpdate = $conn->prepare($updateSql);
                    $stmtUpdate->execute($params);
                }
            }
        }
        // Update edit_requests table
        $stmtUpdateEdit = $conn->prepare("UPDATE edit_requests SET status = ? WHERE item_type = ? AND item_id = ? AND status = 'pending'");
        $stmtUpdateEdit->execute([$status, $item_type, $item_id]);

    } else {
        // Handle New Entry (Add)
        if ($tableName) {
            $stmtUpdateItem = $conn->prepare("UPDATE $tableName SET status = ? WHERE id = ?");
            $stmtUpdateItem->execute([$status, $item_id]);
        }
    }

    // Update contributions table status
    $stmtLog = $conn->prepare("UPDATE contributions SET status = ? WHERE item_type = ? AND item_id = ? AND status = 'pending'");
    $stmtLog->execute([$status, $item_type, $item_id]);

    // Award Points for Approval
    if ($action === 'approve') {
        $points = ($actionType === 'edit') ? 5 : 10;
        $stmtPoints = $conn->prepare("UPDATE users SET trust_score = trust_score + ?, total_contributions = total_contributions + 1 WHERE id = ?");
        $stmtPoints->execute([$points, $contributorId]);
    }

    $conn->commit();
    sendResponse(["status" => "success", "message" => "Item successfully " . $status]);

} catch (Exception $e) {
    if ($conn->inTransaction())
        $conn->rollBack();
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage() . ', ' . $dt]);
}
?>