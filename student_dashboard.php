<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $_SESSION['last_activity'] = time();
}

if (!isset($_SESSION['username']) && isset($_COOKIE['student_backup_login'])) {
    $backup = json_decode($_COOKIE['student_backup_login'], true);
    if (is_array($backup) && isset($backup['username']) && ($backup['role'] ?? '') === 'student') {
        $_SESSION['username'] = $backup['username'];
        $_SESSION['role'] = 'student';
        $_SESSION['fullname'] = $backup['fullname'] ?? '';
        $_SESSION['email'] = $backup['email'] ?? '';
        $_SESSION['user_logged'] = $backup['username'];
    }
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    // Session quá hạn nhưng không xóa ngay
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['username'];
$student_name = $_SESSION['fullname'] ?? '';
$student_email = $_SESSION['email'] ?? '';

$_SESSION['user_logged'] = $student_id;

if ((empty($student_name) || empty($student_email)) && isset($_SESSION['system_users']) && is_array($_SESSION['system_users'])) {
    foreach ($_SESSION['system_users'] as $user) {
        if (($user['user'] ?? '') === $student_id) {
            if (empty($student_name) && !empty($user['fullname'])) {
                $student_name = $user['fullname'];
                $_SESSION['fullname'] = $user['fullname'];
            }
            if (empty($student_email) && !empty($user['email'])) {
                $student_email = $user['email'];
                $_SESSION['email'] = $user['email'];
            }
            break;
        }
    }
}

if (empty($student_name)) {
    $student_name = 'Thí sinh ' . $student_id;
}
if (empty($student_email)) {
    $student_email = 'Chưa liên kết Gmail';
}

$cookie_data = [
    'username' => $student_id,
    'role' => 'student',
    'fullname' => $student_name,
    'email' => $student_email
];
setcookie('student_backup_login', json_encode($cookie_data, JSON_UNESCAPED_UNICODE), time() + 604800, '/');

$subjects = [
    [
        'id' => 'toan',
        'name' => 'Toán Học Đại Cương',
        'code' => 'TOAN',
        'duration' => '15 phút',
        'total_questions' => '25 câu',
        'icon' => '📐',
        'bg_color' => '#eff6ff',
        'border_color' => '#3b82f6'
    ],
    [
        'id' => 'php',
        'name' => 'Lập Trình Web',
        'code' => 'LTWEB',
        'duration' => '15 phút',
        'total_questions' => '25 câu',
        'icon' => '🐘',
        'bg_color' => '#fef2f2',
        'border_color' => '#ef4444'
    ],
    [
        'id' => 'ta',
        'name' => 'Tiếng Anh Chuyên Ngành',
        'code' => 'TIENGANH',
        'duration' => '15 phút',
        'total_questions' => '25 câu',
        'icon' => '📑',
        'bg_color' => '#f0fdf4',
        'border_color' => '#10b981'
    ],
    [
        'id' => 'csdl',
        'name' => 'Cơ Sở Dữ Liệu',
        'code' => 'CSDL',
        'duration' => '15 phút',
        'total_questions' => '25 câu',
        'icon' => '🗄️',
        'bg_color' => '#faf5ff',
        'border_color' => '#a855f7'
    ],
    [
        'id' => 'mmt',
        'name' => 'Mạng Máy Tính',
        'code' => 'MMT',
        'duration' => '15 phút',
        'total_questions' => '25 câu',
        'icon' => '🌐',
        'bg_color' => '#ecfeff',
        'border_color' => '#06b6d4'
    ],
    [
        'id' => 'xstk',
        'name' => 'Xác Suất Thống Kê',
        'code' => 'XSTK',
        'duration' => '15 phút',
        'total_questions' => '25 câu',
        'icon' => '📊',
        'bg_color' => '#fffbeb',
        'border_color' => '#f59e0b'
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Thi Trắc Nghiệm - Sinh Viên</title>
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

        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
            font-family:'Inter',system-ui,-apple-system,sans-serif;
        }

        body{
            background:var(--bg);
            color:var(--text-main);
            min-height:100vh;
            display:flex;
            flex-direction:column;
        }

        .navbar{
            background:rgba(255,255,255,0.85);
            backdrop-filter:saturate(180%) blur(12px);
            padding:14px 40px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            box-shadow:var(--shadow-sm);
            border-bottom:1px solid var(--border);
            position:sticky;
            top:0;
            z-index:100;
        }

        .logo-area{
            display:flex;
            align-items:center;
            gap:12px;
        }

        .logo-text{
            color:var(--primary);
            font-size:20px;
            font-weight:800;
            text-decoration:none;
        }

        .btn-logout{
            color:#fff;
            background:linear-gradient(135deg,var(--danger) 0%,#dc2626 100%);
            text-decoration:none;
            font-weight:700;
            font-size:13px;
            text-transform:uppercase;
            padding:10px 20px;
            border-radius:10px;
            box-shadow:0 4px 12px rgba(220,38,38,0.25);
            transition:transform .2s ease,box-shadow .2s ease;
        }

        .btn-logout:hover{
            transform:translateY(-2px);
            box-shadow:0 6px 16px rgba(220,38,38,0.35);
        }

        .main-container{
            max-width:1200px;
            width:100%;
            margin:32px auto;
            padding:0 24px;
            display:grid;
            grid-template-columns:320px 1fr;
            gap:28px;
            flex:1;
        }

        .profile-card{
            background:var(--surface);
            padding:28px 24px;
            border-radius:16px;
            border:1px solid var(--border);
            box-shadow:var(--shadow-md);
            height:fit-content;
            text-align:center;
        }

        .avatar-box{
            width:80px;
            height:80px;
            background:linear-gradient(135deg,#e0f2fe,#bae6fd);
            color:var(--primary-dark);
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:32px;
            font-weight:800;
            margin:0 auto 16px;
            border:3px solid #bae6fd;
        }

        .student-name{
            font-size:22px;
            font-weight:800;
            color:var(--text-main);
            margin-bottom:6px;
        }

        .student-role{
            display:inline-block;
            background:linear-gradient(135deg,#e0f2fe,#bae6fd);
            color:var(--primary-dark);
            font-size:11px;
            font-weight:700;
            padding:5px 14px;
            border-radius:20px;
            margin-bottom:24px;
            text-transform:uppercase;
            letter-spacing:0.6px;
        }

        .info-list{
            text-align:left;
            border-top:1px dashed var(--border);
            padding-top:18px;
        }

        .info-item{
            margin-bottom:14px;
        }

        .info-label{
            font-size:11px;
            font-weight:700;
            color:var(--text-muted);
            text-transform:uppercase;
            margin-bottom:4px;
        }

        .info-value{
            font-size:14px;
            font-weight:600;
            color:var(--text-sub);
            word-break:break-all;
        }

        .dashboard-content{
            display:flex;
            flex-direction:column;
            gap:24px;
        }

        .welcome-banner{
            background:linear-gradient(135deg,#1e3a8a 0%,var(--primary) 100%);
            color:#fff;
            padding:28px;
            border-radius:16px;
            box-shadow:var(--shadow-md);
        }

        .welcome-banner h1{
            font-size:22px;
            font-weight:800;
            margin-bottom:8px;
        }

        .welcome-banner p{
            font-size:14px;
            opacity:0.95;
            line-height:1.6;
        }

        .section-title{
            font-size:17px;
            font-weight:800;
            color:var(--text-main);
            margin-bottom:14px;
            display:flex;
            align-items:center;
            gap:8px;
        }

        .section-title::before{
            content:"";
            width:4px;
            height:18px;
            border-radius:3px;
            background:linear-gradient(180deg,var(--primary) 0%,var(--accent) 100%);
        }

        .subject-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
            gap:20px;
        }

        .subject-card{
            background:var(--surface);
            border-radius:16px;
            padding:24px;
            border:1px solid var(--border);
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            transition:all .25s ease;
            box-shadow:var(--shadow-sm);
        }

        .subject-card:hover{
            transform:translateY(-4px);
            box-shadow:0 20px 30px rgba(15,23,42,0.08);
        }

        .subject-header{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            margin-bottom:14px;
        }

        .subject-icon{
            font-size:30px;
            padding:10px;
            border-radius:12px;
            background:linear-gradient(135deg,#f1f5f9,#e2e8f0);
        }

        .subject-code{
            font-size:11px;
            font-weight:700;
            color:var(--text-muted);
        }

        .subject-body h3{
            font-size:15px;
            font-weight:800;
            color:var(--text-main);
            margin-bottom:14px;
        }

        .subject-meta{
            display:flex;
            flex-direction:column;
            gap:8px;
            border-top:1px solid var(--border-light);
            padding-top:14px;
            margin-bottom:18px;
        }

        .meta-item{
            display:flex;
            align-items:center;
            gap:6px;
            font-size:13px;
            color:var(--text-muted);
        }

        .btn-enter-exam{
            background:linear-gradient(135deg,#f1f5f9,#e2e8f0);
            color:var(--text-main);
            border:none;
            width:100%;
            text-align:center;
            padding:12px;
            border-radius:12px;
            font-size:14px;
            font-weight:700;
            text-decoration:none;
            transition:all .25s ease;
        }

        .subject-card:hover .btn-enter-exam{
            background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
            color:#fff;
        }

        .main-footer{
            text-align:center;
            padding:28px;
            color:var(--text-muted);
            font-size:13px;
            font-weight:500;
            margin-top:auto;
        }

        .chat-toggle{
            position:fixed;
            right:22px;
            bottom:22px;
            z-index:1000;
            border:none;
            border-radius:999px;
            background:linear-gradient(135deg,var(--primary) 0%,var(--accent) 100%);
            color:#fff;
            width:58px;
            height:58px;
            box-shadow:0 14px 30px rgba(14,165,233,.28);
            cursor:pointer;
            font-size:24px;
        }

        .chat-box{
            position:fixed;
            right:22px;
            bottom:92px;
            width:min(380px,calc(100vw - 24px));
            height:560px;
            background:#fff;
            border:1px solid var(--border);
            border-radius:18px;
            box-shadow:0 20px 50px rgba(15,23,42,.18);
            overflow:hidden;
            z-index:1000;
            display:none;
            flex-direction:column;
        }

        .chat-box.open{
            display:flex;
        }

        .chat-head-mini{
            background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#0ea5e9 100%);
            color:#fff;
            padding:12px 14px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
        }

        .chat-head-mini strong{
            font-size:14px;
        }

        .chat-close{
            border:none;
            background:rgba(255,255,255,.14);
            color:#fff;
            width:28px;
            height:28px;
            border-radius:8px;
            cursor:pointer;
            font-size:18px;
            line-height:1;
        }

        .chat-frame{
            border:none;
            width:100%;
            height:100%;
            flex:1;
            background:#fff;
        }

        @media (max-width: 900px){
            .navbar{
                padding:14px 20px;
            }

            .main-container{
                grid-template-columns:1fr;
                padding:0 16px;
            }

            .subject-grid{
                grid-template-columns:1fr;
            }

            .chat-box{
                width:min(92vw,380px);
                height:520px;
            }
        }
        .chat-toggle:hover{
    transform:translateY(-6px);
    animation:bounceChat .45s ease;
}

@keyframes bounceChat{
    0%{ transform:translateY(0); }
    40%{ transform:translateY(-7px); }
    70%{ transform:translateY(-4px); }
    100%{ transform:translateY(-6px); }
}
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo-area">
        <span style="font-size:24px">🎓</span>
        <a href="student_dashboard.php" class="logo-text">Hệ Thống Khảo Thí Trực Tuyến</a>
    </div>
    <a href="actions/logout.php" class="btn-logout" onclick="document.cookie='student_backup_login=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';">Đăng Xuất</a>
</div>

<div class="main-container">
    <div class="profile-card">
        <div class="avatar-box">
            <?php
            $first_char = mb_substr(trim($student_name), 0, 1, 'UTF-8');
            echo htmlspecialchars(mb_strtoupper($first_char, 'UTF-8'));
            ?>
        </div>

        <div class="student-name"><?php echo htmlspecialchars($student_name); ?></div>
        <div class="student-role">THÍ SINH</div>

        <div class="info-list">
            <div class="info-item">
                <div class="info-label">Tên đăng nhập / Tài khoản</div>
                <div class="info-value" style="color:var(--primary);font-family:monospace;font-size:15px">
                    <?php echo htmlspecialchars($student_id); ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Địa chỉ Gmail liên kết</div>
                <div class="info-value">
                    <?php
                    if ($student_email === 'Chưa liên kết Gmail') {
                        echo '<span style="color:var(--danger);font-weight:700">Chưa liên kết Gmail</span>';
                    } else {
                        echo htmlspecialchars($student_email);
                    }
                    ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Quyền truy cập</div>
                <div class="info-value" style="color:var(--success)">Student Account</div>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="welcome-banner">
            <h1>Chào mừng trở lại, <?php echo htmlspecialchars($student_name); ?>!</h1>
            <p>Vui lòng nghiêm túc chấp hành nội quy phòng thi. Đảm bảo kết nối internet ổn định và không thoát trình duyệt hoặc chuyển tab trong suốt quá trình làm bài thi trực tuyến.</p>
        </div>

        <div class="section-title">📝 Danh sách các môn thi đang mở</div>

        <div class="subject-grid">
            <?php foreach ($subjects as $sub): ?>
                <div class="subject-card" style="border-top:4px solid <?php echo $sub['border_color']; ?>">
                    <div>
                        <div class="subject-header">
                            <div class="subject-icon"><?php echo $sub['icon']; ?></div>
                            <span class="subject-code"><?php echo $sub['code']; ?></span>
                        </div>
                        <div class="subject-body">
                            <h3><?php echo htmlspecialchars($sub['name']); ?></h3>
                        </div>
                        <div class="subject-meta">
                            <div class="meta-item">⏱️ Thời gian: <strong><?php echo $sub['duration']; ?></strong></div>
                            <div class="meta-item">📊 Quy mô: <strong><?php echo $sub['total_questions']; ?> trắc nghiệm</strong></div>
                        </div>
                    </div>
                    <a href="exam.php?subject=<?php echo urlencode($sub['id']); ?>" class="btn-enter-exam">
                        Vào Phòng Thi
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="main-footer">
    © 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online.
</div>

<button class="chat-toggle" id="chatToggle">💬</button>

<div class="chat-box" id="chatBox">
    <div class="chat-head-mini">
        <strong>Chat hỗ trợ</strong>
        <button class="chat-close" id="chatClose">×</button>
    </div>
    <iframe class="chat-frame" src="chat_box.php" title="Chat Box"></iframe>
</div>

<script>
const chatToggle = document.getElementById('chatToggle');
const chatBox = document.getElementById('chatBox');
const chatClose = document.getElementById('chatClose');

chatToggle.addEventListener('click', () => {
    chatBox.classList.toggle('open');
});

chatClose.addEventListener('click', () => {
    chatBox.classList.remove('open');
});
</script>
</body>
</html>