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
$examLocked = false; // Biến cờ kiểm tra trạng thái khóa đề

// --- 1. CẬP NHẬT: KIỂM TRA TRẠNG THÁI ĐÓNG/MỞ ĐỀ THI TỪ ADMIN ---
$examStatusFile = 'data/exam_status.json';
$examStatus = file_exists($examStatusFile) ? json_decode(file_get_contents($examStatusFile), true) : [];
$upperSubject = strtoupper($subject);

// Nếu môn thi chưa cấu hình hoặc đang ở trạng thái 'closed' -> Đánh dấu là đã khóa
if (!isset($examStatus[$upperSubject]) || $examStatus[$upperSubject] !== 'open') {
    $examLocked = true;
}

// Chỉ tải dữ liệu câu hỏi nếu đề đang ở trạng thái MỞ (open)
if (!$examLocked) {
    $jsonFile = 'data/questions.json'; 

    if (file_exists($jsonFile)) {
        $fileContent = file_get_contents($jsonFile);
        if (!empty($fileContent)) {
            $all_questions = json_decode($fileContent, true);
            
            if (is_array($all_questions)) {
                $temp_questions = [];
                foreach ($all_questions as $q) {
                    $q_subject = isset($q['subjectId']) ? strtolower(trim($q['subjectId'])) : '';
                    // Chỉ lấy câu hỏi thuộc môn này và đã được phê duyệt (status là trống hoặc approved)
                    if ($q_subject === $subject && (!isset($q['status']) || $q['status'] === 'approved')) {
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
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phòng Thi Trực Tuyến</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        /* CSS Giao diện thông báo chặn khi đề khóa */
        .lock-card { text-align: center; padding: 50px 30px; border-top: 5px solid #ef4444; }
        .lock-card h2 { color: #b91c1c; margin: 15px 0 10px 0; font-size: 24px; }
        .lock-card p { color: #64748b; font-weight: normal; font-size: 15px; margin-bottom: 25px; }
        .btn-back { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.2s; }
        .btn-back:hover { background: #1d4ed8; }
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
        <?php if ($examLocked): ?>
            <div class="question-card lock-card">
                <div style="font-size: 60px;">🔒</div>
                <h2>MÔN THI HIỆN ĐANG KHÓA</h2>
                <p>Môn học này đã đóng hoặc chưa được Giám thị kích hoạt quyền truy cập. Vui lòng quay lại sau.</p>
                <a href="javascript:history.back()" class="btn-back">Quay Lại Giao Diện Chính</a>
            </div>

        <?php elseif (count($questions) == 0): ?>
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
        // Đồng bộ trạng thái khóa sang JavaScript để ngăn chạy ngầm bộ đếm
        const isExamLocked = <?php echo $examLocked ? 'true' : 'false'; ?>;

        if (isExamLocked) {
            document.getElementById('timeDisplay').innerText = "--:--";
        } else {
            // --- LOGIC ĐẾM THỜI GIAN GIỮ NGUYÊN (CHỈ SỬA THẨM MỸ THÔNG BÁO) ---
            let duration = 900; 
            const display = document.getElementById('timeDisplay');
            const interval = setInterval(() => {
                let min = Math.floor(duration / 60);
                let sec = duration % 60;
                display.innerHTML = `${min}:${sec < 10 ? '0' + sec : sec}`;
                if (duration <= 0) {
                    clearInterval(interval);
                    
                    // THẨM MỸ: Đổi alert() xám thành SweetAlert2 đỏ rực, tự động nộp bài sau 2.5 giây
                    Swal.fire({
                        icon: 'warning',
                        title: 'Hết giờ làm bài!',
                        text: 'Thời gian đã cạn. Hệ thống đang tiến hành thu và nộp bài thi của bạn...',
                        timer: 2500,
                        showConfirmButton: false,
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        document.getElementById('mainExamForm').submit();
                    });
                }
                duration--;
            }, 1000);

            // --- LOGIC CHỐNG GIAN LẬN CHUYỂN TAB GIỮ NGUYÊN (CHỈ SỬA THẨM MỸ THÔNG BÁO) ---
            let cheatCount = 0;
            document.addEventListener("visibilitychange", function() {
                if (document.hidden) {
                    cheatCount++;
                    if (cheatCount >= 3) {
                        // THẨM MỸ: Thông báo cưỡng chế nộp bài cực đẹp, khóa tương tác bên ngoài
                        Swal.fire({
                            icon: 'error',
                            title: 'Hệ Thống Đã Khóa Đề!',
                            text: 'Bạn đã vi phạm quy chế phòng thi (Chuyển tab quá 3 lần). Bài thi sẽ được nộp tự động ngay lập tức!',
                            timer: 3000,
                            showConfirmButton: false,
                            timerProgressBar: true,
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            document.getElementById('mainExamForm').submit();
                        });
                    } else {
                        // THẨM MỸ: Thông báo cảnh báo vi phạm sang trọng, hiển thị rõ số lần phạt
                        Swal.fire({
                            icon: 'warning',
                            title: 'CẢNH BÁO VI PHẠM',
                            html: `Tuyệt đối không được rời khỏi màn hình phòng thi!<br><br>Ghi nhận vi phạm: <b style="color: #ef4444; font-size: 18px;">${cheatCount} / 3</b> lần.`,
                            confirmButtonText: 'Tôi cam kết không tái phạm',
                            confirmButtonColor: '#2563eb',
                            allowOutsideClick: false
                        });
                    }
                }
            });
        }

        // --- CÁC TÍNH NĂNG CHỐNG CLICK CHUỘT PHẢI, COPPY/PASTE GIỮ NGUYÊN ---
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