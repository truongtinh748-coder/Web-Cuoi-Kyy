<?php
// Bắt buộc phải có dòng này để PHP cập nhật bộ đếm thời gian Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Pong']);
?>