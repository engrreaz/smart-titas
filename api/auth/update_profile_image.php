<?php
require_once '../db.php';
require_once '../jwt_helper.php';

$user = requireAuth();
$userId = $user['user_id'];

$input = getJsonInput();
$base64Image = $_POST['image'] ?? ($input['image'] ?? null);

if (!$base64Image) {
    http_response_code(400);
    sendResponse(["status" => "error", "message" => "No image data provided"]);
}

try {
    // Decode base64 image
    $imageData = base64_decode($base64Image);
    if (!$imageData) {
        throw new Exception("Invalid image data");
    }

    $fileName = 'profile_' . $userId . '_' . time() . '.jpg';
    $uploadDir = '../uploads/profiles/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filePath = $uploadDir . $fileName;
    file_put_contents($filePath, $imageData);

    // Update database with relative path or full URL
    $imageUrl = "https://smarttitas.eimbox.com/api/uploads/profiles/" . $fileName;
    
    $stmt = $conn->prepare("UPDATE users SET image = ? WHERE id = ?");
    $stmt->execute([$imageUrl, $userId]);

    sendResponse(["status" => "success", "message" => "Profile image updated successfully", "image_url" => $imageUrl]);

} catch (Exception $e) {
    http_response_code(500);
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}
?>