<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function fail($code, $message) {
    http_response_code($code);
    echo json_encode(
        ['status' => 'error', 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    fail(405, 'Method not allowed');
}

$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
$allowedRoles = ['admin', 'teacher', 'student'];

if ($username === '' || !in_array($role, $allowedRoles, true)) {
    fail(403, 'Forbidden');
}

// ✅ PHẦN SỬA 1: KHÔNG LẤY CHAT_TOKEN TỪ GET NỮA
// $chatToken = trim($_GET['chat_token'] ?? '');

// ✅ PHẦN SỬA 2: DÙNG CHUNG 1 TOKEN TOÀN HỆ THỐNG (PHÒNG CHUNG)
$chatToken = 'global_room';

echo json_encode(
    [
        'status' => 'ok',
        'chat_token' => $chatToken
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);