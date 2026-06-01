<?php
require_once '../db.php';

try {
    // Fetch top contributors based on trust_score
    $sql = "SELECT name, trust_score as points, level_name 
            FROM users 
            WHERE status = 'active' 
            ORDER BY trust_score DESC 
            LIMIT 50";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    $data = [];
    foreach ($users as $index => $user) {
        $user['rank'] = $index + 1;
        $data[] = $user;
    }
    
    sendResponse(["status" => "success", "data" => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>