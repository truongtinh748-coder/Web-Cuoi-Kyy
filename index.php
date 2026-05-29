<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Trắc Nghiệm Online</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: #f0f7ff; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* Navbar */
        .navbar { background: #0f172a; padding: 18px 50px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .logo { color: #38bdf8; font-size: 20px; font-weight: bold; text-decoration: none; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 500; }
        .nav-links a:hover { color: #38bdf8; }

        /* Giao diện chính giữa */
        .hero-section { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #e0f2fe 0%, #f3e8ff 100%); }
        .hero-section h1 { font-size: 42px; color: #0f172a; margin-bottom: 15px; font-weight: 800; }
        .hero-section p { font-size: 16px; color: #475569; margin-bottom: 30px; max-width: 600px; }
        
        .btn-start { background: #2563eb; color: white; padding: 14px 35px; border-radius: 12px; font-size: 16px; font-weight: bold; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: 0.2s; }
        .btn-start:hover { background: #1d4ed8; transform: translateY(-1px); }

        /* Các thẻ tính năng */
        .features-container { display: flex; gap: 20px; justify-content: center; max-width: 1200px; margin: -50px auto 50px auto; padding: 0 20px; width: 100%; }
        .feature-card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); flex: 1; text-align: center; border: 1px solid #e2e8f0; }
        .feature-card h3 { color: #2563eb; font-size: 16px; font-weight: bold; margin-bottom: 12px; text-transform: uppercase; }
        .feature-card p { color: #64748b; font-size: 13px; line-height: 1.6; }

        .footer { text-align: center; padding: 15px; background: #0f172a; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="index.php" class="logo">Cổng Trắc Nghiệm Online</a>
        <div class="nav-links">
            <a href="index.php">Trang Chủ</a>
        </div>
    </div>

    <div class="hero-section">
        <h1>Chào mừng tới Cổng Quản Lý Giáo Dục và Kiểm Tra</h1>
        <p>Nền tảng thi và chấm điểm trực tuyến dành cho Học sinh & Giáo viên.</p>
        
        <a href="login.php" class="btn-start">BẮT ĐẦU NGAY</a>
    </div>

    <div class="features-container">
        <div class="feature-card">
            <h3>Nhanh Chóng</h3>
            <p>Hệ thống bốc đề ngẫu nhiên theo mã đề và hiển thị kết quả chấm điểm tức thì ngay sau khi nộp bài.</p>
        </div>
        <div class="feature-card">
            <h3>Chính Xác</h3>
            <p>Đồng hồ đếm ngược thông minh tự động khóa đề thi khi hết giờ, hệ thống phát hiện gian lận khi thoát ra trong lúc làm bài ,đảm bảo tính minh bạch tuyệt đối.</p>
        </div>
        <div class="feature-card">
            <h3>Tiện Lợi</h3>
            <p>Giáo viên dễ dàng quản lý kho câu hỏi, kiểm soát thời gian làm bài và theo dõi tiến độ thi toàn trường.</p>
        </div>
    </div>

    <div class="footer">
        © 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online - CORE DEV POWERED BY NGUYÊN HỘI
    </div>

</body>
</html>