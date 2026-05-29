<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_logged'])) { 
    header("Location: login.php"); 
    exit(); 
}

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    }
    if ($_SESSION['role'] === 'teacher') {
        header("Location: teacher_dashboard.php");
        exit();
    }
}

$username = $_SESSION['user_logged'];
$subjects = ['toan', 'php', 'xstk', 'mmt', 'csdl', 'ta']; 

$subjectNames = [
    'toan' => 'Toán Học',
    'php'  => 'Lập trình PHP',
    'xstk' => 'Xác suất Thống kê',
    'mmt'  => 'Mạng Máy Tính',
    'csdl' => 'Cơ Sở Dữ Liệu',
    'ta'   => 'Tiếng Anh chuyên ngành'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển - Chọn môn thi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --primary: #1e293b; 
            --accent: #2563eb;  
            --text-main: #334155;
            --white: #ffffff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); line-height: 1.6; }
        .navbar { 
            background: var(--primary); 
            padding: 15px 60px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo { font-weight: 800; font-size: 1.2rem; color: #f8fafc; text-decoration: none; }
        .user-nav { display: flex; align-items: center; gap: 20px; font-size: 0.9rem; }
        .btn-logout { 
            background: #ef4444; 
            color: white; 
            padding: 8px 18px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
        }
        .btn-logout:hover { background: #b91c1c; }
        .container { max-width: 1100px; margin: 60px auto; padding: 0 20px; }
        .header-content { text-align: center; margin-bottom: 50px; }
        .header-content h1 { font-size: 2.5rem; font-weight: 800; color: var(--primary); margin-bottom: 10px; }
        .header-content p { color: #64748b; font-size: 1.1rem; }
        .section-title { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-size: 1.2rem; 
            font-weight: 700; 
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .subject-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 25px; 
            margin-bottom: 60px;
        }
        .subject-card { 
            background: var(--white); 
            border: 1px solid #e2e8f0; 
            padding: 35px 20px; 
            border-radius: 12px; 
            text-align: center; 
            transition: 0.3s;
            text-decoration: none;
            display: block;
        }
        .subject-card:hover { 
            border-color: var(--accent); 
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .subject-card h3 { color: var(--primary); font-size: 1.1rem; font-weight: 700; }
        .subject-card .icon { font-size: 3rem; margin-bottom: 15px; display: block; }
        .random-box { 
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
            padding: 50px; 
            border-radius: 20px; 
            color: white; 
            text-align: center;
        }
        .random-box h2 { font-size: 2rem; margin-bottom: 15px; }
        .random-box p { margin-bottom: 30px; opacity: 0.9; }
        .btn-random { 
            display: inline-block;
            background: #fbbf24; 
            color: #78350f;
            padding: 16px 40px; 
            border-radius: 50px; 
            font-weight: 800; 
            text-decoration: none; 
            text-transform: uppercase;
        }
        .btn-random:hover { transform: scale(1.05); background: #f59e0b; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="home.php" class="logo">EXAM SYSTEM</a>
        <div class="user-nav">
            <span>Thí sinh: <strong><?php echo htmlspecialchars($username); ?></strong></span>
            <a href="actions/logout.php" class="btn-logout">ĐĂNG XUẤT</a>
        </div>
    </div>

    <div class="container">
        <div class="header-content">
            <h1>Phòng Thi Trực Tuyến</h1>
            <p>Vui lòng lựa chọn môn thi cụ thể bên dưới hoặc thử thách bốc đề ngẫu nhiên hệ thống.</p>
        </div>

        <div class="section-title">📂 Danh sách môn học hệ thống</div>
        <div class="subject-grid">
            <?php foreach ($subjects as $sub): ?>
                <a href="exam.php?subject=<?php echo urlencode($sub); ?>" class="subject-card">
                    <span class="icon">
                        <?php 
                            if ($sub === 'toan') echo '📐';
                            elseif ($sub === 'php') echo '🐘';
                            elseif ($sub === 'xstk') echo '📊';
                            elseif ($sub === 'mmt') echo '🌐';
                            elseif ($sub === 'csdl') echo '🗄️';
                            elseif ($sub === 'ta') echo '🇬🇧';
                            else echo '📝';
                        ?>
                    </span>
                    <h3><?php echo isset($subjectNames[$sub]) ? $subjectNames[$sub] : strtoupper($sub); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="random-box">
            <h2>Bốc Đề Ngẫu Nhiên 🎲</h2>
            <p>Hệ thống sẽ tự động bốc ngẫu nhiên 1 môn học và rút ra bộ đề 24 câu hỏi bất ngờ.</p>
            <?php $randomSub = $subjects[array_rand($subjects)]; ?>
            <a href="exam.php?subject=<?php echo urlencode($randomSub); ?>&mode=random" class="btn-random">
                Bắt đầu thử thách ngay
            </a>
        </div>
    </div>

</body>
</html>
