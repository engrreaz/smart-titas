<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
$auth_role = $user['role'] ?? '';

// শুধুমাত্র অ্যাডমিন বা সুপার অ্যাডমিন চেক
if ($auth_role !== 'admin' && $auth_role !== 'super_admin') {
    die(json_encode(["status" => "error", "message" => "Unauthorized access"]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $item_id = $_POST['item_id'];
    $device_id = $_POST['device_id'] ?? '';

    // টেবিল নাম নির্ধারণ (ম্যাপিং)
    $table_mapping = [
        "official" => "officials",
        "institution" => "institutions",
        "donor" => "blood_donors",
        "professional" => "professionals",
        "business" => "businesses",
        "emergency" => "emergency_contacts",
        "tourism" => "tourism_places",
        "notice" => "notices"
    ];

    if (isset($table_mapping[$type])) {
        $table = $table_mapping[$type];
        
        // ট্রানজ্যাকশন শুরু (নিরাপত্তার জন্য)
        $conn->beginTransaction();
        try {
            // ১. আইটেমটি ডিলিট করা
            $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$item_id]);

            // ২. সংশ্লিষ্ট রিপোর্টগুলো ডিলিট করা
            $stmt_rep = $conn->prepare("DELETE FROM reports WHERE item_type = ? AND item_id = ?");
            $stmt_rep->execute([$type, $item_id]);

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "সফলভাবে মুছে ফেলা হয়েছে।"]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(["status" => "error", "message" => "মুছে ফেলা সম্ভব হয়নি। " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid type"]);
    }
}
?>
