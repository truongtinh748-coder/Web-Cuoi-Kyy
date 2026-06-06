<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['user_logged'] ?? '';
$file = '../data/active_sessions.json';

if ($username !== '' && file_exists($file)) {
    $activeSessions = json_decode(file_get_contents($file), true);
    if (!is_array($activeSessions)) $activeSessions = [];

    if (isset($activeSessions[$username])) {
        unset($activeSessions[$username]);
        file_put_contents($file, json_encode($activeSessions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }
}

http_response_code(204);