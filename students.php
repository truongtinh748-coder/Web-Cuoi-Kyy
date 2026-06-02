<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Nếu không có session sinh viên hoặc quyền là admin/teacher thì đẩy về trang login
if (!isset($_SESSION['username']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) { 
    header("Location: login.php"); 
    exit(); 
}

// Khởi tạo mảng lịch sử trống để tránh lỗi foreach nếu chưa thi bài nào
if (!isset($_SESSION['global_history']) || !is_array($_SESSION['global_history'])) {
    $_SESSION['global_history'] = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Điều Khiển Sinh Viên - NGUYÊN HỘI</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: #f3f4f6; color: #1f2937; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar { background: #1e293b; color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .navbar h2 { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .navbar h2 span { color: #38bdf8; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .btn-logout { background: #ef4444; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-logout:hover { background: #dc2626; }
        .main-container { max-width: 1000px; width: 95%; margin: 40px auto; flex: 1; }
        .welcome-card { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 10px 15px -3px rgba(59,130,246,0.3); }
        .welcome-card h3 { font-size: 24px; margin-bottom: 8px; }
        .setup-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 30px; }
        .form-select { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 15px; width: 250px; outline: none; margin-right: 15px; background: #fff; }
        .btn-start { background: #06b6d4; color: white; padding: 11px 24px; border: none; border-radius: 6px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-start:hover { background: #0891b2; transform: translateY(-1px); }
        .table-section h4 { font-size: 18px; margin-bottom: 15px; color: #374151; border-left: 4px solid #3b82f6; padding-left: 10px; }
        .search-input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; margin-bottom: 15px; font-size: 14px; outline: none; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        th, td { padding: 14px 20px; text-align: left; font-size: 14px; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        td { border-bottom: 1px solid #f1f5f9; color: #334155; }
        .badge-score { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-weight: 700; }
        footer { text-align: center; padding: 20px; color: #64748b; font-size: 13px; background: white; border-top: 1px solid #e2e8f0; margin-top: auto; }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>CỔNG THI TRẮC NGHIỆM ONLINE <span>NGUYÊN HỘI</span></h2>
        <div class="user-info">
            <span style="font-weight: 500;">Sinh viên: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="login.php" class="btn-logout">Đăng xuất</a>
        </div>
    </div>

    <div class="main-container">
        <div class="welcome-card">
            <h3>Chào mừng trở lại, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
            <p>Vui lòng lựa chọn môn học phía dưới để bắt đầu làm bài kiểm tra cuối kỳ hệ thống.</p>
        </div>

        <div class="setup-box">
            <label style="font-weight: 600; margin-right: 10px; color: #4b5563;">Chọn môn thi cuối kỳ:</label>
            <select id="subjectSelect" class="form-select">
                <option value="web">Lập trình Web</option>
                <option value="net">Mạng máy tính</option>
            </select>
            <button onclick="startExam()" class="btn-start">Bắt Đầu Làm Bài 🚀</button>
        </div>

        <div class="table-section">
            <h4>🕒 Lịch Sử Khảo Sát Kết Quả</h4>
            <input type="text" id="searchHistory" class="search-input" onkeyup="filterHistory()" placeholder="🔍 Tìm nhanh theo tên môn học hoặc từ khóa...">
            <table>
                <thead>
                    <tr>
                        <th>Học Viên</th>
                        <th>Môn Thi Đã Làm</th>
                        <th>Điểm Đạt Được</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <?php 
                    if (!empty($_SESSION['global_history'])):
                        foreach ($_SESSION['global_history'] as $row): 
                            if (isset($row['name']) && $row['name'] === $_SESSION['username']):
                    ?>
                    <tr data-student="<?php echo htmlspecialchars($row['search_tag'] ?? ''); ?>">
                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['subject'] ?? ''); ?></td>
                        <td><span class="badge-score"><?php echo htmlspecialchars($row['score'] ?? '0'); ?> / 10.0</span></td>
                    </tr>
                    <?php 
                            endif;
                        endforeach; 
                    else:
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px; color: #94a3b8;">Bạn chưa làm bài kiểm tra nào.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>© 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online</footer>

    <script>
        function startExam() {
            const sub = document.getElementById("subjectSelect").value;
            window.location.href = `exam.php?subject=${encodeURIComponent(sub)}`;
        }
        function filterHistory() {
            const query = document.getElementById("searchHistory").value.trim().toLowerCase();
            const rows = document.querySelectorAll("#historyTableBody tr");
            rows.forEach(row => {
                const info = row.getAttribute("data-student") || "";
                row.style.display = info.toLowerCase().includes(query) ? "" : "none";
            });
        }
    </script>
</body>
</html>