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
$allowedRoles = ['admin', 'teacher', 'student'];

if ($username === '' || !in_array($role, $allowedRoles, true)) {
    fail(403, 'Forbidden');
}

$dataDir = __DIR__ . '/../data';
$file = $dataDir . '/chat_messages.json';

if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0777, true)) {
        fail(500, 'Cannot create data directory');
    }
}

if (!file_exists($file)) {
    if (file_put_contents($file, json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        fail(500, 'Cannot create message file');
    }
}

$messages = json_decode(file_get_contents($file), true);
if (!is_array($messages)) {
    fail(500, 'Invalid message data');
}

$changed = false;
$now = time();

foreach ($messages as &$msg) {
    if (!is_array($msg)) continue;
    if (($msg['sender'] ?? '') === $username) continue;
    if ($chatToken !== '' && ($msg['chat_token'] ?? '') !== $chatToken) continue;
    if (!isset($msg['created_at'])) continue;
    if (!empty($msg['revoked'])) continue;
    if (($msg['seen'] ?? false) === true) continue;

    $msg['seen'] = true;
    $msg['seen_at'] = $now;
    $changed = true;
}
unset($msg);

if ($changed) {
    if (file_put_contents(
        $file,
        json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        LOCK_EX
    ) === false) {
        fail(500, 'Cannot write message file');
    }
}

echo json_encode(['status' => 'ok', 'changed' => $changed], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);