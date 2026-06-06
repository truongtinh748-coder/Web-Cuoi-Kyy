<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// =========================================================================
// 🗄️ CẬP NHẬT: CHUYỂN ĐỔI DATABASE TỪ SESSION SANG FILE JSON DÙNG CHUNG LAN
// =========================================================================
$usersFile = 'data/users.json';


// Tự động tạo thư mục data nếu chưa tồn tại
if (!file_exists(dirname($usersFile))) {
    mkdir(dirname($usersFile), 0777, true);
}


// Đọc dữ liệu tài khoản từ file JSON lên hệ thống
if (file_exists($usersFile)) {
    $system_users = json_decode(file_get_contents($usersFile), true);
}


// KHẮC PHỤC: Khởi tạo danh sách mảng chuẩn và cấu hình tài khoản cố định nếu file trống
if (!isset($system_users) || !is_array($system_users) || empty($system_users)) {
    $system_users = [
        [
            'user' => '1123456789', // MSSV mẫu
            'pass' => '123456',
            'role' => 'student',
            'email' => 'sv_mau@gmail.com',
            'fullname' => 'Nguyễn Học Sinh'
        ]
    ];
    file_put_contents($usersFile, json_encode($system_users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}


// LOGIC THÊM ADMIN CỐ ĐỊNH: Đảm bảo tài khoản admin luôn tồn tại và không bị trùng lặp
$admin_exists = false;
foreach ($system_users as $u) {
    if ($u['user'] === 'admin') {
        $admin_exists = true;
        break;
    }
}


if (!$admin_exists) {
    $system_users[] = [
        'user' => 'admin',                 // 🔑 Tài khoản Admin cố định
        'pass' => 'admin123',              // 🔒 Mật khẩu Admin cố định
        'role' => 'admin',                // 🎯 Quyền hạn hệ thống
        'email' => 'admin@gmail.com',     // 📩 Email liên kết mặc định
        'fullname' => 'Quản Trị Viên Hệ Thống' // 👤 Họ tên hiển thị
    ];
    file_put_contents($usersFile, json_encode($system_users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}


// Đồng bộ ngược lại Session để các file cũ xử lý bổ trợ không bị lỗi kết nối
$_SESSION['system_users'] = $system_users;
// =========================================================================


$auth_error = isset($_SESSION['auth_error']) ? $_SESSION['auth_error'] : "";
$reg_error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : "";
$reg_success = isset($_SESSION['register_success']) ? $_SESSION['register_success'] : "";


// XỬ LÝ LOGIC ĐĂNG NHẬP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'login') {
    $username = isset($_POST['username']) ? (string)trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)trim($_POST['password']) : '';


    $found = false;
    $user_role = '';
    $user_fullname = ''; 
    $user_email = ''; // Thêm biến tạm lưu email từ session


    // Duyệt mảng từ cơ sở dữ liệu file JSON mới cập nhật
    foreach ($system_users as $u) {
        if ((string)$u['user'] === $username && (string)$u['pass'] === $password) {
            $found = true;
            $user_role = $u['role']; 
            $user_fullname = isset($u['fullname']) ? $u['fullname'] : $username;
            $user_email = isset($u['email']) ? $u['email'] : ''; // Lấy email ra
            break;
        }
    }


    if ($found) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user_role; 
        $_SESSION['fullname'] = $user_fullname;
        $_SESSION['email'] = $user_email;

        // THÊM MỚI: Tạo/cập nhật active_sessions.json cho học sinh để admin dashboard đọc được
        if ($user_role === 'student') {
            $activeSessionsFile = 'data/active_sessions.json';
            if (!file_exists('data')) {
                mkdir('data', 0777, true);
            }

            $activeSessions = [];
            if (file_exists($activeSessionsFile)) {
                $activeSessions = json_decode(file_get_contents($activeSessionsFile), true);
                if (!is_array($activeSessions)) {
                    $activeSessions = [];
                }
            }

            $activeSessions[$username] = [
                'fullname' => $user_fullname,
                'subject' => '',
                'start_time' => '',
                'session_id' => session_id(),
                'last_update' => time()
            ];

            file_put_contents($activeSessionsFile, json_encode($activeSessions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        }
        
        // ĐIỀU HƯỚNG CHÍNH XÁC THEO VAI TRÒ:
        if ($user_role === 'student') {
            header("Location: student_dashboard.php");
            exit();
        } elseif ($user_role === 'admin') {
            header("Location: admin_dashboard.php"); // Admin vào bảng điều khiển tối cao
            exit();
        } elseif ($user_role === 'teacher') {
            header("Location: teacher_dashboard.php"); // ✅ CẬP NHẬT: Giáo viên rẽ nhánh sang dashboard riêng biệt
            exit();
        }
        $auth_error = "";
    } else {
        $auth_error = "Tài khoản hoặc mật khẩu không chính xác! Vui lòng kiểm tra lại.";
        $reg_success = "";
    }
}


// =========================================================================
// KHU VỰC THÊM MỚI: XỬ LÝ LOGIC XÁC THỰC OTP & ĐẶT LẠI MẬT KHẨU (QUÊN MẬT KHẨU)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'forgot_password') {
    $f_username = isset($_POST['forgot_username']) ? (string)trim($_POST['forgot_username']) : '';
    $f_email = isset($_POST['forgot_email']) ? (string)trim($_POST['forgot_email']) : '';
    $f_otp = isset($_POST['forgot_otp']) ? (string)trim($_POST['forgot_otp']) : '';
    $f_password = isset($_POST['forgot_password']) ? (string)trim($_POST['forgot_password']) : '';


    // 1. Kiểm tra tính hợp lệ của OTP lưu trữ trong Session sinh ra bởi AJAX
    if (!isset($_SESSION['forgot_otp']) || $_SESSION['forgot_otp'] !== $f_otp || $_SESSION['forgot_email'] !== $f_email) {
        $auth_error = "Mã OTP không đúng hoặc đã hết hạn phục hồi!";
        $active_tab = "forgot";
    } else {
        // 2. Tìm kiếm cặp Tài khoản + Email trong cơ sở dữ liệu JSON
        $user_index = -1;
        foreach ($system_users as $index => $u) {
            if ((string)$u['user'] === $f_username && isset($u['email']) && $u['email'] === $f_email) {
                $user_index = $index;
                break;
            }
        }


        if ($user_index !== -1) {
            // 3. Tiến hành cập nhật mật khẩu mới vào mảng dữ liệu
            $system_users[$user_index]['pass'] = $f_password;
            
            // Lưu dữ liệu cập nhật xuống file JSON dữ liệu dùng chung
            file_put_contents($usersFile, json_encode($system_users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $_SESSION['system_users'] = $system_users; // Đồng bộ tức thì lên Session hệ thống
            
            // Xóa bỏ token OTP cũ để bảo mật ngăn chặn tái sử dụng
            unset($_SESSION['forgot_otp'], $_SESSION['forgot_email']);
            
            $reg_success = "Đặt lại mật khẩu thành công! Hãy đăng nhập bằng mật khẩu mới.";
            $active_tab = "login";
        } else {
            $auth_error = "Tài khoản và Email liên kết không khớp dữ liệu hệ thống!";
            $active_tab = "forgot";
        }
    }
}


unset($_SESSION['auth_error'], $_SESSION['register_error'], $_SESSION['register_success']);


// Thiết lập Tab hiển thị mặc định dựa trên kết quả phản hồi logic điều hướng
if (!isset($active_tab)) {
    $active_tab = "login";
    if ($reg_error != "") {
        $active_tab = "register";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Hệ Thống - Đăng Nhập & Đăng Ký</title>
 <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    }

    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        color: #334155;
        background:
            radial-gradient(circle at top left, rgba(56, 189, 248, 0.18), transparent 28%),
            radial-gradient(circle at bottom right, rgba(168, 85, 247, 0.16), transparent 26%),
            linear-gradient(135deg, #f8fbff 0%, #eef4ff 45%, #f7f7ff 100%);
    }

    .navbar {
        background: rgba(15, 23, 42, 0.92);
        backdrop-filter: blur(12px);
        padding: 16px 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px rgba(2, 6, 23, 0.22);
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .logo {
        color: #38bdf8;
        font-size: 20px;
        font-weight: 800;
        text-decoration: none;
        letter-spacing: -0.3px;
        transition: transform .2s ease, opacity .2s ease;
    }

    .logo:hover {
        transform: translateY(-1px);
        opacity: 0.95;
    }

    .nav-links a {
        color: #e2e8f0;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 999px;
        transition: all 0.2s ease;
    }

    .nav-links a:hover {
        color: #0f172a;
        background: linear-gradient(135deg, #7dd3fc, #c4b5fd);
        box-shadow: 0 8px 18px rgba(56, 189, 248, 0.22);
    }

    .auth-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 50px 20px;
    }

    .login-card {
        width: 100%;
        max-width: 460px;
        padding: 44px;
        border-radius: 26px;
        text-align: center;
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(18px);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
    }

    .tab-control {
        display: flex;
        gap: 6px;
        background: rgba(241, 245, 249, 0.9);
        padding: 6px;
        border-radius: 16px;
        margin-bottom: 28px;
        border: 1px solid rgba(226, 232, 240, 0.9);
    }

    .tab-btn {
        flex: 1;
        border: none;
        background: transparent;
        padding: 12px;
        font-size: 14px;
        font-weight: 800;
        color: #64748b;
        cursor: pointer;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        transition: all 0.25s ease;
    }

    .tab-btn:hover {
        color: #0ea5e9;
        background: rgba(255,255,255,0.65);
    }

    .tab-btn.active {
        background: linear-gradient(135deg, #ffffff, #eff6ff);
        color: #0284c7;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .auth-form {
        display: none;
        animation: fadeIn 0.35s ease forwards;
    }

    .auth-form.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-group {
        margin-bottom: 18px;
        text-align: left;
    }

    .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
        margin-bottom: 8px;
        letter-spacing: 0.6px;
    }

    .form-control {
        width: 100%;
        padding: 14px 16px;
        background: rgba(248, 250, 252, 0.95);
        border: 1.5px solid #dbe4f0;
        border-radius: 14px;
        font-size: 15px;
        outline: none;
        transition: all 0.22s ease;
        color: #0f172a;
    }

    .form-control:hover {
        border-color: #bfdbfe;
        background: #ffffff;
    }

    .form-control:focus {
        border-color: #0ea5e9;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.14);
    }

    .email-group {
        display: flex;
        gap: 10px;
    }

    .btn-otp {
        background: linear-gradient(135deg, #ffffff, #eff6ff);
        color: #0ea5e9;
        border: 1.5px solid #7dd3fc;
        padding: 0 18px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.22s ease;
        white-space: nowrap;
    }

    .btn-otp:hover {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #e0f2fe, #dbeafe);
        box-shadow: 0 10px 18px rgba(14, 165, 233, 0.12);
    }

    .btn-otp:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #cbd5e1;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .btn-submit {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 55%, #7c3aed 100%);
        background-size: 200% 100%;
        color: #ffffff;
        border: none;
        width: 100%;
        padding: 16px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        text-transform: uppercase;
        margin-top: 14px;
        letter-spacing: 1px;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
        transition: transform 0.22s ease, box-shadow 0.22s ease, background-position 0.35s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 34px rgba(37, 99, 235, 0.32);
        background-position: 100% 0;
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    .error-msg {
        color: #b91c1c;
        background: #fef2f2;
        border-left: 4px solid #ef4444;
        padding: 14px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 18px;
        text-align: left;
    }

    .success-msg {
        color: #15803d;
        background: #f0fdf4;
        border-left: 4px solid #22c55e;
        padding: 14px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 18px;
        text-align: left;
    }

    #toast-container {
        position: fixed;
        top: 25px;
        right: 25px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 12px;
        pointer-events: none;
    }

    .custom-toast {
        pointer-events: auto;
        background: #ffffff;
        color: #334155;
        padding: 16px 22px;
        border-radius: 14px;
        min-width: 320px;
        max-width: 450px;
        box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12);
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        font-size: 14px;
        transform: translateX(130%);
        border-left: 5px solid #3b82f6;
        transition: transform 0.38s cubic-bezier(0.68, -0.55, 0.265, 1.55), opacity 0.3s;
    }

    .custom-toast.show {
        transform: translateX(0);
    }

    .custom-toast.success {
        border-left-color: #10b981;
        background: #f0fdf4;
        color: #15803d;
    }

    .custom-toast.error {
        border-left-color: #ef4444;
        background: #fef2f2;
        color: #b91c1c;
    }

    .custom-toast.info {
        border-left-color: #0ea5e9;
        background: #f0f9ff;
        color: #0369a1;
    }

    .main-footer {
        text-align: center;
        padding: 20px;
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }

    @media (max-width: 640px) {
        .navbar {
            padding: 14px 16px;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .auth-wrapper {
            padding: 28px 14px;
        }

        .login-card {
            padding: 26px 18px;
            border-radius: 22px;
        }

        .email-group {
            flex-direction: column;
        }

        .btn-otp {
            height: 48px;
        }

        .custom-toast {
            min-width: 0;
            width: calc(100vw - 50px);
        }
    }
</style>
</head>
<body>


    <div id="toast-container"></div>


    <div class="navbar">
        <a href="index.php" class="logo">Cổng Quản Lý Giáo Dục</a>
        <div class="nav-links"><a href="index.php">Trang Chủ</a></div>
    </div>


    <div class="auth-wrapper">
        <div class="login-card">
            
            <div class="tab-control" id="authTabHeader">
                <button class="tab-btn <?php echo $active_tab == 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">Đăng Nhập</button>
                <button class="tab-btn <?php echo $active_tab == 'register' ? 'active' : ''; ?>" onclick="switchTab('register')">Đăng Ký</button>
            </div>


            <div id="loginForm" class="auth-form <?php echo $active_tab == 'login' ? 'active' : ''; ?>">
                <?php if ($auth_error != "" && $active_tab == 'login'): ?><div class="error-msg"><?php echo htmlspecialchars($auth_error); ?></div><?php endif; ?>
                <?php if ($reg_success != ""): ?><div class="success-msg"><?php echo htmlspecialchars($reg_success); ?></div><?php endif; ?>
                
                <form action="" method="POST" autocomplete="off">
                    <input type="hidden" name="action_type" value="login">
                    
                    <div class="form-group">
                        <label>Tài khoản hoặc MSSV</label>
                        <input type="text" name="username" class="form-control" placeholder="Nhập tài khoản hoặc MSSV..." required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••" required>
                    </div>


                    <div style="text-align: right; margin-top: -12px; margin-bottom: 20px;">
                        <a href="javascript:void(0)" onclick="switchTab('forgot')" style="color: #0ea5e9; font-size: 13px; font-weight: 600; text-decoration: none;">Quên mật khẩu?</a>
                    </div>


                    <button type="submit" class="btn-submit">Đăng Nhập Hệ Thống</button>
                </form>
            </div>


            <div id="registerForm" class="auth-form <?php echo $active_tab == 'register' ? 'active' : ''; ?>">
                <?php if ($reg_error != ""): ?><div class="error-msg"><?php echo htmlspecialchars($reg_error); ?></div><?php endif; ?>


                <form action="actions/register_action.php" method="POST" autocomplete="off">
                    
                    <div class="form-group">
                        <label>Họ và Tên</label>
                        <input type="text" name="new_fullname" class="form-control" placeholder="Nhập họ và tên đầy đủ..." required autocomplete="off">
                    </div>


                    <div class="form-group">
                        <label>Tài khoản mới / MSSV (Đúng 10 ký tự)</label>
                        <input type="text" name="new_username" class="form-control" placeholder="Phải nhập đủ 10 ký tự..." minlength="10" maxlength="10" pattern="[A-Za-z0-9]{10}" required autocomplete="off" title="Tài khoản bắt buộc phải có độ dài chính xác là 10 ký tự.">
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ Gmail liên kết</label>
                        <div class="email-group">
                            <input type="email" id="reg_email" name="new_email" class="form-control" placeholder="Ví dụ: nguyen@gmail.com" required autocomplete="off">
                            <button type="button" class="btn-otp" onclick="sendVerificationCode()">Gửi mã xác nhận</button>
                        </div>
                    </div>


                    <div class="form-group">
                        <label>Mã xác thực từ Gmail</label>
                        <input type="text" name="email_otp" class="form-control" placeholder="Nhập mã 6 số nhận được..." required maxlength="6" autocomplete="one-time-code">
                    </div>


                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••" required autocomplete="new-password">
                    </div>


                    <div class="form-group">
                        <label>Vai trò mong muốn</label>
                        <select name="new_role" class="form-control" style="cursor: pointer;">
                            <option value="student">Học Sinh / Thí Sinh</option>
                            <option value="teacher">Giáo Viên</option> </select>
                    </div>
                    <button type="submit" class="btn-submit">Hoàn Tất Đăng Ký</button>
                </form>
            </div>


            <div id="forgotForm" class="auth-form <?php echo $active_tab == 'forgot' ? 'active' : ''; ?>">
                <?php if ($auth_error != "" && $active_tab == 'forgot'): ?><div class="error-msg"><?php echo htmlspecialchars($auth_error); ?></div><?php endif; ?>
                
                <form action="" method="POST" autocomplete="off">
                    <input type="hidden" name="action_type" value="forgot_password">
                    
                    <div class="form-group">
                        <label>Tài khoản / MSSV cần lấy lại</label>
                        <input type="text" name="forgot_username" class="form-control" placeholder="Nhập tài khoản cần khôi phục..." required value="<?php echo isset($_POST['forgot_username']) ? htmlspecialchars($_POST['forgot_username']) : ''; ?>">
                    </div>


                    <div class="form-group">
                        <label>Gmail đã liên kết tài khoản</label>
                        <div class="email-group">
                            <input type="email" id="forgot_email" name="forgot_email" class="form-control" placeholder="Nhập Gmail chính xác của bạn..." required value="<?php echo isset($_POST['forgot_email']) ? htmlspecialchars($_POST['forgot_email']) : ''; ?>">
                            <button type="button" class="btn-otp" id="btn-forgot-otp" onclick="sendForgotVerificationCode()">Gửi mã</button>
                        </div>
                    </div>


                    <div class="form-group">
                        <label>Mã xác thực phục hồi (OTP)</label>
                        <input type="text" name="forgot_otp" class="form-control" placeholder="Nhập mã 6 chữ số..." required maxlength="6">
                    </div>


                    <div class="form-group">
                        <label>Mật khẩu mới muốn thay đổi</label>
                        <input type="password" name="forgot_password" class="form-control" placeholder="Tạo mật khẩu an toàn mới..." required>
                    </div>


                    <button type="submit" class="btn-submit">Xác Nhận Đặt Lại Mật Khẩu</button>


                    <div style="margin-top: 25px;">
                        <a href="javascript:void(0)" onclick="switchTab('login')" style="color: #64748b; font-size: 14px; font-weight: 600; text-decoration: none;">← Quay lại Đăng Nhập</a>
                    </div>
                </form>
            </div>


        </div>
    </div>


    <div class="main-footer">© 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online.</div>


    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `custom-toast ${type}`;
            
            let icon = '📩 ';
            if (type === 'error') icon = '❌ ';
            if (type === 'info') icon = 'ℹ️ ';
            
            toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
            container.appendChild(toast);
            
            setTimeout(() => { toast.classList.add('show'); }, 50);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => { toast.remove(); }, 400);
            }, 4000);
        }


        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
            const tabHeader = document.getElementById('authTabHeader');
            
            if(tabName === 'login') {
                tabHeader.style.display = 'flex';
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
            } else if(tabName === 'register') {
                tabHeader.style.display = 'flex';
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('registerForm').classList.add('active');
            } else if(tabName === 'forgot') {
                // Ẩn thanh tab header gốc đi để tập trung vào luồng lấy lại mật khẩu
                tabHeader.style.display = 'none';
                document.getElementById('forgotForm').classList.add('active');
            }
        }


        // JS cũ gửi OTP đăng ký
        function sendVerificationCode() {
            const emailInput = document.getElementById('reg_email');
            const email = emailInput.value;
            const btnOtp = document.querySelector('.btn-otp');


            if (!email || !emailInput.checkValidity()) {
                showToast('Vui lòng nhập định dạng địa chỉ Gmail chính xác trước khi gửi!', 'error');
                return;
            }


            btnOtp.disabled = true;
            btnOtp.innerText = 'Đang gửi...';


            fetch('actions/send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Mã xác thực đã được gửi thành công vào hộp thư của bạn! Vui lòng kiểm tra.', 'success');
                    let countdown = 60;
                    const timer = setInterval(() => {
                        countdown--;
                        btnOtp.innerText = `Gửi lại (${countdown}s)`;
                        if (countdown <= 0) {
                            clearInterval(timer);
                            btnOtp.disabled = false;
                            btnOtp.innerText = 'Gửi mã xác nhận';
                        }
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                    btnOtp.disabled = false;
                    btnOtp.innerText = 'Gửi mã xác nhận';
                }
            })
            .catch(error => {
                showToast('Không kết nối được tới máy chủ gửi mail hệ thống.', 'error');
                btnOtp.disabled = false;
                btnOtp.innerText = 'Gửi mã xác nhận';
            });
        }


        // THÊM MỚI: JS Kích hoạt gửi OTP khôi phục mật khẩu
        function sendForgotVerificationCode() {
            const emailInput = document.getElementById('forgot_email');
            const email = emailInput.value;
            const btnOtp = document.getElementById('btn-forgot-otp');


            if (!email || !emailInput.checkValidity()) {
                showToast('Vui lòng nhập định dạng địa chỉ Gmail chính xác trước khi phục hồi!', 'error');
                return;
            }


            btnOtp.disabled = true;
            btnOtp.innerText = 'Đang gửi...';


            fetch('actions/send_forgot_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Hệ thống tìm thấy tài khoản! Mã xác thực phục hồi đã gửi vào hòm thư.', 'success');
                    let countdown = 60;
                    const timer = setInterval(() => {
                        countdown--;
                        btnOtp.innerText = `Gửi lại (${countdown}s)`;
                        if (countdown <= 0) {
                            clearInterval(timer);
                            btnOtp.disabled = false;
                            btnOtp.innerText = 'Gửi mã';
                        }
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                    btnOtp.disabled = false;
                    btnOtp.innerText = 'Gửi mã';
                }
            })
            .catch(error => {
                showToast('Không kết nối được tới máy chủ gửi mail hệ thống.', 'error');
                btnOtp.disabled = false;
                btnOtp.innerText = 'Gửi mã';
            });
        }
    </script>
</body>
</html>