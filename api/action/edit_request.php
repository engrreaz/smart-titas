<?php
// রিকোয়েস্ট ট্র্যাক করার জন্য এই ডিরেক্টরিতেই একটি debug.log ফাইল তৈরি হবে
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log'); 

require_once '../db.php';
require_once '../jwt_helper.php';

error_log("--- New Edit Request Hit ---");

$user = requireAuth();
$userId = $user['user_id'];

// অ্যান্ড্রয়েড থেকে JSON বা POST উভয় ডাটা ধরার ব্যবস্থা
$input = getJsonInput(); // db.php তে ডিফাইন করা আছে
if (empty($input)) {
    $input = $_POST;
}

$type = $input['type'] ?? null;
$itemId = $input['item_id'] ?? null;
$changesJson = $input['changes'] ?? null;
$deviceId = $input['device_id'] ?? null;

// প্রাপ্ত সব ডাটা লগে লিখে রাখা (আপনার api/action/debug.log ফাইলটি চেক করুন)
error_log("Input Data: type=$type, itemId=$itemId, changes=" . (is_array($changesJson) ? json_encode($changesJson) : $changesJson));

error_log(json_decode($changesJson) ? "Valid JSON in changes" : "Invalid JSON in changes");

if (!$type || !$itemId || !$changesJson) {
    error_log("Validation failed: Missing parameters");
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "Missing required parameters"]);
}

try {
    // যদি changes অলরেডি অ্যারে হিসেবে আসে (JSON রিকোয়েস্ট), তবে তাকে স্ট্রিং করা
    if (is_array($changesJson)) {
        $changesJson = json_encode($changesJson);
    }

    // JSON ভ্যালিড কি না চেক করা
    if (!json_decode($changesJson)) {
        error_log("Invalid JSON format in changesJson: " . $changesJson);
        throw new Exception("Invalid changes JSON format");
    }

    $stmt = $conn->prepare("INSERT INTO edit_requests (item_type, item_id, requested_by, changes, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$type, $itemId, $userId, $changesJson]);
    
    // কন্ট্রিবিউশন লগ ইনসার্ট
    $stmtLog = $conn->prepare("INSERT INTO contributions (user_id, item_type, item_id, action_type, status) VALUES (?, ?, ?, 'edit', 'pending')");
    $stmtLog->execute([$userId, $type, $itemId]);

    error_log("Success: Edit request saved for user $userId");
    sendResponse(["status" => "success", "message" => "এডিট রিকোয়েস্ট জমা দেওয়া হয়েছে"]);

} catch (Exception $e) {
    error_log("DB Error in edit_request: " . $e->getMessage());
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>