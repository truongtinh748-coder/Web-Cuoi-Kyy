<?php
// Tự động kiểm tra và sửa đường dẫn file cấu hình để tránh lỗi Warning bôi bẩn giao diện
if (file_exists('config/connect.php')) {
    include_once 'config/connect.php';
} elseif (file_exists('config/config.php')) {
    include_once 'config/config.php';
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['username'])) { 
    header("Location: login.php"); 
    exit(); 
}

$score = isset($_SESSION['last_score']) ? $_SESSION['last_score'] : '0.0';
$sub_name = isset($_SESSION['last_subject']) ? $_SESSION['last_subject'] : 'Chưa rõ môn';

// =========================================================================
// 🎯 CẬP NHẬT: TỰ ĐỘNG XÓA KHỎI DANH SÁCH ĐANG LÀM BÀI (ACTIVE SESSIONS)
// =========================================================================
$activeSessionsFile = 'data/active_sessions.json';
if (file_exists($activeSessionsFile)) {
    $activeSessions = json_decode(file_get_contents($activeSessionsFile), true);
    if (!is_array($activeSessions)) $activeSessions = [];
    
    // Nếu tài khoản của học sinh này vẫn đang nằm trong danh sách thi, tiến hành xóa bỏ
    if (isset($activeSessions[$_SESSION['username']])) {
        unset($activeSessions[$_SESSION['username']]);
        file_put_contents($activeSessionsFile, json_encode($activeSessions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
// =========================================================================
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết Quả Bài Thi</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            color: #334155; 
            overflow-x: hidden;
        }
        .result-container { 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); 
            text-align: center; 
            max-width: 450px; 
            width: 90%; 
            z-index: 10;
        }
        .brand { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: #64748b; margin-bottom: 10px; }
        .brand span { color: #0284c7; }
        h2 { font-size: 22px; color: #1e293b; font-weight: 800; margin-bottom: 20px; }
        .score-box { width: 120px; height: 120px; background: #f0fdf4; border: 4px solid #4ade80; color: #166534; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 38px; font-weight: 800; margin: 20px auto; box-shadow: 0 4px 10px rgba(74,222,128,0.2); }
        .details { margin: 20px 0; background: #f8fafc; padding: 15px; border-radius: 8px; font-size: 14px; text-align: left; border: 1px solid #e2e8f0; }
        .details p { margin-bottom: 6px; }
        .details p:last-child { margin-bottom: 0; }
        .btn-back { display: block; background: #0284c7; color: white; padding: 12px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px; transition: 0.2s; margin-top: 25px; box-shadow: 0 4px 6px -1px rgba(2,132,199,0.2); }
        .btn-back:hover { background: #0369a1; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="result-container">
        <h2>HOÀN THÀNH KIỂM TRA 🎉</h2>
        <div class="score-box"><?php echo htmlspecialchars($score); ?></div>
        <div class="details">
            <p>Thí sinh làm bài: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            <p>Môn hoàn thành: <strong><?php echo htmlspecialchars($sub_name); ?></strong></p>
            <p>Trạng thái dữ liệu: <span style="color:#166534; font-weight:600;">Đã ghi nhận lịch sử</span></p>
        </div>
        <a href="dashboard.php" class="btn-back">Quay Lại Bảng Điều Khiển</a>
    </div>
</body>
</html>