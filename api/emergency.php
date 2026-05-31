<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$input = getJsonInput();
$action = $input['action'] ?? 'list';

if ($action === 'list') {
    try {
        $stmt = $conn->prepare("SELECT * FROM emergency_contacts");
        $stmt->execute();
        $data = $stmt->fetchAll();
        sendResponse(["status" => "success", "data" => $data]);
    } catch (PDOException $e) {
        http_response_code(500);
        sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    sendResponse(["status" => "error", "message" => "Invalid action"]);
}
