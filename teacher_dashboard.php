<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
// ⏰ Tăng thời gian sống của session lên 1 ngày (86400 giây)
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Bảo mật: chỉ cho phép giáo viên truy cập
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'teacher') {
    header("Location: login.php");
    exit();
}

$questionsFile = 'data/questions.json';
$resultsFile = 'data/results.json';
$activeSessionsFile = 'data/active_sessions.json';
$examStatusFile = 'data/exam_status.json';

$dataDir = dirname($questionsFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// 🗄️ Tải danh sách kết quả thi của học sinh
$default_students = [];
if (file_exists($resultsFile)) {
    $default_students = json_decode(file_get_contents($resultsFile), true);
}
if (!is_array($default_students)) {
    $default_students = [];
}

// 🗄️ Tải danh sách thí sinh đang làm bài
$activeSessions = [];
if (file_exists($activeSessionsFile)) {
    $activeSessions = json_decode(file_get_contents($activeSessionsFile), true);
}
if (!is_array($activeSessions)) {
    $activeSessions = [];
}

// 🗄️ Tải trạng thái đề thi
$examStatus = [];
if (file_exists($examStatusFile)) {
    $examStatus = json_decode(file_get_contents($examStatusFile), true);
}
if (!is_array($examStatus)) {
    $examStatus = [];
}

$msg_success = "";
$msg_error = "";

// 📝 Xử lý: thêm câu hỏi thủ công (giữ nguyên logic cũ)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_manual'])) {
    $subjectId = trim($_POST['subjectId'] ?? '');
    $questionText = trim($_POST['question'] ?? '');
    $ansA = trim($_POST['A'] ?? '');
    $ansB = trim($_POST['B'] ?? '');
    $ansC = trim($_POST['C'] ?? '');
    $ansD = trim($_POST['D'] ?? '');
    $correct = trim($_POST['correct'] ?? '');

    if ($subjectId && $questionText && $ansA && $ansB && $ansC && $ansD && $correct) {
        $questions = [];
        if (file_exists($questionsFile)) {
            $questions = json_decode(file_get_contents($questionsFile), true);
        }
        if (!is_array($questions)) {
            $questions = [];
        }

        if (!isset($questions[$subjectId])) {
            $questions[$subjectId] = [];
        }
        $questions[$subjectId][] = [
            'question' => $questionText,
            'A' => $ansA,
            'B' => $ansB,
            'C' => $ansC,
            'D' => $ansD,
            'correct' => $correct,
            'status' => 'approved'
        ];

        file_put_contents($questionsFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $msg_success = "Đã lưu câu hỏi trắc nghiệm mới thành công!";
    } else {
        $msg_error = "Vui lòng nhập đầy đủ thông tin trường câu hỏi!";
    }
}

// 📂 Xử lý: import đề thi từ file JSON (giữ nguyên logic cũ)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_file'])) {
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

            foreach ($uploadedData as $subKey => $qList) {
                if (!is_array($qList)) {
                    continue;
                }
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
                            'correct' => $qItem['correct'],
                            'status' => 'Pending'
                        ];
                    }
                }
            }

            file_put_contents($questionsFile, json_encode($currentQuestions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $msg_success = "Nhập toàn bộ dữ liệu đề thi từ file JSON thành công (đang chờ admin phê duyệt)!";
        } else {
            $msg_error = "Định dạng cấu trúc file câu hỏi JSON không hợp lệ!";
        }
    } else {
        $msg_error = "Tải file đề thi lên thất bại, vui lòng thử lại!";
    }
}

