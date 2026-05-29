<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 🗄️ CẬP NHẬT: ĐƯỜNG DẪN ĐẾN CƠ SỞ DỮ LIỆU FILE JSON DÙNG CHUNG LAN
// =========================================================================
$usersFile = '../data/users.json';

// Tự động tạo thư mục data nếu chưa tồn tại
if (!file_exists(dirname($usersFile))) {
    mkdir(dirname($usersFile), 0777, true);
}

// Đọc dữ liệu tài khoản hiện tại từ file JSON lên hệ thống
$system_users = [];
if (file_exists($usersFile)) {
    $system_users = json_decode(file_get_contents($usersFile), true);
}

// 1. KHỞI TẠO ĐỒNG BỘ MẢNG TÀI KHOẢN MẪU NẾU FILE TRỐNG ĐỂ KHÔNG BỊ MẤT DỮ LIỆU
if (!isset($system_users) || !is_array($system_users) || empty($system_users)) {
    $system_users = [
        ['user' => 'adminadmin', 'pass' => 'admin123', 'role' => 'admin', 'email' => 'admin@gmail.com', 'fullname' => 'Quản Trị Viên'],
        ['user' => '2012345678', 'pass' => '123456', 'role' => 'student', 'email' => 'student@gmail.com', 'fullname' => 'Nguyễn Văn Mẫu']
    ];
    file_put_contents($usersFile, json_encode($system_users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CẬP NHẬT: Nhận thêm trường họ và tên từ form đăng ký gửi sang
    $new_fullname = isset($_POST['new_fullname']) ? trim($_POST['new_fullname']) : '';
    $new_user   = trim($_POST['new_username']);
    $new_pass   = trim($_POST['new_password']);
    $new_email  = trim($_POST['new_email']);
    $new_role   = trim($_POST['new_role']);
    $email_otp  = trim($_POST['email_otp']); 

    // Lấy mã xác thực thật và email nhận mã từ Session
    $system_otp    = isset($_SESSION['email_verify_code']) ? $_SESSION['email_verify_code'] : (isset($_SESSION['email_verification_otp']) ? $_SESSION['email_verification_otp'] : '');
    $system_target = isset($_SESSION['email_verification_target']) ? $_SESSION['email_verification_target'] : '';

    // ĐIỀU KIỆN 1: Kiểm tra xem người dùng đã thực sự nhấn nút gửi mã OTP từ trước chưa
    if (empty($system_otp)) {
        $_SESSION['register_error'] = "Đăng ký thất bại: Bạn chưa nhấn nút gửi mã xác thực về Gmail!";
        header("Location: ../login.php");
        exit();
    }

    // ĐIỀU KIỆN 2: Kiểm tra chống đổi Email ảo lúc sau
    if (!empty($system_target) && $new_email !== $system_target) {
        $_SESSION['register_error'] = "Đăng ký thất bại: Email đăng ký không khớp với Email đã nhận mã số OTP!";
        header("Location: ../login.php");
        exit();
    }

    // ĐIỀU KIỆN 3: So khớp mã OTP người dùng nhập vào
    if (empty($email_otp) || (string)$email_otp !== (string)$system_otp) {
        $_SESSION['register_error'] = "Đăng ký thất bại: Mã xác thực OTP nhập vào không chính xác!";
        header("Location: ../login.php");
        exit();
    }

    // ĐIỀU KIỆN 4: Kiểm tra trùng lặp và giới hạn số lượng tài khoản trên mỗi Email dựa vào file JSON mới
    $username_exists = false;
    $email_count = 0; // Biến đếm số tài khoản đã đăng ký bằng Email này

    foreach ($system_users as $u) {
        // Tên tài khoản / MSSV thì bắt buộc luôn luôn là duy nhất (không được trùng)
        if ((string)$u['user'] === (string)$new_user) {
            $username_exists = true;
            break;
        }
        // Đếm số lần email này xuất hiện trong hệ thống
        if (isset($u['email']) && $u['email'] === $new_email) {
            $email_count++;
        }
    }

    // Xử lý chặn dựa trên kết quả kiểm tra
    if ($username_exists) {
        $_SESSION['register_error'] = "Tài khoản hoặc MSSV 10 số này đã tồn tại trong hệ thống!";
        header("Location: ../login.php");
        exit();
    }

    if ($email_count >= 3) {
        $_SESSION['register_error'] = "Địa chỉ Gmail này đã đạt giới hạn tối đa (3 tài khoản). Không thể đăng ký thêm!";
        header("Location: ../login.php");
        exit();
    }

    // TIẾN HÀNH LƯU TÀI KHOẢN NẾU HỢP LỆ (Thỏa mãn < 3 tài khoản)
    $system_users[] = [
        'user'     => (string)$new_user,
        'pass'     => (string)$new_pass,
        'role'     => (string)$new_role,
        'email'    => (string)$new_email,
        'fullname' => (string)$new_fullname 
    ];
    
    // Ghi đè cập nhật vĩnh viễn vào file JSON trên ổ cứng máy chủ
    file_put_contents($usersFile, json_encode($system_users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // Đồng bộ ngược lại Session để các file cũ trong hệ thống xử lý song song không bị lỗi
    $_SESSION['system_users'] = $system_users;

    // Giải phóng bộ nhớ, xóa bỏ mã OTP cũ sau khi dùng xong
    unset($_SESSION['email_verify_code'], $_SESSION['email_verification_otp'], $_SESSION['email_verification_target']);
    
    $_SESSION['register_success'] = "Đăng ký thành công tài khoản mới (Gmail này đã dùng: " . ($email_count + 1) . "/3 lần)!";
    
    header("Location: ../login.php");
    exit();
}

header("Location: ../login.php");
exit();
?>