<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
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
$questionDetails = [];
$answeredQuestions = 0;

if (isset($_SESSION['current_exam_questions']) && is_array($_SESSION['current_exam_questions'])) {
    $exam_questions = $_SESSION['current_exam_questions'];
    $totalQuestions = count($exam_questions);
    
    foreach ($exam_questions as $index => $q) {
        $studentAns = isset($_POST["ans_$index"]) ? trim($_POST["ans_$index"]) : '';
        $correctAns = isset($q['correct']) ? strtoupper(trim($q['correct'])) : '';
        $isCorrect = (!empty($studentAns) && strtoupper($studentAns) === $correctAns);

        if (!empty($studentAns)) {
            $answeredQuestions++;
        }

        if ($isCorrect) {
            $correctAnswers++;
        }

        $questionDetails[] = [
            "question" => $q['question'] ?? '',
            "student_answer" => strtoupper($studentAns),
            "correct_answer" => $correctAns,
            "is_correct" => $isCorrect,
            "A" => $q['A'] ?? '',
            "B" => $q['B'] ?? '',
            "C" => $q['C'] ?? '',
            "D" => $q['D'] ?? ''
        ];
    }
}

$score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 10, 2) : 0;

$resultsFile = '../data/results.json';
$newResult = [
    "username" => $username,
    "subject" => strtoupper($subject),
    "correct" => "$correctAnswers/$totalQuestions",
    "score" => $score,
    "time" => date("H:i:s d/m/Y"),
    "details" => $questionDetails
];

$currentResults = [];
if (file_exists($resultsFile)) {
    $fileContent = file_get_contents($resultsFile);
    if (!empty($fileContent)) {
        $currentResults = json_decode($fileContent, true);
        if (!is_array($currentResults)) $currentResults = [];
    }
}

