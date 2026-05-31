<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$input = getJsonInput();
$last_sync_timestamp = $input['last_sync_timestamp'] ?? null;

if (!$last_sync_timestamp) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing last_sync_timestamp"]);
}

try {
    $data = [];
    $tables = ['officials', 'institutions', 'blood_donors', 'professionals', 'businesses', 'emergency_contacts', 'notices'];
    
    // Assuming all tables have a column updated_at
    foreach ($tables as $table) {
        // First check if table exists and has updated_at column to avoid crashing if schema isn't fully ready
        try {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE updated_at >= ? OR created_at >= ?");
            $stmt->execute([$last_sync_timestamp, $last_sync_timestamp]);
            $data[$table] = $stmt->fetchAll();
        } catch(PDOException $e) {
             // Handle gracefully if table doesn't have updated_at or created_at
             $data[$table] = [];
        }
    }
    
    sendResponse(["status" => "success", "data" => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
