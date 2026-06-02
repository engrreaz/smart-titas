<?php
require_once '../db.php';
require_once '../jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $item_id = $_POST['item_id'];
    $status = $_POST['status']; // 'correct' or 'incorrect'
    $device_id = $_POST['device_id'];
    $user_id = $auth_user_id; 

    $sql = "INSERT INTO verification_logs (user_id, item_type, item_id, status, device_id) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiss", $user_id, $type, $item_id, $status, $device_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "ধন্যবাদ! আপনার মতামত গ্রহণ করা হয়েছে।"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ত্রুটি হয়েছে।"]);
    }
}
?>
