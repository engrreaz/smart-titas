<?php
// db.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$host = "localhost";
$db_name = "eimbox_smart_titas"; // Adjust as necessary
$username = "eimbox_smart_user";       // Adjust as necessary
$password = "@@Rdno2026@@";           // Adjust as necessary

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Connection Error: " . $exception->getMessage()]);
    exit();
}

/**
 * Get the JSON payload from the request
 * @return array
 */
function getJsonInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?: [];
}

/**
 * Send JSON response and exit
 * @param array $data
 */
function sendResponse($data) {
    echo json_encode($data);
    exit();
}
?>