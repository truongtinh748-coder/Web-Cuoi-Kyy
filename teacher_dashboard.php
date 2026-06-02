<?php
// ⏰ TĂNG THỜI GIAN SỐNG CỦA SESSION LÊN 1 NGÀY (86400 giây)
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// 🔐 BẢO MẬT: Kiểm tra quyền truy cập giáo viên, nếu không phải giáo viên đá về trang login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$questionsFile = 'data/questions.json';
$resultsFile = 'data/results.json';

// Tự động tạo thư mục data nếu chưa tồn tại
if (!file_exists(dirname($questionsFile))) {
    mkdir(dirname($questionsFile), 0777, true);
}

// 🗄️ Tải danh sách kết quả thi của học sinh để gán vào biến của bạn
$default_students = [];
if (file_exists($resultsFile)) {
    $default_students = json_decode(file_get_contents($resultsFile), true);
}
if (!is_array($default_students)) {
    $default_students = [];
}

$msg_success = "";
$msg_error = "";

// 📝 XỬ LÝ LOGIC: THÊM CÂU HỎI THỦ CÔNG (TAB 2)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_manual'])) {
    $subjectId = isset($_POST['subjectId']) ? trim($_POST['subjectId']) : '';
    $questionText = isset($_POST['question']) ? trim($_POST['question']) : '';
    $ansA = isset($_POST['A']) ? trim($_POST['A']) : '';
    $ansB = isset($_POST['B']) ? trim($_POST['B']) : '';
    $ansC = isset($_POST['C']) ? trim($_POST['C']) : '';
    $ansD = isset($_POST['D']) ? trim($_POST['D']) : '';
    $correct = isset($_POST['correct']) ? trim($_POST['correct']) : '';

    if ($subjectId && $questionText && $ansA && $ansB && $ansC && $ansD && $correct) {
        $questions = [];
        if (file_exists($questionsFile)) {
            $questions = json_decode(file_get_contents($questionsFile), true);
        }
        if (!is_array($questions)) {
            $questions = [];
        }

        // Cấu trúc lưu trữ phân nhóm theo subjectId: toan, php, xstk
        $questions[$subjectId][] = [
            'question' => $questionText,
            'A' => $ansA,
            'B' => $ansB,
            'C' => $ansC,
            'D' => $ansD,
            'correct' => $correct
        ];

        file_put_contents($questionsFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $msg_success = "Đã lưu câu hỏi trắc nghiệm mới thành công!";
    } else {
        $msg_error = "Vui lòng nhập đầy đủ thông tin trường câu hỏi!";
    }
}

