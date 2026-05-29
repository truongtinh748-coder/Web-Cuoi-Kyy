<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php");
    exit();
}

$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$username = isset($_SESSION['user_logged']) ? $_SESSION['user_logged'] : 'Ẩn danh';
$correctAnswers = 0;
$totalQuestions = 0;

if (isset($_SESSION['current_exam_questions']) && is_array($_SESSION['current_exam_questions'])) {
    $exam_questions = $_SESSION['current_exam_questions'];
    $totalQuestions = count($exam_questions);
    
    foreach ($exam_questions as $index => $q) {
        $studentAns = isset($_POST["ans_$index"]) ? trim($_POST["ans_$index"]) : '';
        if (!empty($studentAns) && strtoupper($studentAns) === strtoupper($q['correct'])) {
            $correctAnswers++;
        }
    }
}

$score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 10, 2) : 0;

$resultsFile = '../data/results.json';
$newResult = [
    "username" => $username,
    "subject" => strtoupper($subject),
    "correct" => "$correctAnswers/$totalQuestions",
    "score" => $score,
    "time" => date("H:i:s d/m/Y")
];

$currentResults = [];
if (file_exists($resultsFile)) {
    $fileContent = file_get_contents($resultsFile);
    if (!empty($fileContent)) {
        $currentResults = json_decode($fileContent, true);
        if (!is_array($currentResults)) $currentResults = [];
    }
}

array_unshift($currentResults, $newResult); // Đẩy kết quả mới nhất lên đầu danh sách

if (!is_dir('../data')) {
    mkdir('../data', 0777, true);
}
file_put_contents($resultsFile, json_encode($currentResults, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết Quả Bài Thi</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .result-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; max-width: 450px; width: 90%; }
        h1 { color: #0f172a; margin-bottom: 20px; font-size: 24px; }
        .score { font-size: 64px; font-weight: 800; color: #10b981; margin: 20px 0; }
        .details { color: #475569; font-size: 16px; margin-bottom: 30px; line-height: 1.8; text-align: left; background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .btn-back { display: inline-block; background: #2563eb; color: white; text-decoration: none; padding: 12px 30px; border-radius: 10px; font-weight: bold; width: 100%; box-sizing: border-box; transition: 0.2s; }
        .btn-back:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="result-card">
        <h1>🎉 Kết Quả Bài Thi</h1>
        <div class="score"><?php echo $score; ?> <span style="font-size: 20px; color:#64748b;">/ 10đ</span></div>
        <div class="details">
            • Thí sinh: <strong><?php echo htmlspecialchars($username); ?></strong><br>
            • Môn thi: <strong style="text-transform: uppercase; color: #2563eb;"><?php echo htmlspecialchars($subject); ?></strong><br>
            • Số câu đúng: <strong><?php echo $correctAnswers; ?> / <?php echo $totalQuestions; ?></strong> câu.<br>
            • Tỷ lệ chính xác: <strong><?php echo $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0; ?>%</strong>
        </div>
        <a href="../home.php" class="btn-back">Quay Lại Trang Chủ</a>
    </div>
</body>
</html>
