<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// KHỞI TẠO VÀ ĐỒNG BỘ MẢNG TÀI KHOẢN MẪU BAN ĐẦU
if (!isset($_SESSION['system_users']) || !is_array($_SESSION['system_users'])) {
    $_SESSION['system_users'] = [
        ['user' => 'admin', 'pass' => 'admin123', 'role' => 'admin', 'email' => 'admin@gmail.com', 'fullname' => 'Quản Trị Viên Hệ Thống'],
        ['user' => '1123456789', 'pass' => '123456', 'role' => 'student', 'email' => 'student@gmail.com', 'fullname' => 'Nguyễn Học Sinh'],
        ['user' => 'teacher01', 'pass' => '123456', 'role' => 'teacher', 'email' => 'teacher@gmail.com', 'fullname' => 'Giáo Viên Mẫu']
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? (string)trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)trim($_POST['password']) : '';

    $found = false;
    $user_role = '';
    $user_fullname = '';
    $user_email = '';

    foreach ($_SESSION['system_users'] as $u) {
        if ((string)$u['user'] === $username && (string)$u['pass'] === $password) {
            $found = true;
            $user_role = $u['role'];
            $user_fullname = $u['fullname'] ?? $username;
            $user_email = $u['email'] ?? '';
            break;
        }
    }

    if ($found) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user_role;
        $_SESSION['fullname'] = $user_fullname;
        $_SESSION['email'] = $user_email;

        if ($user_role === 'admin') {
            echo "<script>
                alert('Đăng nhập thành công với quyền Admin!');
                window.location.href = '../admin_dashboard.php';
            </script>";
        } elseif ($user_role === 'teacher') {
            echo "<script>
                alert('Đăng nhập thành công với quyền Giáo viên!');
                window.location.href = '../teacher_dashboard.php';
            </script>";
        } else {
            echo "<script>
                alert('Đăng nhập thành công với quyền Sinh viên!');
                window.location.href = '../students.php';
            </script>";
        }
        exit();
    } else {
        $_SESSION['auth_error'] = "Tài khoản hoặc mật khẩu không chính xác!";
        echo "<script>
            alert('Sai tài khoản hoặc mật khẩu! Vui lòng kiểm tra lại.');
            window.location.href = '../loBgin.php';
        </script>";
        exit();
    }
}

header("Location: ../login.php");
exit();
?>