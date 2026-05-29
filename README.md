<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

// Giả lập danh sách 24 mã đề
$exam_codes = [];
for ($i = 1; $i <= 24; $i++) {
    $exam_codes[] = "MA-DE-" . str_pad($i, 3, "0", STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đề | NG-HOI ADMIN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; background: #fdfcff; display: flex; height: 100vh; }
        .sidebar { width: 260px; background: #5b21b6; padding: 25px; color: white; }
        .nav-link { display: block; color: #ddd6fe; padding: 12px 15px; text-decoration: none; border-radius: 8px; margin-bottom: 8px; font-weight: 500; }
        .nav-link.active { background: #7c3aed; color: white; }
        .main { flex: 1; padding: 40px; overflow-y: auto; }
        .card { background: white; padding: 30px; border-radius: 16px; border: 1px solid #e9d5ff; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #f1f5f9; }
        th { color: #6d28d9; }
        .view-btn { background: #ede9fe; color: #5b21b6; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; }
        .view-btn:hover { background: #ddd6fe; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 style="color: #ede9fe; font-size: 1.1rem; margin-bottom: 20px;">NG-HOI ADMIN</h2>
        <a href="teacher.php" class="nav-link">📊 Tổng quan</a>
        <a href="manage_exam.php" class="nav-link active">📂 Quản lý Đề</a>
        <a href="students.php" class="nav-link">👥 Danh sách SV</a>
    </div>

    <div class="main">
        <h1>Quản lý Mã Đề (24)</h1>
        <div class="card">
            <h3>Danh sách mã đề hiện có</h3>
            <table>
                <thead><tr><th>STT</th><th>Mã đề</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($exam_codes as $index => $code): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><strong><?= $code ?></strong></td>
                        <td><button class="view-btn">Xem nội dung</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>