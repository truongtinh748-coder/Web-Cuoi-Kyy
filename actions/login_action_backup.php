<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// KHỞI TẠO VÀ ĐỒNG BỘ MẢNG TÀI KHOẢN MẪU BAN ĐẦU
if (!isset($_SESSION['system_users']) || !is_array($_SESSION['system_users'])) {
    $_SESSION['system_users'] = [
        ['user' => 'admin', 'pass' => 'admin123', 'role' => 'admin', 'email' => 'admin@gmail.com'],
        ['user' => '1123456789', 'pass' => '123456', 'role' => 'student', 'email' => 'student@gmail.com']
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? (string)trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)trim($_POST['password']) : '';

    $found = false;
    $user_role = '';

    foreach ($_SESSION['system_users'] as $u) {
        if ((string)$u['user'] === $username && (string)$u['pass'] === $password) {
            $found = true;
            $user_role = $u['role']; 
            break;
        }
    }

    if ($found) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user_role;
        
        // ĐIỀU HƯỚNG CHUẨN XÁC THEO TÊN FILE THỰC TẾ CỦA BẠN
        if ($user_role === 'admin' || $user_role === 'teacher') {
            echo "<script>
                alert('Đăng nhập thành công với quyền Giáo viên!');
                window.location.href = '../dashboard.php'; // Admin vào file quản lý giám sát sinh viên của bạn
            </script>";
        } else {
            echo "<script>
                alert('Đăng nhập thành công với quyền Sinh viên!');
                window.location.href = '../students.php'; // Sinh viên vào đúng file students.php của bạn
            </script>";
        }
        exit();
    } else {
        $_SESSION['auth_error'] = "Tài khoản hoặc mật khẩu không chính xác!";
        echo "<script>
            alert('Sai tài khoản hoặc mật khẩu! Vui lòng kiểm tra lại.');
            window.location.href = '../login.php';
        </script>";
        exit();
    }
}

header("Location: ../login.php");
exit();
?>