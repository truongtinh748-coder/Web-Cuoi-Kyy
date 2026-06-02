<?php
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}

// 🎯 BỔ SUNG KHÔI PHỤC BẢO MẬT: Nếu hệ thống XAMPP tự xóa Session do treo máy lâu, tự động khôi phục từ Cookie sao lưu
if (!isset($_SESSION['username']) && isset($_COOKIE['student_backup_login'])) {
    $backup = json_decode($_COOKIE['student_backup_login'], true);
    if (is_array($backup) && isset($backup['username']) && $backup['role'] === 'student') {
        $_SESSION['username'] = $backup['username'];
        $_SESSION['role'] = 'student';
        $_SESSION['fullname'] = $backup['fullname'];
        $_SESSION['email'] = $backup['email'];
        $_SESSION['user_logged'] = $backup['username'];
    }
}

// CHỐT CHẶN BẢO MẬT: Nếu chưa đăng nhập hoặc không phải sinh viên thì trả về trang đăng nhập
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); 
    exit();
}

$student_id = $_SESSION['username']; // Mã số sinh viên (Tài khoản)
$student_name = "";
$student_email = "";

// ĐỒNG BỘ LOGIC CŨ: Tự động gán thêm khóa user_logged để các file cũ (như exam.php) hiểu được
$_SESSION['user_logged'] = $student_id;

// 1. ƯU TIÊN LẤY DỮ LIỆU TRỰC TIẾP TỪ SESSION KHI ĐĂNG NHẬP THÀNH CÔNG
if (isset($_SESSION['fullname']) && !empty(trim($_SESSION['fullname']))) {
    $student_name = $_SESSION['fullname'];
} 
if (isset($_SESSION['email']) && !empty(trim($_SESSION['email']))) {
    $student_email = $_SESSION['email'];
}

