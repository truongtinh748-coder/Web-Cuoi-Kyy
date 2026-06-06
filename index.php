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
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: radial-gradient(circle at top left, #e0f2fe 0, #eff6ff 40%, #f5f3ff 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.96);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.45);
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        .logo {
            color: #38bdf8;
            font-size: 20px;
            font-weight: 800;
            text-decoration: none;
            letter-spacing: .3px;
            position: relative;
        }

        .logo::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 260px;
            height: 2px;
            background: linear-gradient(90deg, #38bdf8, #a855f7);
            border-radius: 999px;
        }

        .nav-links {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .nav-links a {
            color: #e5e7eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 999px;
            transition: color .18s ease, background .18s ease, transform .18s ease;
        }

        .nav-links a:hover {
            color: #38bdf8;
            background: rgba(15, 23, 42, 0.75);
            transform: translateY(-1px);
        }

        /* Giao diện chính giữa */
        .hero-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 56px 20px 40px;
            position: relative;
            overflow: hidden;
        }

        /* Background hiệu ứng */
        .hero-section::before,
        .hero-section::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            filter: blur(40px);
            opacity: 0.55;
            pointer-events: none;
        }

        .hero-section::before {
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, #38bdf8, transparent 60%);
            top: -60px;
            right: -80px;
        }

        .hero-section::after {
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, #a855f7, transparent 60%);
            bottom: -80px;
            left: -60px;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 720px;
            padding: 28px 24px 32px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(255,255,255,0.96), rgba(248,250,252,0.96));
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 22px 45px rgba(15, 23, 42, 0.18);
        }

        .hero-section h1 {
           font-size: clamp(22px, 3.2vw, 32px);  /* nhỏ hơn, responsive hơn */
           font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.35;
            letter-spacing: 0.03em;              /* nới nhẹ khoảng cách chữ */
            background: linear-gradient(135deg, #0f172a, #1d4ed8, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;                  /* tạo chữ gradient */
            position: relative;
            display: inline-block;
            padding-bottom: 8px;
}

        .hero-section h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, #38bdf8, #a855f7);
            border-radius: 999px;
            transform: translateX(-50%);
            box-shadow: 0 4px 10px rgba(59,130,246,0.45);
}
        }

        .hero-section p {
            font-size: 15px;
            color: #475569;
            margin-bottom: 24px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        .hero-highlight {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.06);
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 15px;
        }

        .btn-start {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            color: white;
            padding: 14px 32px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.35);
            transition: transform .22s ease, box-shadow .22s ease, filter .22s ease;
            overflow: hidden;
        }

        .btn-start::before {
            content: '';
            position: absolute;
            top: 0;
            left: -120%;
            width: 70%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,.5), transparent);
            transform: skewX(-20deg);
            transition: left .55s ease;
        }

        .btn-start:hover::before {
            left: 130%;
        }

        .btn-start:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 18px 38px rgba(37, 99, 235, 0.42);
            filter: brightness(1.03);
        }

        .btn-start span {
            font-size: 18px;
        }

        /* Các thẻ tính năng */
        .features-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            max-width: 1200px;
            margin: -40px auto 50px auto;
            padding: 0 20px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .feature-card {
            background: #ffffff;
            padding: 24px 22px 26px;
            border-radius: 15px;
            box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12);
            flex: 1;
            text-align: left;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transform: translateY(0);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(37,99,235,0.08), rgba(56,189,248,0.08));
            opacity: 0;
            transition: opacity .22s ease;
            pointer-events: none;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.18);
            border-color: #bfdbfe;
            background: #f9fafb;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
            color: #1d4ed8;
            background: radial-gradient(circle at top left, #dbeafe, #eff6ff);
        }

        .feature-card h3 {
            color: #2563eb;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .feature-card p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .footer {
            text-align: center;
            padding: 14px 10px;
            background: #020617;
            color: #64748b;
            font-size: 12px;
            border-top: 1px solid #111827;
        }

        @media (max-width: 900px) {
            .navbar {
                padding: 12px 16px;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .hero-inner {
                padding: 24px 18px 26px;
            }

            .features-container {
                flex-direction: column;
                margin-top: -20px;
            }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a class="logo">Cổng Trắc Nghiệm Online</a>
    </div>
    <div class="hero-section">
        <div class="hero-inner">
            <div class="hero-highlight">
                <span>📚 Nền tảng thi trực tuyến</span>
            </div>
            <h1>Chào mừng tới Cổng Quản Lý Giáo Dục và Kiểm Tra</h1>
            <p style="margin-bottom: 10px;">
            Nền tảng thi và chấm điểm trực tuyến dành cho Học sinh & Giáo viên.</p>
            <a href="login.php" class="btn-start">
                BẮT ĐẦU NGAY
                <span>➜</span>
            </a>
        </div>
    </div>

    <div class="features-container">
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3>Nhanh Chóng</h3>
            <p>Hệ thống bốc đề ngẫu nhiên theo mã đề và hiển thị kết quả chấm điểm tức thì ngay sau khi nộp bài.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Chính Xác</h3>
            <p>Đồng hồ đếm ngược thông minh tự động khóa đề thi khi hết giờ, hệ thống phát hiện gian lận khi thoát ra trong lúc làm bài, đảm bảo tính minh bạch tuyệt đối.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🧑‍🏫</div>
            <h3>Tiện Lợi</h3>
            <p>Giáo viên dễ dàng quản lý kho câu hỏi, kiểm soát thời gian làm bài và theo dõi tiến độ thi toàn trường.</p>
        </div>
    </div>

    <div class="footer">
        © 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online
    </div>

</body>
</html>