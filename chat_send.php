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
$sessionRole = $_SESSION['role'] ?? '';
$chatToken = trim($_POST['chat_token'] ?? '');
if ($chatToken === '') {
    $chatToken = 'global_room';
}

$sender = $username;
$role = $sessionRole;
$message = trim($_POST['message'] ?? '');

$allowedRoles = ['admin', 'teacher', 'student'];

if ($username === '' || $sessionRole === '' || $sender === '' || !in_array($role, $allowedRoles, true)) {
    fail(400, 'Invalid data');
}

$hasText = $message !== '';
$hasFiles = isset($_FILES['images']) && is_array($_FILES['images']['name'] ?? null) && count(array_filter($_FILES['images']['name'])) > 0;

if (!$hasText && !$hasFiles) {
    fail(400, 'Message or image required');
}

$dataDir = __DIR__ . '/../data';
$file = $dataDir . '/chat_messages.json';

if (!is_dir($dataDir) && !mkdir($dataDir, 0777, true)) {
    fail(500, 'Cannot create data directory');
}

if (!file_exists($file)) {
    if (file_put_contents($file, json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        fail(500, 'Cannot create message file');
    }
}

$messages = json_decode(file_get_contents($file), true);
if (!is_array($messages)) {
    $messages = [];
}

$images = [];

if ($hasFiles) {
    $uploadDir = $dataDir . '/chat_images';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        fail(500, 'Cannot create upload directory');
    }

    $maxSize = 2 * 1024 * 1024;
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    $count = min(5, count($_FILES['images']['name']));

    for ($i = 0; $i < $count; $i++) {
        if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpName = $_FILES['images']['tmp_name'][$i] ?? '';
        $fileSize = (int)($_FILES['images']['size'][$i] ?? 0);

        if ($tmpName === '' || $fileSize <= 0 || $fileSize > $maxSize) {
            continue;
        }

        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
            continue;
        }

        $mime = $imageInfo['mime'] ?? '';
        if (!isset($allowedMimes[$mime])) {
            continue;
        }

        $ext = $allowedMimes[$mime];
        $newName = 'chat_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
        $targetPath = $uploadDir . '/' . $newName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            continue;
        }

        $images[] = '/data/chat_images/' . $newName;
    }
}

if (!$hasText && count($images) === 0) {
    fail(400, 'Message or image required');
}

$messages[] = [
    'id' => bin2hex(random_bytes(8)),
    'sender' => $sender,
    'role' => $role,
    'chat_token' => $chatToken,
    'message' => $message,
    'images' => $images,
    'created_at' => time(),
    'time' => date('H:i d/m/Y'),
    'revoked' => false,
    'seen' => false,
    'seen_at' => null
];

if (file_put_contents(
    $file,
    json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
    LOCK_EX
) === false) {
    fail(500, 'Cannot write message file');
}

echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);