// 2. NẾU MẤT DỮ LIỆU TẠM THỜI, QUÉT DANH SÁCH SYSTEM_USERS ĐỂ ĐỒNG BỘ LẠI TỪNG TRƯỜNG
if ((empty($student_name) || empty($student_email)) && isset($_SESSION['system_users']) && is_array($_SESSION['system_users'])) {
    foreach ($_SESSION['system_users'] as $user) {
        if ($user['user'] === $student_id) {
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

// 3. BIỆN PHÁP DỰ PHÒNG CUỐI CÙNG (Nếu không tìm thấy thông tin)
if (empty($student_name)) {
    $student_name = "Thí sinh " . $student_id;
}
if (empty($student_email)) {
    $student_email = "Chưa liên kết Gmail";
}

// 🎯 BỔ SUNG GHI NHỚ: Tạo/Cập nhật Cookie dự phòng thời hạn 7 ngày sau khi đã xác định đủ thông tin sĩ tử
$cookie_data = [
    'username' => $student_id,
    'role' => 'student',
    'fullname' => $student_name,
    'email' => $student_email
];
setcookie('student_backup_login', json_encode($cookie_data, JSON_UNESCAPED_UNICODE), time() + 604800, "/");

// 🎯 BỔ SUNG SELF-PING: Xử lý phản hồi lệnh duy trì kết nối trực tiếp vào file này (Không cần file ping.php riêng lẻ)
if (isset($_GET['action']) && $_GET['action'] === 'ping') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}

// DANH SÁCH 6 MÔN THI ĐỒNG BỘ MÃ CHUẨN ĐỂ ĐỌC FILE JSON
$subjects = [
    [
        "id" => "toan",
        "name" => "Toán Học Đại Cương",
        "code" => "TOAN",
        "duration" => "15 phút",
        "total_questions" => "24 câu",
        "icon" => "📐",
        "bg_color" => "#eff6ff",
        "border_color" => "#3b82f6"
    ],
    [
        "id" => "php",
        "name" => "Lập Trình Web",
        "code" => "LTWEB",
        "duration" => "15 phút",
        "total_questions" => "24 câu",
        "icon" => "🐘",
        "bg_color" => "#fef2f2",
        "border_color" => "#ef4444"
    ],
    [
        "id" => "ta",
        "name" => "Tiếng Anh Chuyên Ngành",
        "code" => "TIENGANH",
        "duration" => "15 phút",
        "total_questions" => "24 câu",
        "icon" => "📑",
        "bg_color" => "#f0fdf4",
        "border_color" => "#10b981"
    ],
    [
        "id" => "csdl",
        "name" => "Cơ Sở Dữ Liệu",
        "code" => "CSDL",
        "duration" => "15 phút",
        "total_questions" => "24 câu",
        "icon" => "🗄️",
        "bg_color" => "#faf5ff",
        "border_color" => "#a855f7"
    ],
    [
        "id" => "mmt",
        "name" => "Mạng Máy Tính",
        "code" => "MMT",
        "duration" => "15 phút",
        "total_questions" => "24 câu",
        "icon" => "🌐",
        "bg_color" => "#ecfeff",
        "border_color" => "#06b6d4"
    ],
    [
        "id" => "xstk",
        "name" => "Xác Suất Thống Kê",
        "code" => "XSTK",
        "duration" => "15 phút",
        "total_questions" => "24 câu",
        "icon" => "📊",
        "bg_color" => "#fffbeb",
        "border_color" => "#f59e0b"
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Thi Trắc Nghiệm - Sinh Viên</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; }
        body { 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%); 
            color: #334155; min-height: 100vh; display: flex; flex-direction: column;
        }
        
        /* HEADER NAVBAR */
        .navbar { 
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            padding: 16px 50px; display: flex; justify-content: space-between; align-items: center; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); border-bottom: 1px solid #e2e8f0;
            position: sticky; top: 0; z-index: 10;
        }
        .logo-area { display: flex; align-items: center; gap: 10px; }
        .logo-text { color: #0ea5e9; font-size: 20px; font-weight: 800; text-decoration: none; }
        .btn-logout { 
            color: #ffffff; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
            text-decoration: none; font-weight: 700; font-size: 13px; text-transform: uppercase; 
            padding: 10px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); 
        }

        /* MAIN CONTAINER */
        .main-container { 
            max-width: 1200px; width: 100%; margin: 40px auto; padding: 0 20px; 
            display: grid; grid-template-columns: 320px 1fr; gap: 30px; flex: 1;
        }

        /* THÔNG TIN HỒ SƠ THÍ SINH (SIDEBAR TRAI) */
        .profile-card { 
            background: #ffffff; padding: 30px 24px; border-radius: 20px; 
            border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            height: fit-content; text-align: center;
        }
        .avatar-box { 
            width: 80px; height: 80px; background: #e0f2fe; color: #0284c7; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            font-size: 32px; font-weight: 700; margin: 0 auto 15px; border: 3px solid #bae6fd;
        }
        .student-name { font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
        .student-role { display: inline-block; background: #e0f2fe; color: #0284c7; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; margin-bottom: 25px; text-transform: uppercase; }
        
        .info-list { text-align: left; border-top: 1px dashed #e2e8f0; padding-top: 20px; }
        .info-item { margin-bottom: 15px; }
        .info-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 14px; font-weight: 600; color: #334155; word-break: break-all; }

        /* NỘI DUNG CHÍNH (BÊN PHẢI) */
        .dashboard-content { display: flex; flex-direction: column; gap: 25px; }
        .welcome-banner { 
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%); color: #ffffff; 
            padding: 30px; border-radius: 20px;
        }
        .welcome-banner h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
        .welcome-banner p { font-size: 14px; opacity: 0.9; }

        .section-title { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 10px;}
        
        .subject-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
        .subject-card { 
            background: #ffffff; border-radius: 18px; padding: 25px; border: 1px solid #e2e8f0;
            display: flex; flex-direction: column; justify-content: space-between; transition: all 0.3s;
        }
        .subject-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08); }
        .subject-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .subject-icon { font-size: 30px; padding: 10px; border-radius: 12px; background: #f1f5f9; }
        .subject-code { font-size: 12px; font-weight: 700; color: #94a3b8; }
        .subject-body h3 { font-size: 16px; font-weight: 800; color: #1e293b; margin-bottom: 15px; }
        
        .subject-meta { display: flex; flex-direction: column; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-bottom: 20px; }
        .meta-item { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; }

        .btn-enter-exam { 
            background: #f1f5f9; color: #1e293b; border: none; width: 100%; text-align: center;
            padding: 12px; border-radius: 10px; font-size: 14px; font-weight: 700; text-decoration: none;
        }
        .subject-card:hover .btn-enter-exam {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="logo-area">
            <span style="font-size: 24px;">🎓</span>
            <a href="#" class="logo-text">Hệ Thống Khảo Thí Trực Tuyến</a>
        </div>
        <a href="logout.php" class="btn-logout" onclick="document.cookie = 'student_backup_login=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';">Đăng Xuất</a>
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
                    <div class="info-value" style="color: #0ea5e9; font-family: monospace; font-size: 16px;"><?php echo htmlspecialchars($student_id); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Địa chỉ Gmail liên kết</div>
                    <div class="info-value">
                        <?php 
                        if($student_email === "Chưa liên kết Gmail") {
                            echo '<span style="color: #ef4444; font-weight: 700;">Chưa liên kết Gmail</span>';
                        } else {
                            echo htmlspecialchars($student_email);
                        }
                        ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Quyền truy cập</div>
                    <div class="info-value" style="color: #10b981;">Student Account</div>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="welcome-banner">
                <h1>Chào mừng trở lại, <?php echo htmlspecialchars($student_name); ?>!</h1>
                <p>Vui lòng nghiêm túc chấp hành nội quy phòng thi. Đảm bảo kết nối internet ổn định và không thoát trình duyệt hoặc chuyển tab trong suốt quá trình làm bài thi trực tuyến.</p>
            </div>

            <div class="section-title">📝 Danh sách các môn thi đang mở kết nối</div>

            <div class="subject-grid">
                <?php foreach ($subjects as $sub): ?>
                    <div class="subject-card" style="border-top: 4px solid <?php echo $sub['border_color']; ?>;">
                        <div>
                            <div class="subject-header">
                                <div class="subject-icon"><?php echo $sub['icon']; ?></div>
                                <span class="subject-code"><?php echo $sub['code']; ?></span>
                            </div>
                            <div class="subject-body">
                                <h3><?php echo $sub['name']; ?></h3>
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

    <script>
        setInterval(function() {
            // Ping trực tiếp vào chính trang này kèm tham số ẩn để kích hoạt PHP gia hạn session liên tục khi tab mở
            fetch('student_dashboard.php?action=ping')
            .then(response => response.json())
            .then(data => {
                console.log("Hệ thống duy trì kết nối thành công.");
            })
            .catch(error => {
                console.error("Tab đang bị trình duyệt đóng băng hoặc mất kết nối tạm thời.");
            });
        }, 60000); // Tần suất 1 phút/lần
    </script>
</body>
</html>