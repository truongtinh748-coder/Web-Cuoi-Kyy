<?php
session_start();

// Bảo mật: chỉ giáo viên và admin mới được truy cập
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'] ?? '', ['teacher', 'admin'])) {
    header("Location: login.php");
    exit();
}

$questionsFile = 'data/questions.json';
$subjectKey = isset($_GET['subject']) ? strtolower(trim($_GET['subject'])) : '';

if ($subjectKey === '') {
    header("Location: teacher_dashboard.php");
    exit();
}

$msg_success = '';
$msg_error = '';

function loadQuestions($questionsFile) {
    $questionsList = [];
    if (file_exists($questionsFile)) {
        $questionsList = json_decode(file_get_contents($questionsFile), true);
    }
    return is_array($questionsList) ? $questionsList : [];
}

function saveQuestions($questionsFile, $questionsList) {
    file_put_contents($questionsFile, json_encode($questionsList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function filterSubjectQuestions($questionsList, $subjectKey) {
    $questions = [];
    foreach ($questionsList as $item) {
        if (is_array($item) && strtolower(trim($item['subjectId'] ?? '')) === $subjectKey) {
            $questions[] = $item;
        }
    }
    return $questions;
}

function findGlobalIndexBySubjectIndex($questionsList, $subjectKey, $subjectIndex) {
    $matchCount = -1;
    foreach ($questionsList as $i => $item) {
        if (is_array($item) && strtolower(trim($item['subjectId'] ?? '')) === $subjectKey) {
            $matchCount++;
            if ($matchCount === $subjectIndex) {
                return $i;
            }
        }
    }
    return null;
}

// Đọc dữ liệu
$questionsList = loadQuestions($questionsFile);
$questions = filterSubjectQuestions($questionsList, $subjectKey);

// === CẬP NHẬT TOÀN BỘ DANH SÁCH CÂU HỎI (INLINE EDIT) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all_questions'])) {
    $postedQuestions = $_POST['questions'] ?? [];
    if (!is_array($postedQuestions)) {
        $postedQuestions = [];
    }

    $newSubjectQuestions = [];
    foreach ($postedQuestions as $q) {
        $questionText = trim($q['question'] ?? '');
        $ansA = trim($q['A'] ?? '');
        $ansB = trim($q['B'] ?? '');
        $ansC = trim($q['C'] ?? '');
        $ansD = trim($q['D'] ?? '');
        $correct = strtoupper(trim($q['correct'] ?? ''));

        if ($questionText === '' && $ansA === '' && $ansB === '' && $ansC === '' && $ansD === '') {
            continue;
        }

        if (!$questionText || !$ansA || !$ansB || !$ansC || !$ansD || !$correct) {
            $msg_error = 'Có câu hỏi chưa đầy đủ dữ liệu, vui lòng kiểm tra lại!';
            break;
        }

        $newSubjectQuestions[] = [
            'subjectId' => $subjectKey,
            'examCode' => 1,
            'displayNum' => count($newSubjectQuestions) + 1,
            'questionId' => $q['questionId'] ?? time(),
            'question' => $questionText,
            'A' => $ansA,
            'B' => $ansB,
            'C' => $ansC,
            'D' => $ansD,
            'correct' => $correct,
            'difficulty' => $q['difficulty'] ?? 'easy',
            'status' => 'approved'
        ];
    }

    if ($msg_error === '') {
        $otherQuestions = [];
        foreach ($questionsList as $item) {
            if (is_array($item) && strtolower(trim($item['subjectId'] ?? '')) !== $subjectKey) {
                $otherQuestions[] = $item;
            }
        }

        $questionsList = array_merge($otherQuestions, $newSubjectQuestions);
        saveQuestions($questionsFile, $questionsList);
        $msg_success = 'Đã lưu toàn bộ thay đổi thành công!';
    }

    $questionsList = loadQuestions($questionsFile);
    $questions = filterSubjectQuestions($questionsList, $subjectKey);
}

// === XÓA CÂU HỎI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question'])) {
    $index = (int)($_POST['index'] ?? -1);

    if ($index >= 0 && $index < count($questions)) {
        $globalIndex = findGlobalIndexBySubjectIndex($questionsList, $subjectKey, $index);

        if ($globalIndex !== null) {
            array_splice($questionsList, $globalIndex, 1);
            saveQuestions($questionsFile, $questionsList);
            $msg_success = 'Đã xóa câu hỏi thành công!';
        } else {
            $msg_error = 'Không tìm thấy câu hỏi cần xóa!';
        }

        $questionsList = loadQuestions($questionsFile);
        $questions = filterSubjectQuestions($questionsList, $subjectKey);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Đề Thi - Môn <?php echo htmlspecialchars(strtoupper($subjectKey)); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary:#0ea5e9;
            --primary-dark:#0284c7;
            --accent:#6366f1;
            --success:#10b981;
            --danger:#ef4444;
            --bg:linear-gradient(135deg,#f0f9ff 0%,#e0e7ff 100%);
            --surface:#ffffff;
            --text-main:#1e293b;
            --text-sub:#475569;
            --text-muted:#64748b;
            --border:#e2e8f0;
            --border-light:#f1f5f9;
            --shadow-sm:0 2px 8px rgba(15,23,42,0.04);
            --shadow-md:0 8px 24px rgba(15,23,42,0.06);
        }
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,-apple-system,sans-serif}
        body{background:var(--bg);color:var(--text-main);min-height:100vh;padding-bottom:60px}
        .navbar{background:rgba(255,255,255,0.85);backdrop-filter:saturate(180%) blur(12px);padding:14px 40px;display:flex;justify-content:space-between;align-items:center;box-shadow:var(--shadow-sm);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
        .logo{color:var(--primary-dark);font-size:20px;font-weight:800;text-decoration:none}
        .nav-links a{color:var(--text-sub);text-decoration:none;font-size:14px;font-weight:600;padding:10px 16px;border-radius:12px;transition:all .2s ease}
        .nav-links a:hover{background:#f1f5f9;color:var(--primary)}
        .container{max-width:1100px;width:100%;margin:32px auto;padding:0 24px}
        .header-box{background:var(--surface);padding:24px 28px;border-radius:16px;box-shadow:var(--shadow-md);border:1px solid var(--border-light);margin-bottom:28px}
        .header-box h1{font-size:22px;font-weight:800;color:var(--text-main)}
        .header-box p{font-size:14px;color:var(--text-muted);margin-top:6px}
        .alert{padding:16px 20px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:24px;border-left:5px solid;box-shadow:var(--shadow-sm)}
        .alert-success{color:#166534;background:#f0fdf4;border-left-color:var(--success)}
        .alert-error{color:#991b1b;background:#fef2f2;border-left-color:var(--danger)}
        .content-grid{display:grid;grid-template-columns:1fr;gap:24px}
        .card{background:var(--surface);padding:24px;border-radius:16px;box-shadow:var(--shadow-md);border:1px solid var(--border-light)}
        .card h2{font-size:18px;font-weight:800;color:var(--text-main);margin-bottom:18px}
        .btn-primary{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;border:none;padding:13px 24px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;text-transform:uppercase;letter-spacing:0.5px}
        .btn-danger{background:linear-gradient(135deg,var(--danger) 0%,#dc2626 100%);color:#fff;border:none;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer}
        .question-list{max-height:720px;overflow-y:auto;display:flex;flex-direction:column;gap:14px}
        .question-item{background:#f8fafc;border:1px solid var(--border);border-radius:14px;padding:16px}
        .question-top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
        .question-no{font-weight:800;color:var(--primary);font-size:14px}
        .small-muted{font-size:12px;color:var(--text-muted)}
        .question-block{margin-bottom:12px}
        .question-block label{display:block;font-size:12px;font-weight:700;color:var(--text-sub);margin-bottom:6px;text-transform:uppercase}
        .question-block input,.question-block textarea,.question-block select{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#fff;font-size:14px}
        .options-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .main-footer{text-align:center;padding:28px;color:var(--text-muted);font-size:13px;font-weight:500;margin-top:auto}
        @media (max-width:900px){.content-grid{grid-template-columns:1fr}.navbar{padding:14px 20px}.container{padding:0 16px}.options-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="navbar">
    <a href="teacher_dashboard.php" class="logo">Cổng Quản Lý Giáo Dục</a>
    <div class="nav-links">
        <a href="teacher_dashboard.php">Quay lại Trang chính</a>
        <a href="actions/logout.php">Đăng Xuất</a>
    </div>
</div>

<div class="container">
    <div class="header-box">
        <h1>Chỉnh Sửa Đề Thi - Môn <?php echo htmlspecialchars(strtoupper($subjectKey)); ?></h1>
        <p>Sửa trực tiếp từng câu hỏi, từng đáp án rồi lưu thẳng vào file JSON.</p>
    </div>

    <?php if ($msg_success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg_success); ?></div>
    <?php endif; ?>
    <?php if ($msg_error !== ''): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($msg_error); ?></div>
    <?php endif; ?>

    <div class="content-grid">
        <div class="card">
            <h2>Danh sách câu hỏi (<?php echo count($questions); ?> câu)</h2>

            <form method="POST">
                <div class="question-list">
                    <?php if (count($questions) === 0): ?>
                        <p style="text-align:center;color:var(--text-muted);padding:30px;">Chưa có câu hỏi nào cho môn này.</p>
                    <?php else: ?>
                        <?php foreach ($questions as $index => $q): ?>
                            <div class="question-item">
                                <div class="question-top">
                                    <div>
                                        <div class="question-no">Câu <?php echo $index + 1; ?></div>
                                        <div class="small-muted">Chỉnh sửa trực tiếp nội dung và đáp án</div>
                                    </div>
                                    <button type="submit" name="delete_question" value="1" class="btn-danger" onclick="this.form.index.value=<?php echo $index; ?>;return true;" formnovalidate>Xóa câu này</button>
                                </div>

                                <input type="hidden" name="index" value="">

                                <div class="question-block">
                                    <label>Nội dung câu hỏi</label>
                                    <textarea name="questions[<?php echo $index; ?>][question]" rows="3" required><?php echo htmlspecialchars($q['question'] ?? ''); ?></textarea>
                                    <input type="hidden" name="questions[<?php echo $index; ?>][questionId]" value="<?php echo htmlspecialchars($q['questionId'] ?? ''); ?>">
                                    <input type="hidden" name="questions[<?php echo $index; ?>][difficulty]" value="<?php echo htmlspecialchars($q['difficulty'] ?? 'easy'); ?>">
                                </div>

                                <div class="options-grid">
                                    <div class="question-block">
                                        <label>Đáp án A</label>
                                        <input type="text" name="questions[<?php echo $index; ?>][A]" required value="<?php echo htmlspecialchars($q['A'] ?? ''); ?>">
                                    </div>
                                    <div class="question-block">
                                        <label>Đáp án B</label>
                                        <input type="text" name="questions[<?php echo $index; ?>][B]" required value="<?php echo htmlspecialchars($q['B'] ?? ''); ?>">
                                    </div>
                                    <div class="question-block">
                                        <label>Đáp án C</label>
                                        <input type="text" name="questions[<?php echo $index; ?>][C]" required value="<?php echo htmlspecialchars($q['C'] ?? ''); ?>">
                                    </div>
                                    <div class="question-block">
                                        <label>Đáp án D</label>
                                        <input type="text" name="questions[<?php echo $index; ?>][D]" required value="<?php echo htmlspecialchars($q['D'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="question-block">
                                    <label>Đáp án đúng</label>
                                    <select name="questions[<?php echo $index; ?>][correct]" required>
                                        <option value="A" <?php echo (($q['correct'] ?? '') === 'A') ? 'selected' : ''; ?>>A</option>
                                        <option value="B" <?php echo (($q['correct'] ?? '') === 'B') ? 'selected' : ''; ?>>B</option>
                                        <option value="C" <?php echo (($q['correct'] ?? '') === 'C') ? 'selected' : ''; ?>>C</option>
                                        <option value="D" <?php echo (($q['correct'] ?? '') === 'D') ? 'selected' : ''; ?>>D</option>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="margin-top:18px;display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" name="save_all_questions" class="btn-primary">Lưu toàn bộ thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="main-footer">© 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online.</div>
</body>
</html>