// 📂 XỬ LÝ LOGIC: IMPORT ĐỀ THI TỪ FILE JSON (TAB 3)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_file'])) {
    if (isset($_FILES['exam_file']) && $_FILES['exam_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['exam_file']['tmp_name'];
        $fileContent = file_get_contents($fileTmpPath);
        $uploadedData = json_decode($fileContent, true);

        if (is_array($uploadedData)) {
            $currentQuestions = [];
            if (file_exists($questionsFile)) {
                $currentQuestions = json_decode(file_get_contents($questionsFile), true);
            }
            if (!is_array($currentQuestions)) {
                $currentQuestions = [];
            }

            // Đồng bộ dữ liệu cũ và dữ liệu mới import vào file JSON chung
            foreach ($uploadedData as $subKey => $qList) {
                if (is_array($qList)) {
                    if (!isset($currentQuestions[$subKey])) {
                        $currentQuestions[$subKey] = [];
                    }
                    foreach ($qList as $qItem) {
                        if (isset($qItem['question'], $qItem['A'], $qItem['B'], $qItem['C'], $qItem['D'], $qItem['correct'])) {
                            $currentQuestions[$subKey][] = [
                                'question' => $qItem['question'],
                                'A' => $qItem['A'],
                                'B' => $qItem['B'],
                                'C' => $qItem['C'],
                                'D' => $qItem['D'],
                                'correct' => $qItem['correct']
                            ];
                        }
                    }
                }
            }

            file_put_contents($questionsFile, json_encode($currentQuestions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $msg_success = "Nhập toàn bộ dữ liệu đề thi từ file JSON thành công!";
        } else {
            $msg_error = "Định dạng cấu trúc file câu hỏi JSON không hợp lệ!";
        }
    } else {
        $msg_error = "Tải file đề thi lên thất bại, vui lòng thử lại!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển Giáo Viên</title>
    <style>
        /* RESET & BASE - ĐỒNG BỘ STYLE MODERN VỚI LOGIN */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; }
        body { 
            display: flex; flex-direction: column; min-height: 100vh; 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%); 
            color: #334155; 
        }
        
        /* NAVBAR */
        .navbar { 
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            padding: 16px 50px; display: flex; justify-content: space-between; align-items: center; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); border-bottom: 1px solid #e2e8f0;
            position: sticky; top: 0; z-index: 10;
        }
        .logo { color: #0284c7; font-size: 20px; font-weight: 800; text-decoration: none; letter-spacing: -0.5px; }
        .nav-links a { 
            color: #475569; text-decoration: none; font-size: 15px; font-weight: 600; 
            transition: color 0.2s ease; padding: 8px 16px; border-radius: 8px;
        }
        .nav-links a.logout-btn { color: #ef4444; }
        .nav-links a.logout-btn:hover { background: #fef2f2; }

        /* MAIN DASHBOARD WRAPPER */
        .dashboard-container { max-width: 1100px; width: 100%; margin: 40px auto; padding: 0 20px; flex: 1; }
        
        .welcome-box { margin-bottom: 25px; text-align: left; }
        .welcome-box h2 { font-size: 24px; color: #1e293b; font-weight: 800; }
        .welcome-box p { color: #64748b; font-size: 14px; margin-top: 4px; }

        /* TAB CONTROLS PILL STYLE */
        .tab-control { 
            display: flex; background: #e2e8f0; padding: 6px; 
            border-radius: 14px; margin-bottom: 25px; gap: 5px; max-width: 600px;
        }
        .tab-btn { 
            flex: 1; border: none; background: transparent; padding: 12px 20px; 
            font-size: 14px; font-weight: 700; color: #64748b; cursor: pointer; 
            border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
        }
        .tab-btn.active { 
            background: #ffffff; color: #0ea5e9; box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
        }

        /* TAB CONTENTS PANEL */
        .tab-content { 
            display: none; background: #ffffff; padding: 35px; border-radius: 20px; 
            box-shadow: 0 15px 35px -5px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
            animation: fadeIn 0.4s ease forwards;
        }
        .tab-content h3 { font-size: 18px; font-weight: 800; margin-bottom: 25px; color: #1e293b; border-left: 4px solid #0ea5e9; padding-left: 12px; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* TABLES STYLE */
        .table-responsive { overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 15px; }
        .data-table th { background: #f8fafc; color: #475569; font-weight: 700; padding: 16px; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .data-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #f8fafc; }

        /* FORM ELEMENTS */
        .form-group { margin-bottom: 22px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .form-group input[type="text"], .form-group input[type="file"], .form-group select, .form-group textarea { 
            width: 100%; padding: 14px 18px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 15px; outline: none; transition: all 0.2s ease; color: #1e293b; 
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #0ea5e9; background: #ffffff; box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15); }
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 20px; margin-bottom: 5px; }

        /* SUBMIT BUTTONS */
        .btn-submit { 
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); color: #ffffff; border: none; padding: 15px 30px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; text-transform: uppercase; box-shadow: 0 6px 18px rgba(37, 99, 235, 0.2); transition: all 0.2s ease; letter-spacing: 0.5px; 
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(37, 99, 235, 0.3); }

        /* ALERTS */
        .alert { padding: 14px 18px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 25px; text-align: left; border-left: 4px solid; }
        .alert-success { color: #15803d; background: #f0fdf4; border-left-color: #22c55e; }
        .alert-error { color: #b91c1c; background: #fef2f2; border-left-color: #ef4444; }

        /* FOOTER */
        .main-footer { text-align: center; padding: 25px; color: #64748b; font-size: 13px; font-weight: 500; margin-top: auto; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="teacher_dashboard.php" class="logo">Cổng Quản Lý Giáo Dục</a>
        <div class="nav-links">
            <a href="actions/logout.php" class="logout-btn">Đăng Xuất</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="welcome-box">
            <h2>Xin chào, Giáo viên <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>!</h2>
            <p>Hệ thống hỗ trợ theo dõi tiến độ thi cử của học sinh và quản lý ngân hàng câu hỏi tổng hợp.</p>
        </div>

        <?php if ($msg_success != ""): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg_success); ?></div><?php endif; ?>
        <?php if ($msg_error != ""): ?><div class="alert alert-error"><?php echo htmlspecialchars($msg_error); ?></div><?php endif; ?>

        <div class="tab-control">
            <button class="tab-btn active" onclick="openTab(event, 'tab1')">Kết quả học sinh</button>
            <button class="tab-btn" onclick="openTab(event, 'tab2')">Thêm câu hỏi</button>
            <button class="tab-btn" onclick="openTab(event, 'tab3')">Nhập đề thi</button>
        </div>

        <div id="tab1" class="tab-content" style="display: block;">
            <h3>Danh sách kết quả bài làm của học sinh</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tài khoản / MSSV</th>
                            <th>Môn học</th>
                            <th>Số câu đúng</th>
                            <th>Điểm số</th>
                            <th>Thời gian nộp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // SỬA LỖI: Vòng lặp an toàn chạy qua biến đã kiểm tra xử lý lỗi ở trên cùng
                        if (count($default_students) > 0):
                            foreach ($default_students as $stt => $res):
                        ?>
                            <tr>
                                <td><?php echo $stt + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($res['username']); ?></strong></td>
                                <td style="text-transform: uppercase; color:#3b82f6; font-weight:700;"><?php echo htmlspecialchars($res['subject']); ?></td>
                                <td><?php echo htmlspecialchars($res['correct']); ?></td>
                                <td style="font-weight:800; color:<?php echo $res['score'] >= 5 ? '#10b981' : '#ef4444'; ?>">
                                    <?php echo htmlspecialchars($res['score']); ?>đ
                                </td>
                                <td><?php echo isset($res['time']) ? htmlspecialchars($res['time']) : '---'; ?></td>
                            </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 40px;">Chưa có học sinh nào nộp bài hoặc dữ liệu rỗng.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab2" class="tab-content">
            <h3>Thêm câu hỏi trắc nghiệm mới</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Chọn môn học</label>
                    <select name="subjectId" required>
                        <option value="Toan">Toán học (Toan)</option>
                        <option value="Web">Lập trình Web (Web)</option>
                        <option value="TA">Tiếng Anh chuyên ngành (TA)</option>
                        <option value="CSDL">Cơ Sở Dữ Liệu (CSDL)</option>
                        <option value="MMT">Mạng Máy Tính (MMT)</option>
                        <option value="XSTK">Xác suất thống kê (XSTK)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nội dung câu hỏi</label>
                    <textarea name="question" rows="3" required placeholder="Nhập câu hỏi đề bài thi tại đây..."></textarea>
                </div>
                <div class="options-grid">
                    <div class="form-group"><label>Đáp án A</label><input type="text" name="A" required placeholder="Nội dung đáp án A"></div>
                    <div class="form-group"><label>Đáp án B</label><input type="text" name="B" required placeholder="Nội dung đáp án B"></div>
                    <div class="form-group"><label>Đáp án C</label><input type="text" name="C" required placeholder="Nội dung đáp án C"></div>
                    <div class="form-group"><label>Đáp án D</label><input type="text" name="D" required placeholder="Nội dung đáp án D"></div>
                </div>
                <div class="form-group">
                    <label>Đáp án đúng</label>
                    <select name="correct" required>
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                    </select>
                </div>
                <button type="submit" name="add_manual" class="btn-submit">Lưu câu hỏi</button>
            </form>
        </div>

        <div id="tab3" class="tab-content">
            <h3>Nhập bộ đề thi từ file (.json)</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Chọn file JSON chứa cấu trúc đề thi hợp lệ</label>
                    <input type="file" name="exam_file" accept=".json" required style="padding: 10px;">
                </div>
                <button type="submit" name="upload_file" class="btn-submit" style="background: #4f46e5">Bắt đầu nhập</button>
            </form>
        </div>
    </div>

    <div class="main-footer">© 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online.</div>

    <script>
        // GIỮ NGUYÊN SCRIPT CHUYỂN TAB TRÊN DASHBOARD CỦA BẠN
        function openTab(evt, tabName) {
            let i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
    </script>
</body>
</html>