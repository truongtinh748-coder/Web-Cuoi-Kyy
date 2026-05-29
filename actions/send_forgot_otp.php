<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Chặn tuyệt đối các cảnh báo hệ thống để không làm bẩn chuỗi JSON trả về
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Gọi thư viện PHPMailer bằng đường dẫn tương đối giống hệt bên file đăng ký của bạn
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Định dạng Email không hợp lệ!']);
        exit();
    }

    // 1. ĐỌC FILE JSON ĐỂ KIỂM TRA EMAIL CÓ TỒN TẠI TRÊN HỆ THỐNG KHÔNG
    $usersFile = '../data/users.json'; // Bạn hãy check xem đường dẫn đến file users.json này đúng chưa nhé
    $email_exists = false;

    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        if (is_array($users)) {
            foreach ($users as $u) {
                if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
                    $email_exists = true;
                    break;
                }
            }
        }
    }

    if (!$email_exists) {
        echo json_encode(['status' => 'error', 'message' => 'Địa chỉ Gmail này chưa được đăng ký trên hệ thống!']);
        exit();
    }

    // 2. TẠO MÃ OTP NGẪU NHIÊN 6 SỐ
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Lưu mã OTP và email vào Session phục vụ cho bước xác thực đổi mật khẩu tiếp theo
    $_SESSION['forgot_otp'] = $otp;
    $_SESSION['forgot_email'] = $email;

    // 3. TIẾN HÀNH GỬI EMAIL THỰC TẾ QUA TRẠM GỬI CỦA BẠN
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug  = 0; // Tắt debug thô
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // Sử dụng thông tin tài khoản Gmail đã chạy thành công của bạn
        $mail->Username   = 'truongtinh748@gmail.com'; 
        $mail->Password   = 'dvrriamfpbieqtpz'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Cấu hình người gửi & người nhận
        $mail->setFrom('truongtinh748@gmail.com', 'Hệ Thống Thi Online');
        $mail->addAddress($email); 

        // Giao diện email Khôi phục mật khẩu (Màu đỏ cảnh báo thay vì màu xanh đăng ký)
        $mail->isHTML(true);
        $mail->Subject = '=== MÃ KHÔI PHỤC MẬT KHẨU HỆ THỐNG ===';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 500px;'>
                <h2 style='color: #ef4444; margin-bottom: 20px;'>Yêu Cầu Khôi Phục Mật Khẩu</h2>
                <p>Chào bạn,</p>
                <p>Chúng tôi nhận được yêu cầu lấy lại mật khẩu cho tài khoản ứng với Email này của bạn.</p>
                <p>Mã xác thực OTP Quên mật khẩu của bạn là:</p>
                <div style='background: #f1f5f9; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; color: #0f172a; letter-spacing: 4px; border-radius: 8px; margin: 20px 0;'>
                    {$otp}
                </div>
                <p style='color: #64748b; font-size: 12px;'>Mã này có hiệu lực trong vòng 5 phút. Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email và bảo mật tài khoản.</p>
            </div>
        ";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Mã OTP khôi phục mật khẩu đã được gửi vào Gmail của bạn!']);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối SMTP Mailer: ' . $mail->ErrorInfo]);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ!']);
    exit();
}