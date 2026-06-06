<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$file = __DIR__ . '/../data/chat_messages.json';
if (!file_exists($file)) exit('no file');

$content = file_get_contents($file);
$messages = json_decode($content, true);
if (!is_array($messages)) $messages = [];

$now = time();
$messages = array_values(array_filter($messages, function($m) use ($now) {
    return isset($m['created_at']) && ($now - (int)$m['created_at']) <= 86400;
}));

file_put_contents($file, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "cleanup done";