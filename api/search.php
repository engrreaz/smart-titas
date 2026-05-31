<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$input = getJsonInput();
$query = $input['query'] ?? null;

if (!$query) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing search query"]);
}

$searchTerm = "%" . $query . "%";

try {
    $results = [];
    
    // Search officials
    $stmt = $conn->prepare("SELECT 'official' as type, id, name, phone, designation as subtitle FROM officials WHERE name LIKE ? OR phone LIKE ? OR designation LIKE ?");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $results = array_merge($results, $stmt->fetchAll());
    
    // Search institutions
    $stmt = $conn->prepare("SELECT 'institution' as type, id, name, phone, address as subtitle FROM institutions WHERE name LIKE ? OR phone LIKE ?");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = array_merge($results, $stmt->fetchAll());

    // Add more table searches as needed...
    
    sendResponse(["status" => "success", "data" => $results]);

} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
