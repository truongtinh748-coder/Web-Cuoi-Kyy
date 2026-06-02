<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

$exam_code = $_GET['code'] ?? 1;
$json_data = file_get_contents('data/questions.json');
$questions = json_decode($json_data, true);

$current_exam_questions = array_filter($questions, function($q) use ($exam_code) {
    return (int)$q['examCode'] === (int)$exam_code;
});
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Đề #<?= $exam_code ?> | NG-HOI ADMIN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; background: #fdfcff; color: #1e293b; padding: 40px; }
        .container { max-width: 800px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { padding: 10px 20px; background: #ede9fe; color: #5b21b6; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .btn-back:hover { background: #ddd6fe; }
        .question-card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e9d5ff; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .q-text { font-weight: 600; margin-bottom: 15px; color: #312e81; }
        .options { list-style: none; padding: 0; }
        .options li { padding: 8px 0; border-bottom: 1px solid #f8fafc; }
        .badge { background: #5b21b6; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nội dung Đề thi #<?= $exam_code ?></h1>
            <a href="manage_exam.php" class="btn-back">← Quay lại</a>
        </div>

        <?php if (empty($current_exam_questions)): ?>
            <div class="question-card">Không có dữ liệu cho mã đề này.</div>
        <?php else: ?>
            <?php foreach ($current_exam_questions as $q): ?>
                <div class="question-card">
                    <div class="q-text">Câu <?= $q['displayNum'] ?>: <?= $q['question'] ?></div>
                    <ul class="options">
                        <li>A. <?= $q['A'] ?></li>
                        <li>B. <?= $q['B'] ?></li>
                        <li>C. <?= $q['C'] ?></li>
                        <li>D. <?= $q['D'] ?></li>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>