<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : "";
$success = isset($_SESSION['register_success']) ? $_SESSION['register_success'] : "";

unset($_SESSION['register_error']);
unset($_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản - Cổng Quản Lý Giáo Dục</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { display: flex; flex-direction: column; min-height: 100vh; background: #ffffff; color: #1e293b; }
        
        .navbar { background: #1e293b; padding: 18px 50px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); z-index: 10; }
        .logo { color: #38bdf8; font-size: 20px; font-weight: bold; text-decoration: none; letter-spacing: 0.5px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 500; transition: 0.2s; padding: 6px 12px; border-radius: 6px; }
        .nav-links a:hover { color: #38bdf8; }
        .nav-links a.active { color: #ffffff; font-weight: 600; background: rgba(255, 255, 255, 0.1); }

        .auth-container { flex: 1; display: flex; width: 100%; position: relative; }
        
        .left-panel { flex: 1.2; background: linear-gradient(135deg, #ccfbf1 0%, #dbeafe 50%, #f3e8ff 100%); display: flex; align-items: center; justify-content: center; }
        
        .right-panel { flex: 1; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #dbeafe 0%, #f3e8ff 100%); padding: 20px; }
        
        .login-card { background: #ffffff; padding: 40px; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.02); max-width: 440px; width: 100%; border: 1px solid rgba(226, 232, 240, 0.8); text-align: center; }
        .card-title { font-size: 24px; font-weight: 800; color: #1e293b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: -0.5px; }
        .card-subtitle { font-size: 13px; color: #64748b; margin-bottom: 25px; font-weight: 500; }
        
        .form-group { margin-bottom: 16px; text-align: left; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #1e293b; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 12px 16px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; outline: none; transition: all 0.2s ease; color: #334155; }
        .form-control:focus { border-color: #38bdf8; background: #ffffff; box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15); }
        
        .btn-submit { background: linear-gradient(90deg, #38bdf8 0%, #0ea5e9 100%); color: #ffffff; border: none; width: 100%; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-transform: uppercase; margin-top: 10px; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.25); transition: all 0.2s ease; letter-spacing: 0.5px; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(14, 165, 233, 0.35); }
        
        .error-msg { color: #ef4444; background: #fef2f2; border: 1px solid #fee2e2; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        .success-msg { color: #166534; background: #dcfce7; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        
        .brand-footer { text-align: center; margin-top: 30px; font-size: 11px; color: #94a3b8; font-weight: bold; border-top: 1px dashed #e2e8f0; padding-top: 20px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .brand-footer span { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        
        .main-footer { text-align: center; padding: 15px; background: #0f172a; color: #64748b; font-size: 12px; font-weight: 500; border-top: 1px solid #1e293b; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="index.php" class="logo">Cổng Quản Lý Giáo Dục và Kiểm Tra Online</a>
        <div class="nav-links">
            <a href="index.php">Trang Chủ</a>
            <a href="login.php">Đăng Nhập</a>
        </div>
    </div>

    <div class="auth-container">
        <div class="left-panel"></div>
        
        <div class="right-panel">
            <div class="login-card">
                <div class="card-title">TẠO TÀI KHOẢN</div>
                <div class="card-subtitle">Gia nhập Hệ thống Giáo dục trực tuyến</div>

                <?php if ($error != ""): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success != ""): ?>
                    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form action="actions/register_action.php" method="POST">
                    <div class="form-group">
                        <label>Tài khoản đăng ký mới / MSSV</label>
                        <input type="text" name="new_username" class="form-control" placeholder="Ví dụ: 440125" required>
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••" required>
                    </div>

                    <div class="form-group">
                        <label>Vai trò mong muốn</label>
                        <select name="new_role" class="form-control" style="cursor: pointer;">
                            <option value="student">Học Sinh / Thí Sinh</option>
                            <option value="admin">Giáo Viên (Admin)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit">Hoàn Tất Đăng Ký</button>
                </form>

                <p style="text-align: center; font-size: 13px; color: #64748b; margin-top: 20px;">
                    Đã có tài khoản rồi? <a href="login.php" style="color: #0ea5e9; text-decoration: none; font-weight: 600;">Quay lại Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>

    <div class="main-footer">
        © 2026 Toàn bộ bản quyền thuộc về Hệ thống thi trắc nghiệm Online.
    </div>

</body>
</html>