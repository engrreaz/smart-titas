<?php
require_once '../db.php';
require_once '../jwt_helper.php';

// রেসপন্স সবসময় JSON ফরম্যাটে হওয়া নিশ্চিত করা
header('Content-Type: application/json; charset=UTF-8');

 echo json_encode([
        "status" => "success", 
        "message" => "Internal Server Error", 
        "debug_info" => $e->getMessage()
    ]);
exit;

try {
    error_log("Accessing reports.php");
    
    // ১. অথেন্টিকেশন চেক
    $user = requireAuth();
    $auth_role = $user['role'] ?? '';

    // ২. পারমিশন চেক (অ্যাডমিন, সুপার অ্যাডমিন বা মডারেটর)
    $allowed_roles = ['admin', 'super_admin', 'moderator'];
    if (!in_array($auth_role, $allowed_roles)) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized access. Role: $auth_role"]);
        exit;
    }

    // ৩. মূল রিপোর্ট কুয়েরি
    $sql = "SELECT r.id, r.item_type as type, r.item_id, r.reason, r.timestamp, u.name as reported_by
            FROM reports r
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.timestamp DESC";
            
    $stmt = $conn->query($sql);
    
    // কুয়েরি ব্যর্থ হলে হ্যান্ডেল করা (৫00 এরর রোধ করতে)
    if (!$stmt) {
        $error = $conn->errorInfo();
        throw new Exception("Database query failed: " . ($error[2] ?? 'Unknown Error'));
    }

    $reports = [];
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

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = $row['type'];
        $iid = $row['item_id'];
        $row['item_name'] = "Unknown Item"; // ডিফল্ট

        if (isset($table_mapping[$type])) {
            $table = $table_mapping[$type];
            $col = ($type === "notice") ? "title" : "name";
            
            try {
                $item_sql = "SELECT $col as name FROM $table WHERE id = ?";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->execute([$iid]);
                
                $item_row = $item_stmt->fetch(PDO::FETCH_ASSOC);
                if ($item_row) {
                    $row['item_name'] = $item_row['name'];
                }
            } catch (PDOException $e) {
                // কোনো নির্দিষ্ট টেবিল না থাকলে এরর ইগনোর করে পরবর্তী আইটেমে যাবে
                $row['item_name'] = "Error loading name";
            }
        }
        
        $reports[] = $row;
    }

    // ৪. সফল রেসপন্স
    echo json_encode($reports);

} catch (Exception $e) {
    // এরর লগ করা এবং ৫00 স্ট্যাটাস কোড পাঠানো
    error_log("Error in reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Internal Server Error", 
        "debug_info" => $e->getMessage()
    ]);
}
?>
