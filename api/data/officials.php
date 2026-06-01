<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$input = getJsonInput();
$last_sync = isset($input['last_sync']) ? (int)$input['last_sync'] : 0;

try {
    // শুধুমাত্র approved ডেটা এবং last_sync এর পরের ডেটা নিয়ে আসবে
    $stmt = $conn->prepare("SELECT * FROM officials WHERE UNIX_TIMESTAMP(updated_at) > ? AND status = 'approved'");
    $stmt->execute([$last_sync]);
    $records = $stmt->fetchAll();
    
    // সরাসরি ডাটাবেজ রেকর্ড পাঠানো হচ্ছে, যাতে অ্যাপের @SerializedName("verification_level") এর সাথে মিলে যায়
    sendResponse($records);
} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>