// 👉 Xử lý: Xóa đề thi theo môn (tác động trực tiếp vào questions.json)
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['delete_subject'])) {
    $subjectToDelete = trim($_GET['delete_subject']);

    if (file_exists($questionsFile)) {
        $questions = json_decode(file_get_contents($questionsFile), true);
        if (!is_array($questions)) {
            $questions = [];
        }

        if (isset($questions[$subjectToDelete])) {
            unset($questions[$subjectToDelete]);
            file_put_contents($questionsFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $msg_success = "Đã xóa đề thi môn {$subjectToDelete} thành công!";
        } else {
            $msg_error = "Không tìm thấy đề thi môn này!";
        }
    } else {
        $msg_error = "File câu hỏi không tồn tại!";
    }
}

// 👉 Xử lý: Cập nhật trạng thái đề thi (Approved/Pending) - giữ nguyên logic cũ
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {
    $subjectId = trim($_POST['subjectId'] ?? '');
    $newStatus = trim($_POST['status'] ?? '');

    if ($subjectId && $newStatus && in_array($newStatus, ['Approved', 'Pending'])) {
        if (file_exists($questionsFile)) {
            $questions = json_decode(file_get_contents($questionsFile), true);
            if (!is_array($questions)) {
                $questions = [];
            }

            if (isset($questions[$subjectId])) {
                foreach ($questions[$subjectId] as $key => $q) {
                    $questions[$subjectId][$key]['status'] = $newStatus;
                }

                file_put_contents($questionsFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                $msg_success = "Đã cập nhật trạng thái đề thi môn {$subjectId} thành {$newStatus}!";
            } else {
                $msg_error = "Không tìm thấy đề thi môn này!";
            }
        } else {
            $msg_error = "File câu hỏi không tồn tại!";
        }
    } else {
        $msg_error = "Thông tin không hợp lệ!";
    }
}

// 👉 Xử lý: hủy phiên làm bài của thí sinh
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['kick_user'])) {
    $userKick = trim($_GET['kick_user']);
    if (isset($activeSessions[$userKick])) {
        unset($activeSessions[$userKick]);
        file_put_contents($activeSessionsFile, json_encode($activeSessions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $msg_success = "Đã hủy phiên làm bài của {$userKick}!";
    } else {
        $msg_error = "Không tìm thấy phiên làm bài của thí sinh này!";
    }
}

// 👉 Thống kê số học sinh đang làm bài
$studentsDoingExam = 0;
foreach ($default_students as $res) {
    if (($res['status'] ?? '') === 'doing') {
        $studentsDoingExam++;
    }
}

// 👉 Đọc ngân hàng câu hỏi từ questions.json
$questionsList = [];
if (file_exists($questionsFile)) {
    $questionsList = json_decode(file_get_contents($questionsFile), true);
}
if (!is_array($questionsList)) {
    $questionsList = [];
}

// 👉 Danh sách môn học
$subjectNames = [
    'Toan' => 'Toán học',
    'Web' => 'Lập trình Web',
    'TA' => 'Tiếng Anh chuyên ngành',
    'CSDL' => 'Cơ Sở Dữ Liệu',
    'MMT' => 'Mạng Máy Tính',
    'XSTK' => 'Xác suất thống kê'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển Giáo Viên</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary:#0ea5e9;
            --primary-dark:#0284c7;
            --primary-light:#e0f2fe;
            --accent:#6366f1;
            --success:#10b981;
            --danger:#ef4444;
            --bg-start:#f0f9ff;
            --bg-end:#e0e7ff;
            --surface:#ffffff;
            --text-main:#1e293b;
            --text-sub:#475569;
            --text-muted:#64748b;
            --border:#e2e8f0;
            --border-light:#f1f5f9;
            --shadow-sm:0 2px 8px rgba(15,23,42,0.04);
            --shadow-md:0 8px 24px rgba(15,23,42,0.06);
            --radius-md:14px;
            --radius-lg:20px;
        }
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,-apple-system,sans-serif}
        body{
            display:flex;
            flex-direction:column;
            min-height:100vh;
            background:linear-gradient(135deg,var(--bg-start) 0%,var(--bg-end) 100%);
            color:var(--text-main);
            line-height:1.5;
        }
        .navbar{
            background:rgba(255,255,255,0.85);
            backdrop-filter:saturate(180%) blur(12px);
            padding:14px 40px 14px 50px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            box-shadow:var(--shadow-sm);
            border-bottom:1px solid var(--border);
            position:sticky;
            top:0;
            z-index:100;
        }
        .logo{
            display:flex;
            align-items:center;
            gap:12px;
            color:var(--primary-dark);
            font-size:20px;
            font-weight:800;
            text-decoration:none;
        }
        .logo-icon{
            width:32px;height:32px;border-radius:8px;
            background:linear-gradient(135deg,var(--primary) 0%,var(--accent) 100%);
            display:flex;align-items:center;justify-content:center;
            color:#fff;font-weight:800;font-size:16px;
        }
        .nav-links a{
            color:var(--text-sub);
            text-decoration:none;
            font-size:14px;
            font-weight:600;
            padding:10px 16px;
            border-radius:12px;
        }
        .nav-links a.logout-btn{color:var(--danger)}
        .dashboard-container{
            max-width:1180px;
            width:100%;
            margin:28px auto;
            padding:0 18px;
            flex:1;
        }
        .welcome-box,
        .section-box{
            background:var(--surface);
            border-radius:var(--radius-lg);
            box-shadow:var(--shadow-md);
            border:1px solid var(--border-light);
        }
        .welcome-box{
            margin-bottom:22px;
            padding:20px 22px;
        }
        .welcome-box h2{font-size:22px;font-weight:800}
        .welcome-box p{font-size:14px;color:var(--text-muted);margin-top:6px}
        .alert{
            padding:14px 18px;
            border-radius:var(--radius-md);
            font-size:14px;
            font-weight:600;
            margin-bottom:18px;
            border-left:5px solid;
        }
        .alert-success{color:#166534;background:#f0fdf4;border-left-color:var(--success)}
        .alert-error{color:#991b1b;background:#fef2f2;border-left-color:var(--danger)}
        .section-wrapper{display:flex;flex-direction:column;gap:20px}
        .section-box{
            padding:20px;
            position:relative;
            overflow:hidden;
        }
        .section-box::before{
            content:"";
            position:absolute;
            top:0;left:0;right:0;height:4px;
            background:linear-gradient(90deg,var(--primary) 0%,var(--accent) 100%);
        }
        .section-header{margin-bottom:10px}
        .section-header h2{
            font-size:19px;
            font-weight:800;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .section-header h2::before{
            content:"";
            width:6px;height:18px;border-radius:4px;
            background:linear-gradient(180deg,var(--primary) 0%,var(--accent) 100%);
        }
        .section-header p{font-size:13px;color:var(--text-muted);margin-top:5px}
        .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:12px;
            margin:14px 0 10px;
        }
        .stat-card{
            background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);
            border-radius:var(--radius-md);
            padding:14px 16px;
            border:1px solid var(--border);
        }
        .stat-label{
            font-size:11px;
            text-transform:uppercase;
            color:var(--text-muted);
            letter-spacing:0.6px;
            margin-bottom:6px;
            font-weight:700;
        }
        .stat-value{
            font-size:22px;
            font-weight:800;
            background:linear-gradient(135deg,var(--primary) 0%,var(--accent) 100%);
            -webkit-background-clip:text;
            background-clip:text;
            color:transparent;
        }
        .tab-sub-control{
            display:flex;
            gap:6px;
            margin:16px 0 14px;
            background:linear-gradient(135deg,#f1f5f9 0%,#e2e8f0 100%);
            padding:5px;
            border-radius:999px;
            max-width:100%;
        }
        .tab-sub-btn{
            flex:1;
            border:none;
            background:transparent;
            padding:10px 12px;
            border-radius:999px;
            font-size:13px;
            font-weight:700;
            cursor:pointer;
            color:var(--text-muted);
        }
        .tab-sub-btn.active{
            background:#fff;
            color:var(--primary);
            box-shadow:0 6px 18px rgba(14,165,233,0.18);
        }
        .sub-tab-content{display:none}
        .sub-tab-content h3{
            font-size:16px;
            font-weight:800;
            margin-bottom:14px;
            color:var(--text-main);
        }
        .table-responsive{
            overflow-x:auto;
            border-radius:var(--radius-md);
            border:1px solid var(--border);
            box-shadow:var(--shadow-sm);
        }
        .results-scroll-box{
            max-height:420px;
            overflow-y:auto;
            overflow-x:hidden;
            border-radius:var(--radius-md);
            border:1px solid var(--border);
            box-shadow:var(--shadow-sm);
        }
        .data-table{
            width:100%;
            border-collapse:collapse;
            font-size:13px;
            table-layout:fixed;
        }
        .data-table th,
        .data-table td{
            padding:10px 8px;
            border-bottom:1px solid var(--border-light);
            color:var(--text-main);
            vertical-align:top;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .data-table th{
            background:linear-gradient(180deg,#f8fafc,#f1f5f9);
            color:var(--text-sub);
            font-weight:700;
            text-transform:uppercase;
            font-size:10px;
            letter-spacing:0.4px;
            white-space:nowrap;
        }
        .data-table td{
            white-space:nowrap;
        }
        .data-table td:nth-child(5){
            white-space:normal;
            word-break:break-word;
        }
        .form-group{margin-bottom:16px}
        .form-group label{
            display:block;
            font-size:12px;
            font-weight:700;
            color:var(--text-sub);
            text-transform:uppercase;
            margin-bottom:7px;
        }
        .form-group input[type=text],
        .form-group input[type=file],
        .form-group select,
        .form-group textarea{
            width:100%;
            padding:11px 13px;
            background:#f8fafc;
            border:1.5px solid var(--border);
            border-radius:var(--radius-md);
            font-size:14px;
            outline:none;
            color:var(--text-main);
        }
        .options-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }
        .btn-submit{
            background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
            color:#fff;
            border:none;
            padding:12px 22px;
            border-radius:var(--radius-md);
            font-size:13px;
            font-weight:700;
            cursor:pointer;
        }
        .btn-small{
            padding:7px 12px;
            font-size:12px;
            color:#fff;
            border-radius:8px;
            text-decoration:none;
            font-weight:700;
            display:inline-block;
            margin-right:6px;
        }
        .chat-box{
            background:var(--surface);
            padding:20px;
            border-radius:var(--radius-lg);
            box-shadow:var(--shadow-md);
            border:1px solid var(--border-light);
            display:flex;
            flex-direction:column;
            gap:14px;
        }
        .chat-box-header h2{
            font-size:19px;
            font-weight:800;
            color:var(--text-main);
        }
        .chat-box-header p{
            font-size:13px;
            color:var(--text-muted);
            margin-top:5px;
        }
        .chat-frame-wrap{
            height:520px;
            border:1px solid var(--border);
            border-radius:14px;
            overflow:hidden;
            background:#fff;
        }
        .chat-frame{
            width:100%;
            height:100%;
            border:none;
            background:#fff;
            display:block;
        }
        .main-footer{
            text-align:center;
            padding:22px;
            color:var(--text-muted);
            font-size:13px;
            margin-top:auto;
        }
        @media (max-width:900px){
            .navbar{padding:12px 16px}
            .dashboard-container{padding:0 12px;margin:18px auto}
            .section-box{padding:16px}
            .welcome-box{padding:18px}
            .stats-grid{grid-template-columns:1fr}
            .data-table{font-size:12px}
            .options-grid{grid-template-columns:1fr}
            .chat-frame-wrap{height:480px}
        }
    </style>
</head>
<body>

<div class="navbar">
    <a href="teacher_dashboard.php" class="logo">
        <div class="logo-icon">T</div>
        Cổng Quản Lý Giáo Dục
    </a>
    <div class="nav-links">
        <a href="actions/logout.php" class="logout-btn">Đăng Xuất</a>
    </div>
</div>

<div class="dashboard-container">
    <div class="welcome-box">
        <h2>Xin chào, Giáo viên <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>!</h2>
        <p>Hệ thống hỗ trợ theo dõi tiến độ thi cử của học sinh và quản lý ngân hàng đề thi.</p>
    </div>

    <?php if ($msg_success !== ""): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg_success); ?></div>
    <?php endif; ?>
    <?php if ($msg_error !== ""): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($msg_error); ?></div>
    <?php endif; ?>

    <div class="section-wrapper">
        <section class="section-box">
            <div class="section-header">
                <h2>Quản lý học sinh</h2>
                <p>Theo dõi tình trạng làm bài, thời gian làm, số lần thoát giao diện.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Số học sinh đang làm bài</div>
                    <div class="stat-value"><?php echo htmlspecialchars($studentsDoingExam); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Tổng lượt nộp bài</div>
                    <div class="stat-value"><?php echo count($default_students); ?></div>
                </div>
            </div>

            <div class="tab-sub-control">
                <button class="tab-sub-btn active" onclick="openSubTab(event,'student_results')">Kết quả nộp bài</button>
                <button class="tab-sub-btn" onclick="openSubTab(event,'online_students')">Đang làm bài</button>
                <button class="tab-sub-btn" onclick="openSubTab(event,'boxchat')">Boxchat</button>
            </div>

            <div id="student_results" class="sub-tab-content" style="display:block;">
                <h3>Danh sách kết quả bài làm</h3>
                <div class="results-scroll-box">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tài khoản</th>
                            <th>Môn</th>
                            <th>Đúng</th>
                            <th>Điểm</th>
                            <th>Phút</th>
                            <th>Bắt đầu</th>
                            <th>Thoát</th>
                            <th>Nộp</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($default_students) > 0): ?>
                            <?php foreach ($default_students as $stt => $res): ?>
                                <tr>
                                    <td><?php echo $stt + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($res['username'] ?? ''); ?></strong></td>
                                    <td style="text-transform:uppercase;color:#3b82f6;font-weight:700;"><?php echo htmlspecialchars($res['subject'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($res['correct'] ?? '0'); ?></td>
                                    <td style="font-weight:800;color:<?php echo ($res['score'] ?? 0) >= 5 ? 'var(--success)' : 'var(--danger)'; ?>">
                                        <?php echo htmlspecialchars($res['score'] ?? '0'); ?>đ
                                    </td>
                                    <td><?php echo htmlspecialchars($res['duration_minutes'] ?? '---'); ?></td>
                                    <td><?php echo htmlspecialchars($res['start_time'] ?? '---'); ?></td>
                                    <td><?php echo htmlspecialchars($res['tab_switch_count'] ?? '0'); ?></td>
                                    <td><?php echo htmlspecialchars($res['time'] ?? '---'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:24px;">Chưa có học sinh nào nộp bài hoặc dữ liệu rỗng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="online_students" class="sub-tab-content">
                <h3>Theo dõi thí sinh đang làm bài trực tuyến</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Họ tên</th>
                            <th>Môn thi</th>
                            <th>Bắt đầu</th>
                            <th>Hành động</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($activeSessions) > 0): ?>
                            <?php foreach ($activeSessions as $username => $sessionData): ?>
                                <tr>
                                    <td><strong style="color:#b91c1c;"><?php echo htmlspecialchars($username); ?></strong></td>
                                    <td><?php echo htmlspecialchars($sessionData['fullname'] ?? ''); ?></td>
                                    <td style="text-transform:uppercase;font-weight:700;color:#4f46e5;"><?php echo htmlspecialchars($sessionData['subject'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($sessionData['start_time'] ?? '---'); ?></td>
                                    <td>
                                        <a href="?kick_user=<?php echo urlencode($username); ?>" class="btn-small" style="background:var(--danger);" onclick="return confirm('Bạn chắc chắn muốn hủy phiên làm bài của thí sinh này?');">Hủy phiên</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:24px;">Hiện tại không có học sinh nào đang trong phòng thi.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="boxchat" class="sub-tab-content">
                <div class="chat-box">
                    <div class="chat-box-header">
                        <h2>Boxchat lớp học</h2>
                        <p>Trao đổi nhanh giữa giáo viên và học sinh.</p>
                    </div>
                    <div class="chat-frame-wrap">
                        <iframe src="chat_box.php" class="chat-frame" title="Chat Box"></iframe>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-box">
            <div class="section-header">
                <h2>Quản lý đề thi</h2>
                <p>Up đề thi, sửa và xóa đề.</p>
            </div>

            <div class="tab-sub-control">
                <button class="tab-sub-btn active" onclick="openSubTab(event,'exam_upload')">Up đề thi</button>
                <button class="tab-sub-btn" onclick="openSubTab(event,'exam_add_manual')">Thêm câu hỏi</button>
            </div>

            <div id="exam_upload" class="sub-tab-content" style="display:block;">
                <h3>Nhập bộ đề thi từ file (.json)</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Chọn file JSON</label>
                        <input type="file" name="exam_file" accept=".json" required>
                    </div>
                    <button type="submit" name="upload_file" class="btn-submit" style="background:linear-gradient(135deg,var(--accent) 0%,#4f46e5 100%)">
                        Up đề thi &amp; chờ phê duyệt
                    </button>
                </form>
            </div>
            <div id="exam_add_manual" class="sub-tab-content">
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
                        <div class="form-group">
                            <label>Đáp án A</label>
                            <input type="text" name="A" required placeholder="Nội dung đáp án A">
                        </div>
                        <div class="form-group">
                            <label>Đáp án B</label>
                            <input type="text" name="B" required placeholder="Nội dung đáp án B">
                        </div>
                        <div class="form-group">
                            <label>Đáp án C</label>
                            <input type="text" name="C" required placeholder="Nội dung đáp án C">
                        </div>
                        <div class="form-group">
                            <label>Đáp án D</label>
                            <input type="text" name="D" required placeholder="Nội dung đáp án D">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Đáp án đúng</label>
                        <select name="correct" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <button type="submit" name="add_manual" class="btn-submit">Lưu câu hỏi</button>
                </form>
            </div>
        </section>

        <section class="section-box">
            <div class="section-header">
                <h2>Kho câu hỏi chính thức</h2>
                <p>Hiển thị toàn bộ câu hỏi và đáp án đã được duyệt</p>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã môn</th>
                        <th>Mã đề</th>
                        <th>Câu số</th>
                        <th>Câu hỏi</th>
                        <th>A</th>
                        <th>B</th>
                        <th>C</th>
                        <th>D</th>
                        <th>Đúng</th>
                        <th>Độ khó</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($questionsList) > 0): ?>
                        <?php foreach ($questionsList as $index => $q): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td style="font-weight:800;color:#3b82f6;"><?php echo htmlspecialchars($q['subjectId'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['examCode'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['displayNum'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['question'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['A'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['B'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['C'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['D'] ?? ''); ?></td>
                                <td style="font-weight:800;color:var(--success);"><?php echo htmlspecialchars($q['correct'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($q['difficulty'] ?? ''); ?></td>
                                <td>
                                    <a href="view_exam.php?subject=<?php echo urlencode($q['subjectId'] ?? ''); ?>" class="btn-small" style="background:var(--primary);">Sửa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" style="text-align:center;padding:24px;color:var(--text-muted);">
                                Kho dữ liệu câu hỏi trống.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<div class="main-footer">
    © 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online.
</div>

<script>
function openSubTab(evt, tabId) {
    const container = evt.currentTarget.closest('.section-box');
    const contents = container.getElementsByClassName('sub-tab-content');
    for (let i = 0; i < contents.length; i++) {
        contents[i].style.display = 'none';
    }
    const btns = container.getElementsByClassName('tab-sub-btn');
    for (let i = 0; i < btns.length; i++) {
        btns[i].classList.remove('active');
    }
    document.getElementById(tabId).style.display = 'block';
    evt.currentTarget.classList.add('active');
}
</script>
</body>
</html>