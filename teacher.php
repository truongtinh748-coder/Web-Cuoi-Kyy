<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }
$total = 21;
$submitted = 13;
$progress = ($submitted / $total) * 100;
$cheaters = ["Chăm Sóc Kh - Bighome" => "Tab Switch", "Đăng Khoa" => "Tab Switch", "Hồng Trần" => "Tab Switch"];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | NG-HOI</title>
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; background: #fdfcff; display: flex; height: 100vh; color: #1e293b; }
        .sidebar { width: 260px; background: #5b21b6; padding: 25px; color: white; }
        .nav-link { display: block; color: #ddd6fe; padding: 12px 15px; text-decoration: none; border-radius: 8px; margin-bottom: 8px; }
        .nav-link.active { background: #7c3aed; color: white; }
        .main { flex: 1; padding: 40px; overflow-y: auto; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e9d5ff; box-shadow: 0 4px 6px rgba(91, 33, 182, 0.05); }
        .card-label { font-size: 0.8rem; font-weight: 700; color: #6d28d9; text-transform: uppercase; margin-bottom: 15px; }
        .card-value { font-size: 2.2rem; font-weight: 800; color: #4c1d95; }
        .progress-container { background: #f5f3ff; height: 10px; border-radius: 10px; margin-top: 20px; }
        .progress-bar { background: #7c3aed; height: 100%; border-radius: 10px; width: <?= $progress ?>%; }
        .cheat-item { font-size: 0.85rem; padding: 8px 0; border-bottom: 1px solid #f5f3ff; display: flex; justify-content: space-between; }
        .cheat-reason { color: #be123c; background: #fef2f2; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 style="font-size: 1.1rem; margin-bottom: 20px;">NG-HOI ADMIN</h2>
        <a href="teacher.php" class="nav-link active">📊 Tổng quan</a>
        <a href="manage_exam.php" class="nav-link">📂 Quản lý Đề</a>
        <a href="students.php" class="nav-link">👥 Danh sách SV</a>
    </div>
    <div class="main">
        <h1>Dashboard Tổng quan</h1>
        <div class="grid">
            <div class="card">
                <div class="card-label">Tiến độ bài làm</div>
                <div class="card-value"><?= $submitted ?>/<?= $total ?> SV</div>
                <div class="progress-container"><div class="progress-bar"></div></div>
            </div>
            <div class="card">
                <div class="card-label" style="color: #be123c;">⚠️ Cảnh báo gian lận</div>
                <?php foreach($cheaters as $name => $reason): ?>
                <div class="cheat-item"><span><?= $name ?></span><span class="cheat-reason"><?= $reason ?></span></div>
                <?php endforeach; ?>
            </div>
            <div class="card">
                <div class="card-label">Tổng sinh viên</div>
                <div class="card-value"><?= $total ?></div>
                <div style="margin-top:10px; color:#6d28d9;">Đang trong kỳ thi</div>
            </div>
        </div>
    </div>
</body>
</html>