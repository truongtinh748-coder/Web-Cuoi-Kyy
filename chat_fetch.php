<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

header('Content-Type: application/json; charset=utf-8');

$dataDir = __DIR__ . '/../data';
$file = $dataDir . '/chat_messages.json';
$chatToken = trim($_GET['chat_token'] ?? '');

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

if (!file_exists($file)) {
    file_put_contents($file, json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

$messages = [];
$content = file_get_contents($file);
if ($content !== false && trim($content) !== '') {
    $messages = json_decode($content, true);
    if (!is_array($messages)) {
        $messages = [];
    }
}

$filtered = [];
foreach ($messages as $m) {
    if (!is_array($m)) continue;
    if (!isset($m['created_at'])) continue;
    if ($chatToken !== '' && ($m['chat_token'] ?? '') !== $chatToken) continue;

    $filtered[] = [
        'id' => $m['id'] ?? '',
        'sender' => $m['sender'] ?? '',
        'role' => $m['role'] ?? '',
        'message' => $m['message'] ?? '',
        'time' => $m['time'] ?? '',
        'created_at' => $m['created_at'] ?? '',
        'images' => $m['images'] ?? [],
        'image_url' => $m['image_url'] ?? '',
        'revoked' => $m['revoked'] ?? false,
        'seen' => $m['seen'] ?? false,
        'seen_at' => $m['seen_at'] ?? null,
        'chat_token' => $m['chat_token'] ?? ''
    ];
}

echo json_encode(array_values($filtered), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);