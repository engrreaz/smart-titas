<?php
// require_once '../db.php';
// require_once '../jwt_helper.php';

error_log("Accessing reports.php");
// $user = requireAuth();
// $auth_role = $user['role'] ?? '';



// // শুধুমাত্র অ্যাডমিন বা সুপার অ্যাডমিন চেক
// if ($auth_role !== 'admin' && $auth_role !== 'super_admin' && $auth_role !== 'moderator') {
//     die(json_encode(["status" => "error", "message" => "Unauthorized"]));
// }

// $sql = "SELECT r.id, r.item_type as type, r.item_id, r.reason, r.timestamp, u.name as reported_by 
//         FROM reports r 
//         LEFT JOIN users u ON r.user_id = u.id 
//         ORDER BY r.timestamp DESC";
// $result = $conn->query($sql);

// $reports = [];
// while($row = $result->fetch(PDO::FETCH_ASSOC)) {
//     $type = $row['type'];
//     $iid = $row['item_id'];
    
//     // আইটেমের নাম খুঁজে বের করার ম্যাপিং
//     $table_mapping = [
//         "official" => "officials",
//         "institution" => "institutions",
//         "donor" => "blood_donors",
//         "professional" => "professionals",
//         "business" => "businesses",
//         "emergency" => "emergency_contacts",
//         "tourism" => "tourism_places",
//         "notice" => "notices"
//     ];

//     if (isset($table_mapping[$type])) {
//         $table = $table_mapping[$type];
        
//         $item_sql = "SELECT name FROM $table WHERE id = ?";
//         if ($type === "notice") {
//              $item_sql = "SELECT title as name FROM notices WHERE id = ?";
//         }
        
//         $stmt = $conn->prepare($item_sql);
//         $stmt->execute([$iid]);
        
//         if ($stmt->rowCount() > 0) {
//             $item_row = $stmt->fetch(PDO::FETCH_ASSOC);
//             $row['item_name'] = $item_row['name'];
//         } else {
//             $row['item_name'] = "Unknown Item";
//         }
//     } else {
//         $row['item_name'] = "Unknown Type";
//     }
    
//     $reports[] = $row;
// }

// echo json_encode($reports);
// ?>
