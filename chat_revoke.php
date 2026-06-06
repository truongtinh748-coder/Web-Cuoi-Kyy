<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function fail($code, $message) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Method not allowed');
}

$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
$chatToken = trim($_POST['chat_token'] ?? '');
if ($chatToken === '') {
    $chatToken = 'global_room';
}

$allowedRoles = ['admin', 'teacher', 'student'];

if ($username === '' || !in_array($role, $allowedRoles, true)) {
    fail(403, 'Forbidden');
}

$id = trim($_POST['id'] ?? '');
if ($id === '') {
    fail(400, 'Missing id');
}

$file = __DIR__ . '/../data/chat_messages.json';
if (!file_exists($file)) {
    fail(404, 'Message file not found');
}

$messages = json_decode(file_get_contents($file), true);
if (!is_array($messages)) {
    fail(500, 'Invalid message data');
}

$now = time();
$found = false;

foreach ($messages as &$msg) {
    if (($msg['id'] ?? '') !== $id) continue;
    if (($msg['sender'] ?? '') !== $username) {
        fail(403, 'You can only revoke your own message');
    }
    if (($msg['chat_token'] ?? 'global_room') !== $chatToken) {
        fail(403, 'Forbidden');
    }
    if (($now - (int)($msg['created_at'] ?? 0)) > 60) {
        fail(403, 'Message is too old to revoke');
    }

    $msg['revoked'] = true;
    $msg['revoked_at'] = $now;
    $found = true;
    break;
}
unset($msg);

if (!$found) {
    fail(404, 'Message not found');
}

if (file_put_contents(
    $file,
    json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
    LOCK_EX
) === false) {
    fail(500, 'Cannot write message file');
}

echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);