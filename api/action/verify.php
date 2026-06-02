<?php
require_once '../db.php';
require_once '../jwt_helper.php';

error_log("verify.php accessed");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(["status" => "error", "message" => "Method Not Allowed"]);
}

$user = requireAuth();

// Simple role check, adjust as per your actual role logic
if ($user['role'] !== 'moderator' && $user['role'] !== 'admin') {
    http_response_code(403);
    sendResponse(["status" => "error", "message" => "Forbidden: Moderators only"]);
}

error_log("User authenticated: ID: " . $user['user_id'] . ", Role: " . $user['role']);
