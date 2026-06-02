<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

$json_data = file_get_contents('data/questions.json');
$questions = json_decode($json_data, true);
$exam_stats = [];

if ($questions) {
    foreach ($questions as $q) {
        $code = $q['examCode'];
        if (!isset($exam_stats[$code])) $exam_stats[$code] = 0;
        $exam_stats[$code]++;
    }
}
ksort($exam_stats);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đề Thi | NG-HOI SYSTEM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; background: #fdfcff; display: flex; height: 100vh; }
        .sidebar { width: 260px; background: #5b21b6; padding: 25px; color: white; }
        .nav-link { display: block; color: #ddd6fe; padding: 12px 15px; text-decoration: none; border-radius: 8px; margin-bottom: 8px; font-weight: 500; }
        .nav-link.active { background: #7c3aed; color: white; }
        .main { flex: 1; padding: 40px; overflow-y: auto; }
        .table-container { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e9d5ff; box-shadow: 0 4px 6px rgba(91, 33, 182, 0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #6d28d9; padding: 15px; border-bottom: 2px solid #f5f3ff; }
        td { padding: 15px; border-bottom: 1px solid #f5f3ff; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-open { background: #dcfce7; color: #166534; }
        .action-btn { padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd6fe; background: white; cursor: pointer; color: #5b21b6; font-size: 0.8rem; }
        .action-btn:hover { background: #5b21b6; color: white; }
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
        <h1 style="color: #2e1065;">Quản lý Ngân hàng đề thi</h1>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>Mã đề</th><th>Số câu hỏi</th><th>Trạng thái</th><th>Thao tác</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($exam_stats as $code => $count): ?>
                    <tr>
                        <td><strong>Đề thi #<?= $code ?></strong></td>
                        <td><?= $count ?> câu hỏi</td>
                        <td><span class="status-badge badge-open">Đang khả dụng</span></td>
                        <td>
                            <button class="action-btn">Xem chi tiết</button>
                            <button class="action-btn" style="color: #be123c;">Khóa</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>