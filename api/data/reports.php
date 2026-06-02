<?php
require_once '../db.php';
require_once '../jwt_helper.php';

include_once '../auth/check_token.php';

// শুধুমাত্র অ্যাডমিন বা সুপার অ্যাডমিন চেক
if ($auth_role !== 'admin' && $auth_role !== 'super_admin' && $auth_role !== 'moderator') {
    die(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

$sql = "SELECT r.id, r.item_type as type, r.item_id, r.reason, r.timestamp, u.name as reported_by 
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id 
        ORDER BY r.timestamp DESC";
$result = $conn->query($sql);

$reports = [];
while($row = $result->fetch_assoc()) {
    $type = $row['type'];
    $iid = $row['item_id'];
    
    // আইটেমের নাম খুঁজে বের করার ম্যাপিং
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
        $name_res = $conn->query("SELECT name FROM $table WHERE id = $iid");
        // Notices don't have 'name' column, they have 'title'
        if ($type === "notice") {
             $name_res = $conn->query("SELECT title as name FROM notices WHERE id = $iid");
        }
        $row['item_name'] = ($name_res && $name_res->num_rows > 0) ? $name_res->fetch_assoc()['name'] : "Unknown Item";
    } else {
        $row['item_name'] = "Unknown Type";
    }
    
    $reports[] = $row;
}

echo json_encode($reports);
?>
