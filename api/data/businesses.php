<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$input = getJsonInput();
$last_sync = isset($input['last_sync']) ? (int)$input['last_sync'] : 0;

try {
    $stmt = $conn->prepare("SELECT * FROM businesses WHERE UNIX_TIMESTAMP(updated_at) > ? AND status = 'approved'");
    $stmt->execute([$last_sync]);
    $records = $stmt->fetchAll();
    
    $formatted_records = array_map(function($record) {
        if(isset($record['verification_level'])) {
            $record['verificationLevel'] = $record['verification_level'];
            unset($record['verification_level']);
        }
        return $record;
    }, $records);

    sendResponse($formatted_records);
} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>