array_unshift($currentResults, $newResult);

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
        :root{
            --bg:#f1f5f9;
            --card:#ffffff;
            --text:#0f172a;
            --muted:#64748b;
            --border:#e2e8f0;
            --primary:#2563eb;
            --success:#10b981;
            --danger:#ef4444;
            --soft-success:#ecfdf5;
            --soft-danger:#fef2f2;
            --shadow:0 12px 30px rgba(15,23,42,.08);
        }
        *{box-sizing:border-box}
        body{
            font-family:'Segoe UI',sans-serif;
            background:linear-gradient(135deg,#e0f2fe 0%,#eef2ff 45%,#f8fafc 100%);
            margin:0;
            min-height:100vh;
            padding:28px 16px;
            color:var(--text);
        }
        .result-wrap{
            max-width:1000px;
            margin:0 auto;
        }
        .result-card{
            background:rgba(255,255,255,.92);
            backdrop-filter:blur(10px);
            border:1px solid rgba(226,232,240,.9);
            border-radius:24px;
            box-shadow:var(--shadow);
            padding:28px;
        }
        .top-box{
            display:grid;
            grid-template-columns:1.1fr .9fr;
            gap:18px;
            align-items:stretch;
            margin-bottom:24px;
        }
        .hero{
            background:linear-gradient(135deg,#1d4ed8 0%,#0ea5e9 100%);
            color:#fff;
            border-radius:20px;
            padding:26px;
            box-shadow:0 12px 26px rgba(37,99,235,.18);
        }
        .hero h1{
            margin:0 0 10px;
            font-size:26px;
            line-height:1.25;
        }
        .hero p{
            margin:0;
            opacity:.92;
            font-size:14px;
            line-height:1.6;
        }
        .score-box{
            background:#fff;
            border:1px solid var(--border);
            border-radius:20px;
            padding:22px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            text-align:center;
        }
        .score{
            font-size:64px;
            font-weight:800;
            line-height:1;
            color:var(--success);
            margin:6px 0 8px;
        }
        .score small{
            font-size:18px;
            color:var(--muted);
            font-weight:600;
        }
        .summary{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:12px;
            margin:22px 0 26px;
        }
        .stat{
            background:#fff;
            border:1px solid var(--border);
            border-radius:16px;
            padding:14px 16px;
        }
        .stat .label{
            font-size:12px;
            color:var(--muted);
            text-transform:uppercase;
            letter-spacing:.6px;
            font-weight:700;
            margin-bottom:8px;
        }
        .stat .value{
            font-size:18px;
            font-weight:800;
            color:var(--text);
        }
        .details{
            background:#f8fafc;
            border:1px solid var(--border);
            border-radius:18px;
            padding:16px 18px;
            line-height:1.9;
            color:#334155;
            margin-bottom:24px;
        }
        .section-title{
            font-size:18px;
            font-weight:800;
            margin:0 0 14px;
            color:var(--text);
        }
        .review-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px;
        }
        .review-item{
            background:#fff;
            border:1px solid var(--border);
            border-radius:18px;
            padding:16px;
            box-shadow:0 4px 14px rgba(15,23,42,.04);
        }
        .review-item.correct{
            border-left:6px solid var(--success);
            background:linear-gradient(180deg,#ffffff 0%,var(--soft-success) 100%);
        }
        .review-item.wrong{
            border-left:6px solid var(--danger);
            background:linear-gradient(180deg,#ffffff 0%,var(--soft-danger) 100%);
        }
        .q-head{
            display:flex;
            justify-content:space-between;
            gap:10px;
            align-items:flex-start;
            margin-bottom:10px;
        }
        .q-title{
            font-weight:800;
            color:var(--text);
            line-height:1.45;
            font-size:15px;
            margin:0;
            padding-right:8px;
            flex:1;
        }
        .badge{
            flex:0 0 auto;
            font-size:11px;
            font-weight:800;
            padding:6px 10px;
            border-radius:999px;
            text-transform:uppercase;
            letter-spacing:.5px;
            white-space:nowrap;
        }
        .badge.correct{
            background:#dcfce7;
            color:#166534;
        }
        .badge.wrong{
            background:#fee2e2;
            color:#991b1b;
        }
        .ans-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:8px;
            margin-top:10px;
        }
        .ans-chip{
            border:1px solid var(--border);
            border-radius:12px;
            padding:10px 12px;
            font-size:13px;
            color:#334155;
            background:#fff;
        }
        .ans-chip strong{
            color:var(--text);
        }
        .ans-chip.pick{
            border-color:#93c5fd;
            background:#eff6ff;
        }
        .ans-chip.right{
            border-color:#86efac;
            background:#f0fdf4;
        }
        .btn-back{
            display:block;
            background:linear-gradient(135deg,var(--primary) 0%,#1d4ed8 100%);
            color:white;
            text-decoration:none;
            padding:14px 20px;
            border-radius:14px;
            font-weight:800;
            text-align:center;
            width:100%;
            box-sizing:border-box;
            transition:transform .2s, box-shadow .2s;
            box-shadow:0 10px 20px rgba(37,99,235,.18);
            margin-top:24px;
        }
        .btn-back:hover{
            transform:translateY(-2px);
            box-shadow:0 14px 28px rgba(37,99,235,.24);
        }
        @media (max-width: 900px){
            .top-box,.summary,.review-grid{grid-template-columns:1fr}
            .result-card{padding:20px}
            .hero h1{font-size:22px}
            .score{font-size:54px}
        }
    </style>
</head>
<body>
    <div class="result-wrap">
        <div class="result-card">
            <div class="top-box">
                <div class="hero">
                    <h1>🎉 Kết Quả Bài Thi</h1>
                    <p>Kết quả tổng quan và phần xem lại từng câu trả lời đã được sắp xếp gọn để dễ theo dõi.</p>
                </div>
                <div class="score-box">
                    <div class="score"><?php echo $score; ?> <small>/ 10đ</small></div>
                    <div style="color:var(--muted);font-size:14px;font-weight:600;">Tổng số câu đúng: <?php echo $correctAnswers; ?> / <?php echo $totalQuestions; ?></div>
                </div>
            </div>

            <div class="summary">
                <div class="stat">
                    <div class="label">Thí sinh</div>
                    <div class="value"><?php echo htmlspecialchars($username); ?></div>
                </div>
                <div class="stat">
                    <div class="label">Môn thi</div>
                    <div class="value" style="text-transform:uppercase;"><?php echo htmlspecialchars($subject); ?></div>
                </div>
                <div class="stat">
                    <div class="label">Đã làm</div>
                    <div class="value"><?php echo $answeredQuestions; ?> / <?php echo $totalQuestions; ?></div>
                </div>
                <div class="stat">
                    <div class="label">Tỷ lệ đúng</div>
                    <div class="value"><?php echo $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0; ?>%</div>
                </div>
            </div>

            <div class="details">
                • Thí sinh: <strong><?php echo htmlspecialchars($username); ?></strong><br>
                • Môn thi: <strong style="text-transform: uppercase; color: #2563eb;"><?php echo htmlspecialchars($subject); ?></strong><br>
                • Số câu đã làm: <strong><?php echo $answeredQuestions; ?> / <?php echo $totalQuestions; ?></strong> câu.<br>
                • Số câu đúng: <strong><?php echo $correctAnswers; ?> / <?php echo $totalQuestions; ?></strong> câu.<br>
                • Tỷ lệ chính xác: <strong><?php echo $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0; ?>%</strong>
            </div>

            <div class="section-title">Xem lại đáp án đã chọn</div>

            <?php if (!empty($questionDetails)): ?>
                <div class="review-grid">
                    <?php foreach ($questionDetails as $i => $item): ?>
                        <div class="review-item <?php echo $item['is_correct'] ? 'correct' : 'wrong'; ?>">
                            <div class="q-head">
                                <p class="q-title">Câu <?php echo $i + 1; ?>: <?php echo htmlspecialchars($item['question']); ?></p>
                                <span class="badge <?php echo $item['is_correct'] ? 'correct' : 'wrong'; ?>">
                                    <?php echo $item['is_correct'] ? 'Đúng' : 'Sai'; ?>
                                </span>
                            </div>

                            <div class="ans-grid">
                                <div class="ans-chip pick">
                                    Đã chọn: <strong><?php echo $item['student_answer'] !== '' ? htmlspecialchars($item['student_answer']) : 'Chưa chọn'; ?></strong>
                                </div>
                                <div class="ans-chip right">
                                    Đúng: <strong><?php echo htmlspecialchars($item['correct_answer']); ?></strong>
                                </div>
                                <div class="ans-chip">
                                    A. <?php echo htmlspecialchars($item['A']); ?>
                                </div>
                                <div class="ans-chip">
                                    B. <?php echo htmlspecialchars($item['B']); ?>
                                </div>
                                <div class="ans-chip">
                                    C. <?php echo htmlspecialchars($item['C']); ?>
                                </div>
                                <div class="ans-chip">
                                    D. <?php echo htmlspecialchars($item['D']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="../student_dashboard.php" class="btn-back">Quay Lại Trang Chủ</a>
        </div>
    </div>
</body>
</html>