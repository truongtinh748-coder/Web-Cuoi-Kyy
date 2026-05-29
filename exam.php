<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_logged'])) { 
    header("Location: login.php"); 
    exit(); 
}

$username = $_SESSION['user_logged'];
$subject = isset($_GET['subject']) ? strtolower(trim($_GET['subject'])) : 'toan';
$questions = [];

$jsonFile = 'data/questions.json'; 

if (file_exists($jsonFile)) {
    $fileContent = file_get_contents($jsonFile);
    if (!empty($fileContent)) {
        $all_questions = json_decode($fileContent, true);
        
        if (is_array($all_questions)) {
            $temp_questions = [];
            foreach ($all_questions as $q) {
                $q_subject = isset($q['subjectId']) ? strtolower(trim($q['subjectId'])) : '';
                if ($q_subject === $subject) {
                    $temp_questions[] = $q;
                }
            }
            
            if (count($temp_questions) > 0) {
                shuffle($temp_questions);
                $questions = array_slice($temp_questions, 0, 24); 
                $_SESSION['current_exam_questions'] = $questions; 
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phòng Thi Trực Tuyến</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f8fafc; color: #1e293b; padding-bottom: 60px; }
        .top-sticky-bar { position: sticky; top: 0; background: #0f172a; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; z-index: 100; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .title-area h2 { font-size: 18px; color: #38bdf8; text-transform: uppercase; }
        .right-nav-area { display: flex; align-items: center; gap: 20px; }
        .timer-box { background: #fef2f2; border: 1px solid #fee2e2; color: #ef4444; font-weight: 700; font-size: 16px; padding: 6px 14px; border-radius: 8px; }
        .btn-logout { background: #ef4444; color: white; padding: 6px 14px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 13px; }
        .exam-content { max-width: 800px; width: 92%; margin: 40px auto; }
        .question-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .question-card p { font-weight: 600; font-size: 16px; margin-bottom: 15px; color: #0f172a; }
        .question-card label { display: block; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: 0.2s; }
        .question-card label:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .question-card input[type="radio"] { margin-right: 12px; transform: scale(1.1); }
        .btn-submit-exam { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; border: none; width: 100%; padding: 16px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 16px; text-transform: uppercase; transition: 0.2s; }
        .btn-submit-exam:hover { filter: brightness(1.1); }
    </style>
</head>
<body>

    <div class="top-sticky-bar">
        <div class="title-area">
            <h2>Môn thi: <?php echo htmlspecialchars(strtoupper($subject)); ?> (Đề 24 Câu Ngẫu Nhiên)</h2>
        </div>
        <div class="right-nav-area">
            <div class="timer-box">⏱️ <span id="timeDisplay">15:00</span></div>
            <div>Thí sinh: <strong><?php echo htmlspecialchars($username); ?></strong></div>
            <a href="actions/logout.php" class="btn-logout">Đăng Xuất</a>
        </div>
    </div>

    <div class="exam-content">
        <?php if (count($questions) == 0): ?>
            <div class="question-card" style="text-align: center; padding: 40px;">
                <p>Không tìm thấy đủ dữ liệu câu hỏi môn này trong file data/questions.json.</p>
            </div>
        <?php else: ?>
            <form action="actions/submit_action.php" method="POST" id="mainExamForm">
                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                
                <?php foreach ($questions as $index => $q): ?>
                <div class="question-card">
                    <p>Câu <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($q['question']); ?></p>
                    <label><input type="radio" name="ans_<?php echo $index; ?>" value="A"> A. <?php echo htmlspecialchars($q['A']); ?></label>
                    <label><input type="radio" name="ans_<?php echo $index; ?>" value="B"> B. <?php echo htmlspecialchars($q['B']); ?></label>
                    <label><input type="radio" name="ans_<?php echo $index; ?>" value="C"> C. <?php echo htmlspecialchars($q['C']); ?></label>
                    <label><input type="radio" name="ans_<?php echo $index; ?>" value="D"> D. <?php echo htmlspecialchars($q['D']); ?></label>
                </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn-submit-exam">NỘP BÀI THI</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        let duration = 900; 
        const display = document.getElementById('timeDisplay');
        const interval = setInterval(() => {
            let min = Math.floor(duration / 60);
            let sec = duration % 60;
            display.innerHTML = `${min}:${sec < 10 ? '0' + sec : sec}`;
            if (duration <= 0) {
                clearInterval(interval);
                alert("Hết giờ làm bài! Hệ thống tự động nộp bài.");
                document.getElementById('mainExamForm').submit();
            }
            duration--;
        }, 1000);

        let cheatCount = 0;
        document.addEventListener("visibilitychange", function() {
            if (document.hidden) {
                cheatCount++;
                if (cheatCount >= 3) {
                    alert("⚠️ BẠN VI PHẠM QUY CHẾ CHUYỂN TAB QUÁ 3 LẦN! Hệ thống tự động nộp bài.");
                    document.getElementById('mainExamForm').submit();
                } else {
                    alert(`⚠️ CẢNH BÁO: Không được rời màn hình phòng thi! (Vi phạm: ${cheatCount}/3)`);
                }
            }
        });

        document.addEventListener('contextmenu', event => event.preventDefault());

        document.onkeydown = function(e) {
            if (e.keyCode == 123) return false; 
            if (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) return false; 
            if (e.ctrlKey && e.keyCode == 85) return false; 
            if (e.ctrlKey && (e.keyCode == 67 || e.keyCode == 86)) return false; 
        };
    </script>
</body>
</html>
