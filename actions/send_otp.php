<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Chặn tuyệt đối các cảnh báo hệ thống (Warning/Notice) in ra làm bẩn chuỗi JSON công cộng
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Gọi thủ công các file trong thư mục PHPMailer thay vì dùng autoload của Composer
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

    // Tạo mã OTP ngẫu nhiên gồm 6 chữ số
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Lưu mã OTP và email vào Session để đối chiếu lúc bấm "Hoàn Tất Đăng Ký"
    $_SESSION['email_verify_code'] = $otp;
    $_SESSION['email_verify_target'] = $email;

    $mail = new PHPMailer(true);

    try {
        // Tắt debug thô để đảm bảo an toàn cho phản hồi JSON
        $mail->SMTPDebug  = 0;
        
        // --- CẤU HÌNH SERVER GỬI MAIL (TỔNG ĐÀI) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // BẮT BUỘC ĐIỀN ĐÚNG THÔNG TIN TÀI KHOẢN CỦA BẠN ĐỂ LÀM TRẠM GỬI
        $mail->Username   = 'truongtinh748@gmail.com'; 
        $mail->Password   = 'dvrriamfpbieqtpz'; // Mật khẩu ứng dụng 16 ký tự tạo từ Google
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // --- CẤU HÌNH NGƯỜI GỬI & NGƯỜI NHẬN ---
        $mail->setFrom('truongtinh748@gmail.com', 'Hệ Thống Thi Online');
        
        // ĐÂY CHÍNH LÀ ĐỘNG: Nhận bất kỳ email nào học sinh gõ trên giao diện để gửi tới đó
        $mail->addAddress($email); 

        // --- NỘI DUNG EMAIL ---
        $mail->isHTML(true);
        $mail->Subject = '=== MÃ XÁC THỰC ĐĂNG KÝ HỆ THỐNG ===';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 500px;'>
                <h2 style='color: #0ea5e9; margin-bottom: 20px;'>Xác Thực Địa Chỉ Email</h2>
                <p>Chào bạn,</p>
                <p>Bạn đang thực hiện đăng ký tài khoản trên hệ thống thi trắc nghiệm Online.</p>
                <p>Mã xác thực OTP của bạn là:</p>
                <div style='background: #f1f5f9; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; color: #0f172a; letter-spacing: 4px; border-radius: 8px; margin: 20px 0;'>
                    {$otp}
                </div>
                <p style='color: #64748b; font-size: 12px;'>Mã này có hiệu lực trong vòng 5 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>
            </div>
        ";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Mã xác thực đã được gửi đi thành công!']);
        exit();
    } catch (Exception $e) {
        // Trả về thông báo lỗi chi tiết nếu Google từ chối mật khẩu hoặc kết nối thất bại
        echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối SMTP Mailer: ' . $mail->ErrorInfo]);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ!']);
    exit();
}