<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
// === BƯỚC 1: TĂNG THỜI GIAN SESSION ===
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
    $_SESSION['last_activity'] = time();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    // session cũ
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$jsonFile = 'data/questions.json';
$resultsFile = 'data/results.json';
$examStatusFile = 'data/exam_status.json';
$activeSessionsFile = 'data/active_sessions.json';
$usersFile = 'data/users.json';

if (file_exists($usersFile)) {
    $_SESSION['system_users'] = json_decode(file_get_contents($usersFile), true);
} else {
    if (isset($_SESSION['system_users']) && is_array($_SESSION['system_users'])) {
        if (!file_exists('data')) { mkdir('data', 0777, true); }
        file_put_contents($usersFile, json_encode($_SESSION['system_users'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

if (!isset($_SESSION['system_users']) || !is_array($_SESSION['system_users'])) {
    $_SESSION['system_users'] = [];
}

$questions = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
if (!is_array($questions)) $questions = [];

$results = file_exists($resultsFile) ? json_decode(file_get_contents($resultsFile), true) : [];
if (!is_array($results)) $results = [];

$examStatus = file_exists($examStatusFile) ? json_decode(file_get_contents($examStatusFile), true) : [];
if (!is_array($examStatus)) $examStatus = [];

$activeSessions = file_exists($activeSessionsFile) ? json_decode(file_get_contents($activeSessionsFile), true) : [];
if (!is_array($activeSessions)) $activeSessions = [];

$subjectsList = [];
foreach ($questions as $q) {
    $qSubjectId = $q['subjectId'] ?? '';
    if ($qSubjectId !== '' && (!isset($q['status']) || $q['status'] === 'approved')) {
        $subName = strtoupper(trim($qSubjectId));
        if (!in_array($subName, $subjectsList) && $subName !== '') {
            $subjectsList[] = $subName;
        }
    }
}

if (isset($_GET['toggle_subject'])) {
    $sub_toggle = strtoupper(trim($_GET['toggle_subject']));
    if (in_array($sub_toggle, $subjectsList)) {
        $current_st = isset($examStatus[$sub_toggle]) ? $examStatus[$sub_toggle] : 'closed';
        $new_st = ($current_st === 'open') ? 'closed' : 'open';
        $examStatus[$sub_toggle] = $new_st;
        file_put_contents($examStatusFile, json_encode($examStatus, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'new_status' => $new_st, 'subject' => $sub_toggle]);
            exit();
        }

        header("Location: admin_dashboard.php?success_toggle=" . $sub_toggle);
        exit();
    }
}

if (isset($_GET['kick_user'])) {
    $user_kick = trim($_GET['kick_user']);
    if (isset($activeSessions[$user_kick])) {
        unset($activeSessions[$user_kick]);
        file_put_contents($activeSessionsFile, json_encode($activeSessions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php?success_kick=" . urlencode($user_kick));
        exit();
    }
}

if (isset($_GET['delete_user'])) {
    $user_del = strtolower(trim($_GET['delete_user']));
    $current_admin = strtolower(trim($_SESSION['username']));

    if ($user_del === 'student') {
        header("Location: admin_dashboard.php?error_delete=default");
        exit();
    }

    if ($user_del === $current_admin) {
        header("Location: admin_dashboard.php?error_delete=self");
        exit();
    }

    if (isset($_SESSION['system_users']) && is_array($_SESSION['system_users'])) {
        foreach ($_SESSION['system_users'] as $index => $u) {
            if (strtolower(trim($u['user'])) === $user_del) {
                unset($_SESSION['system_users'][$index]);
                $_SESSION['system_users'] = array_values($_SESSION['system_users']);
                break;
            }
        }
        file_put_contents($usersFile, json_encode($_SESSION['system_users'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    header("Location: admin_dashboard.php?success_delete=1");
    exit();
}

if (isset($_GET['approve_q_id'])) {
    $id = (int)$_GET['approve_q_id'];
    if (isset($questions[$id])) {
        $questions[$id]['status'] = 'approved';
        file_put_contents($jsonFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php");
        exit();
    }
}

if (isset($_GET['delete_q_id'])) {
    $id = (int)$_GET['delete_q_id'];
    if (isset($questions[$id])) {
        unset($questions[$id]);
        $questions = array_values($questions);
        file_put_contents($jsonFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php");
        exit();
    }
}

if (isset($_GET['delete_res_id'])) {
    $id = (int)$_GET['delete_res_id'];
    if (isset($results[$id])) {
        unset($results[$id]);
        $results = array_values($results);
        file_put_contents($resultsFile, json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php");
        exit();
    }
}

if (isset($_POST['edit_question'])) {
    $id = (int)$_POST['question_index'];
    if (isset($questions[$id])) {
        $questions[$id] = [
            "subjectId" => trim($_POST['subjectId']),
            "question" => trim($_POST['question']),
            "A" => trim($_POST['A']),
            "B" => trim($_POST['B']),
            "C" => trim($_POST['C']),
            "D" => trim($_POST['D']),
            "correct" => strtoupper(trim($_POST['correct'])),
            "status" => isset($questions[$id]['status']) ? $questions[$id]['status'] : 'approved'
        ];
        file_put_contents($jsonFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php");
        exit();
    }
}

if (isset($_POST['add_user'])) {
    $new_user = trim($_POST['username']);
    $new_pass = trim($_POST['password']);
    $new_fullname = trim($_POST['fullname']);
    $new_email = trim($_POST['email']);
    $new_role = trim($_POST['role']);

    if (!empty($new_user) && !empty($new_pass)) {
        $exists = false;
        foreach ($_SESSION['system_users'] as $u) {
            if (strtolower(trim($u['user'])) === strtolower($new_user)) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            header("Location: admin_dashboard.php?error_add=exists");
            exit();
        } else {
            $_SESSION['system_users'][] = [
                'user' => $new_user,
                'pass' => $new_pass,
                'role' => $new_role,
                'email' => $new_email,
                'fullname' => $new_fullname
            ];
            file_put_contents($usersFile, json_encode($_SESSION['system_users'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            header("Location: admin_dashboard.php?success_add=1");
            exit();
        }
    }
}

$countTotalUsers = count($_SESSION['system_users']);
$countTotalQuestions = count($questions);
$countPendingQuestions = 0;
foreach ($questions as $q) {
    if (isset($q['status']) && $q['status'] === 'pending') {
        $countPendingQuestions++;
    }
}
$countActiveSessions = count($activeSessions);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Quản Trị Hệ Thống - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root{
            --bg:#f4f7fb;
            --card:#ffffff;
            --text:#0f172a;
            --muted:#64748b;
            --border:#e5eaf2;
            --primary:#2563eb;
            --primary2:#0ea5e9;
            --danger:#ef4444;
            --success:#10b981;
            --shadow:0 14px 40px rgba(15,23,42,.08);
        }
        *{box-sizing:border-box}
        body{
            font-family:'Segoe UI',sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37,99,235,.10), transparent 32%),
                radial-gradient(circle at top right, rgba(14,165,233,.08), transparent 28%),
                linear-gradient(135deg,#eef4ff 0%,#f8fafc 60%,#eef2ff 100%);
            margin:0;
            color:var(--text);
        }
        .navbar{
            position:sticky;
            top:0;
            z-index:50;
            background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
            color:#fff;
            padding:14px 26px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:14px;
            box-shadow:0 8px 24px rgba(15,23,42,.16);
        }
        .navbar h2{
            margin:0;
            font-size:16px;
            letter-spacing:.3px;
            font-weight:800;
        }
        .navbar a{
            color:#fff;
            text-decoration:none;
            font-weight:700;
            padding:10px 16px;
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.18);
            border-radius:12px;
        }
        .container{
            max-width:1260px;
            margin:28px auto;
            padding:0 18px 80px;
        }
        .header-card{
            background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%);
            padding:24px 24px;
            border:1px solid var(--border);
            border-radius:22px;
            margin-bottom:22px;
            box-shadow:var(--shadow);
        }
        .header-card h1{margin:0 0 8px;font-size:26px}
        .header-card p{margin:0;color:var(--muted);line-height:1.6}
        .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:16px;
            margin-bottom:28px;
        }
        .stat-card{
            background:var(--card);
            padding:18px 20px;
            border-radius:18px;
            box-shadow:var(--shadow);
            border:1px solid var(--border);
            border-top:4px solid #4f46e5;
        }
        .stat-card.u-cnt{border-top-color:#3b82f6}
        .stat-card.q-cnt{border-top-color:#10b981}
        .stat-card.p-cnt{border-top-color:#f59e0b}
        .stat-card.o-cnt{border-top-color:#ef4444}
        .stat-title{
            font-size:12px;
            color:var(--muted);
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:.7px;
            margin-bottom:8px;
        }
        .stat-value{font-size:30px;font-weight:900;color:var(--text)}
        .section-title{
            margin:28px 0 14px;
            font-size:18px;
            font-weight:900;
            color:#1e293b;
        }
        .q-table{
            width:100%;
            background:#fff;
            border-collapse:separate;
            border-spacing:0;
            border-radius:18px;
            overflow:hidden;
            margin-bottom:28px;
            border:1px solid var(--border);
            box-shadow:var(--shadow);
        }
        .q-table th,.q-table td{
            padding:14px 15px;
            border-bottom:1px solid var(--border);
            text-align:left;
            vertical-align:top;
        }
        .q-table th{
            background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
            color:#fff;
            font-size:13px;
            letter-spacing:.3px;
        }
        .q-table tr:hover td{background:#f8fbff}
        .btn-edit,.btn-approve,.btn-del,.btn-toggle-on,.btn-toggle-off,.btn-add-action{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            text-decoration:none;
            border:none;
            cursor:pointer;
            font-weight:800;
            border-radius:12px;
            transition:.22s ease;
            white-space:nowrap;
        }
        .btn-edit{background:#3b82f6;color:#fff;padding:8px 12px}
        .btn-approve{background:#10b981;color:#fff;padding:8px 12px}
        .btn-del{background:#ef4444;color:#fff;padding:8px 12px}
        .btn-toggle-on{background:#10b981;color:#fff;padding:8px 12px}
        .btn-toggle-off{background:#f59e0b;color:#fff;padding:8px 12px}
        .btn-add-action{
            background:linear-gradient(135deg,#4f46e5 0%,#2563eb 100%);
            color:#fff;
            padding:10px 16px;
            box-shadow:0 10px 20px rgba(79,70,229,.18);
        }
        .btn-edit:hover,.btn-approve:hover,.btn-del:hover,.btn-toggle-on:hover,.btn-toggle-off:hover,.btn-add-action:hover{
            transform:translateY(-2px);
            filter:brightness(1.03);
        }
        .role-badge{
            padding:5px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
            display:inline-block;
        }
        .role-student{background:#e0f2fe;color:#0369a1}
        .role-teacher{background:#fef3c7;color:#b45309}
        .role-admin{background:#fce7f3;color:#be185d}
        .status-open{background:#d1fae5;color:#065f46}
        .status-closed{background:#fee2e2;color:#991b1b}
        .modal{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(15,23,42,.55);
            justify-content:center;
            align-items:center;
            z-index:999;
            padding:16px;
            backdrop-filter:blur(6px);
        }
        .modal-content{
            background:#fff;
            padding:26px;
            border-radius:20px;
            width:560px;
            max-width:100%;
            box-shadow:0 30px 70px rgba(15,23,42,.24);
            border:1px solid rgba(255,255,255,.8);
        }
        .form-group{margin-bottom:12px}
        .form-group label{display:block;font-weight:700;margin-bottom:6px;color:#334155;font-size:14px}
        .form-group input,.form-group textarea,.form-group select{
            width:100%;
            padding:11px 12px;
            border:1px solid #cbd5e1;
            border-radius:12px;
            outline:none;
            font-size:14px;
            background:#fff;
        }
        .form-group input:focus,.form-group textarea:focus,.form-group select:focus{
            border-color:#60a5fa;
            box-shadow:0 0 0 4px rgba(96,165,250,.15);
        }
        .chat-fab{
            position:fixed;
            right:22px;
            bottom:22px;
            width:58px;
            height:58px;
            border:none;
            border-radius:50%;
            background:linear-gradient(135deg,#2563eb 0%,#0ea5e9 100%);
            color:#fff;
            font-size:24px;
            box-shadow:0 16px 34px rgba(37,99,235,.30);
            cursor:pointer;
            z-index:1000;
            transition:.25s ease;
        }
        .chat-fab:hover{transform:translateY(-3px) scale(1.04)}
        .chat-panel{
            position:fixed;
            right:22px;
            bottom:92px;
            width:330px;
            max-width:calc(100vw - 32px);
            height:420px;
            background:#fff;
            border:1px solid var(--border);
            border-radius:20px;
            box-shadow:0 24px 60px rgba(15,23,42,.22);
            overflow:hidden;
            z-index:999;
            display:none;
            flex-direction:column;
        }
        .chat-panel.open{display:flex}
        .chat-panel-head{
            background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
            color:#fff;
            padding:14px 16px;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        .chat-panel-head h4{margin:0;font-size:14px}
        .chat-close{
            border:none;
            background:rgba(255,255,255,.12);
            color:#fff;
            width:30px;
            height:30px;
            border-radius:50%;
            cursor:pointer;
            font-size:18px;
        }
        .chat-panel-body{
            flex:1;
            padding:14px;
            background:#f8fafc;
            overflow:auto;
        }
        .chat-panel-footer{
            padding:12px;
            border-top:1px solid var(--border);
            background:#fff;
            display:flex;
            gap:8px;
        }
        .chat-panel-footer input{
            flex:1;
            border:1px solid #cbd5e1;
            border-radius:12px;
            padding:10px 12px;
            outline:none;
        }
        .chat-panel-footer button{
            border:none;
            border-radius:12px;
            background:linear-gradient(135deg,#2563eb 0%,#0ea5e9 100%);
            color:#fff;
            font-weight:800;
            padding:0 14px;
        }
        .chat-note{
            font-size:12px;
            color:var(--muted);
            line-height:1.5;
            margin-top:10px;
        }
        @media (max-width: 900px){
            .navbar{padding:12px 16px;flex-direction:column;align-items:flex-start}
            .chat-panel{width:min(92vw,360px)}
            .q-table{display:block;overflow:auto}
        }
    </style>
</head>
<body>

<div class="navbar">
    <h2>🛠️ BẢNG ĐIỀU KHIỂN QUẢN TRỊ TỐI CAO (ADMIN)</h2>
    <a href="login.php">Đăng Xuất</a>
</div>

<div class="container">
    <div class="header-card">
        <h1>Xin chào Tổng Quản Trị: <?php echo htmlspecialchars($_SESSION['fullname']); ?></h1>
        <p>Hệ thống Quản Trị tối cao giúp giám sát Giáo Viên và Học Sinh</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card u-cnt">
            <div class="stat-title">Tổng Tài Khoản</div>
            <div class="stat-value"><?php echo $countTotalUsers; ?></div>
        </div>
        <div class="stat-card q-cnt">
            <div class="stat-title">Kho Câu Hỏi</div>
            <div class="stat-value"><?php echo $countTotalQuestions; ?></div>
        </div>
        <div class="stat-card p-cnt">
            <div class="stat-title">Câu Hỏi Chờ Duyệt</div>
            <div class="stat-value"><?php echo $countPendingQuestions; ?></div>
        </div>
        <div class="stat-card o-cnt">
            <div class="stat-title">Thí Sinh Đang Thi</div>
            <div class="stat-value"><?php echo $countActiveSessions; ?></div>
        </div>
    </div>

    <h3 class="section-title" style="color:#ef4444;">⚡ THEO DÕI THÍ SINH ĐANG LÀM BÀI TRỰC TUYẾN</h3>
    <table class="q-table" style="border-left:4px solid #ef4444;">
        <thead>
            <tr>
                <th width="20%">Tài khoản</th>
                <th width="25%">Họ và Tên</th>
                <th width="20%">Môn Thi</th>
                <th width="20%">Bắt Đầu</th>
                <th width="15%">Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($activeSessions) > 0): foreach ($activeSessions as $username => $sessionData): ?>
            <tr>
                <td><strong style="color:#b91c1c;"><?php echo htmlspecialchars($username); ?></strong></td>
                <td><?php echo htmlspecialchars($sessionData['fullname']); ?></td>
                <td><span class="role-badge role-teacher" style="text-transform:uppercase;"><?php echo htmlspecialchars($sessionData['subject']); ?></span></td>
                <td style="color:#64748b;font-size:13px;font-weight:600;"><?php echo htmlspecialchars($sessionData['start_time']); ?></td>
                <td>
                    <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?kick_user=<?php echo urlencode($username); ?>', 'Hủy phiên làm bài?', 'Trục xuất học sinh này ra khỏi phòng thi!')">Hủy phiên</a>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" style="text-align:center;color:#64748b;padding:20px;font-style:italic;">Hiện tại không có học sinh nào đang trong phòng thi.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 class="section-title" style="color:#f59e0b;">💡 DANH SÁCH MÔN HỌC</h3>
    <table class="q-table">
        <thead>
            <tr>
                <th width="20%">Mã Bộ Đề</th>
                <th width="25%">Số Câu Hỏi</th>
                <th width="25%">Trạng Thái</th>
                <th width="30%">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($subjectsList) > 0): foreach ($subjectsList as $sub):
                $countQ = 0;
                foreach($questions as $q) {
                    $qSub = $q['subjectId'] ?? '';
                    if(strtoupper(trim($qSub)) === $sub && (!isset($q['status']) || $q['status'] === 'approved')) {
                        $countQ++;
                    }
                }
                $st = isset($examStatus[$sub]) ? $examStatus[$sub] : 'closed';
            ?>
            <tr>
                <td><strong style="color:#4f46e5;font-size:16px;"><?php echo $sub; ?></strong></td>
                <td><span style="font-weight:600;"><?php echo $countQ; ?> câu hỏi</span></td>
                <td>
                    <?php if($st === 'open'): ?>
                        <span class="role-badge status-open">🟢 Đang mở thi</span>
                    <?php else: ?>
                        <span class="role-badge status-closed">🔴 Đang khóa</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($st === 'open'): ?>
                        <a href="admin_dashboard.php?toggle_subject=<?php echo urlencode($sub); ?>" class="btn-toggle-off btn-toggle-exam" data-subject="<?php echo htmlspecialchars($sub); ?>">🔒 Khóa đề</a>
                    <?php else: ?>
                        <a href="admin_dashboard.php?toggle_subject=<?php echo urlencode($sub); ?>" class="btn-toggle-on btn-toggle-exam" data-subject="<?php echo htmlspecialchars($sub); ?>">🚀 Mở thi</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px;">Không có môn học nào đủ điều kiện.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:5px;flex-wrap:wrap;">
        <h3 class="section-title" style="margin:0;">👥 QUẢN LÝ TÀI KHOẢN TRÊN HỆ THỐNG</h3>
        <button class="btn-add-action" onclick="openAddUserModal()">➕ Tạo Tài Khoản Mới</button>
    </div>
    <table class="q-table">
        <thead>
            <tr>
                <th>Tài khoản</th>
                <th>Họ và Tên</th>
                <th>Email</th>
                <th>Mật khẩu</th>
                <th>Phân loại</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($_SESSION['system_users']) && count($_SESSION['system_users']) > 0): foreach ($_SESSION['system_users'] as $u):
                $check_user = strtolower(trim($u['user']));
                $current_admin = strtolower(trim($_SESSION['username']));
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($u['user']); ?></strong></td>
                <td><?php echo htmlspecialchars(isset($u['fullname']) ? $u['fullname'] : 'Chưa cập nhật'); ?></td>
                <td><?php echo htmlspecialchars(isset($u['email']) ? $u['email'] : 'Không có'); ?></td>
                <td><span style="font-family:monospace;background:#fee2e2;color:#991b1b;padding:4px 8px;border-radius:6px;font-weight:bold;font-size:13px;"><?php echo htmlspecialchars(isset($u['pass']) ? $u['pass'] : 'Chưa rõ'); ?></span></td>
                <td>
                    <?php
                    $role = isset($u['role']) ? $u['role'] : 'student';
                    if($role === 'admin') echo '<span class="role-badge role-admin">Quản trị viên</span>';
                    elseif($role === 'teacher') echo '<span class="role-badge role-teacher">Giáo viên</span>';
                    else echo '<span class="role-badge role-student">Học sinh</span>';
                    ?>
                </td>
                <td>
                    <?php if ($check_user === $current_admin): ?>
                        <span style="color:#64748b;font-size:13px;font-style:italic;">Đang dùng</span>
                    <?php elseif ($check_user === 'student'): ?>
                        <span style="color:#f59e0b;font-size:13px;font-style:italic;font-weight:bold;">📌 Mặc định</span>
                    <?php else: ?>
                        <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_user=<?php echo urlencode($u['user']); ?>', 'Xóa tài khoản này?', 'Tài khoản này sẽ bị xóa vĩnh viễn khỏi hệ thống!')">Xóa</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;color:#64748b;padding:20px;">Không tìm thấy danh sách tài khoản.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 class="section-title" style="color:#dd6b20;">💡 ĐỀ THI ĐANG CHỜ DUYỆT</h3>
    <table class="q-table">
        <thead>
            <tr>
                <th width="10%">Môn</th>
                <th width="55%">Nội Dung Câu Hỏi</th>
                <th width="10%">Đáp Án</th>
                <th width="25%">Quyết Định</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $pending_count = 0;
            foreach ($questions as $index => $q):
                if (isset($q['status']) && $q['status'] === 'pending'):
                    $pending_count++;
            ?>
            <tr style="background:#fffaf0;">
                <td><strong style="color:#dd6b20;text-transform:uppercase;"><?php echo htmlspecialchars($q['subjectId'] ?? ''); ?></strong></td>
                <td><?php echo htmlspecialchars($q['question'] ?? ''); ?></td>
                <td style="font-weight:bold;color:#dd6b20;"><?php echo htmlspecialchars($q['correct'] ?? ''); ?></td>
                <td>
                    <a href="admin_dashboard.php?approve_q_id=<?php echo $index; ?>" class="btn-approve">Phê Duyệt</a>
                    <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_q_id=<?php echo $index; ?>', 'Từ chối câu hỏi?', 'Câu hỏi này sẽ bị gỡ bỏ!')">Xóa</a>
                </td>
            </tr>
            <?php endif; endforeach; if ($pending_count === 0): ?>
            <tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px;">Hiện tại không có câu hỏi nào chờ duyệt.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 class="section-title">📦 KHO CÂU HỎI CHÍNH THỨC</h3>
    <table class="q-table">
        <thead>
            <tr>
                <th width="10%">Môn</th>
                <th width="50%">Nội Dung Câu Hỏi</th>
                <th width="10%">Đáp Án</th>
                <th width="15%">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $approved_count = 0;
            foreach ($questions as $index => $q):
                if (!isset($q['status']) || $q['status'] === 'approved'):
                    $approved_count++;
            ?>
            <tr>
                <td><strong style="color:#4f46e5;text-transform:uppercase;"><?php echo htmlspecialchars($q['subjectId'] ?? ''); ?></strong></td>
                <td><?php echo htmlspecialchars($q['question'] ?? ''); ?></td>
                <td style="font-weight:bold;color:#10b981"><?php echo htmlspecialchars($q['correct'] ?? ''); ?></td>
                <td>
                    <button class="btn-edit" onclick="openEditModal(<?php echo $index; ?>, <?php echo htmlspecialchars(json_encode($q)); ?>)">Sửa</button>
                    <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_q_id=<?php echo $index; ?>', 'Xóa câu hỏi này?', 'Câu hỏi sẽ bị gỡ vĩnh viễn!')">Xóa</a>
                </td>
            </tr>
            <?php endif; endforeach; if ($approved_count === 0): ?>
            <tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px;">Kho dữ liệu câu hỏi trống.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 class="section-title">📊 QUẢN LÝ DANH SÁCH ĐIỂM THI CỦA SINH VIÊN</h3>
    <table class="q-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>MSSV / Thí Sinh</th>
                <th>Môn Thi</th>
                <th>Số Câu Đúng</th>
                <th>Điểm Số</th>
                <th>Thời Gian</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($results) > 0): foreach ($results as $index => $res): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><strong><?php echo htmlspecialchars($res['username']); ?></strong></td>
                <td style="text-transform:uppercase;font-weight:600;"><?php echo htmlspecialchars($res['subject']); ?></td>
                <td><?php echo htmlspecialchars($res['correct']); ?></td>
                <td style="font-weight:bold;color:#10b981"><?php echo htmlspecialchars($res['score']); ?>đ</td>
                <td style="color:#64748b;font-size:13px;"><?php echo htmlspecialchars($res['time']); ?></td>
                <td>
                    <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_res_id=<?php echo $index; ?>', 'Xóa điểm số này?', 'Kết quả sẽ bị hủy bỏ hoàn toàn!')">Xóa</a>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" style="text-align:center;color:#64748b;padding:20px;">Chưa có dữ liệu điểm sinh viên.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;color:#1e293b;">Chỉnh sửa câu hỏi</h3>
        <form method="POST">
            <input type="hidden" name="question_index" id="modal_index">
            <div class="form-group"><label>Môn học (subjectId)</label><input type="text" name="subjectId" id="modal_subject" required></div>
            <div class="form-group"><label>Câu hỏi</label><textarea name="question" id="modal_question" rows="3" required></textarea></div>
            <div class="form-group"><label>Đáp án A</label><input type="text" name="A" id="modal_a" required></div>
            <div class="form-group"><label>Đáp án B</label><input type="text" name="B" id="modal_b" required></div>
            <div class="form-group"><label>Đáp án C</label><input type="text" name="C" id="modal_c" required></div>
            <div class="form-group"><label>Đáp án D</label><input type="text" name="D" id="modal_d" required></div>
            <div class="form-group">
                <label>Đáp án đúng</label>
                <select name="correct" id="modal_correct">
                    <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                </select>
            </div>
            <div style="text-align:right;margin-top:20px;">
                <button type="button" onclick="closeEditModal()" style="padding:10px 15px;border-radius:10px;border:1px solid #cbd5e1;cursor:pointer;background:#fff;">Hủy</button>
                <button type="submit" name="edit_question" class="btn-edit" style="padding:10px 15px;">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<div id="addUserModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;color:#1e293b;">Thêm tài khoản mới vào hệ thống</h3>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Tài khoản (Username / MSSV)</label>
                <input type="text" name="username" placeholder="Nhập tên đăng nhập duy nhất..." required>
            </div>
            <div class="form-group">
                <label>Mật khẩu đăng nhập</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
            </div>
            <div class="form-group">
                <label>Họ và Tên</label>
                <input type="text" name="fullname" placeholder="Nhập họ và tên..." required>
            </div>
            <div class="form-group">
                <label>Email liên kết</label>
                <input type="email" name="email" placeholder="Nhập email..." required>
            </div>
            <div class="form-group">
                <label>Chức vụ (Quyền hạn)</label>
                <select name="role">
                    <option value="student">Sinh viên (Student)</option>
                    <option value="teacher">Giáo viên (Teacher)</option>
                    <option value="admin">Quản trị viên (Admin)</option>
                </select>
            </div>
            <div style="text-align:right;margin-top:20px;">
                <button type="button" onclick="closeAddUserModal()" style="padding:10px 15px;border-radius:10px;border:1px solid #cbd5e1;cursor:pointer;background:#fff;">Hủy</button>
                <button type="submit" name="add_user" class="btn-edit" style="padding:10px 15px;background:#4f46e5;">Xác nhận tạo</button>
            </div>
        </form>
    </div>
</div>

<a class="chat-fab" href="chat_box.php?role=admin" title="Mở chat box">💬</a>

<script>
function openEditModal(index, data) {
    document.getElementById('modal_index').value = index;
    document.getElementById('modal_subject').value = data.subjectId || '';
    document.getElementById('modal_question').value = data.question || '';
    document.getElementById('modal_a').value = data.A || '';
    document.getElementById('modal_b').value = data.B || '';
    document.getElementById('modal_c').value = data.C || '';
    document.getElementById('modal_d').value = data.D || '';
    document.getElementById('modal_correct').value = data.correct || 'A';
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
function openAddUserModal() { document.getElementById('addUserModal').style.display = 'flex'; }
function closeAddUserModal() { document.getElementById('addUserModal').style.display = 'none'; }

function confirmAction(event, targetUrl, titleText, detailText) {
    event.preventDefault();
    Swal.fire({
        title: titleText,
        text: detailText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Đồng ý thực hiện',
        cancelButtonText: 'Hủy bỏ'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = targetUrl;
    });
}

document.addEventListener('click', function(event) {
    const toggleBtn = event.target.closest('.btn-toggle-exam');
    if (!toggleBtn) return;

    event.preventDefault();
    const targetUrl = toggleBtn.getAttribute('href') + '&ajax=1';
    const subject = toggleBtn.getAttribute('data-subject');
    const row = toggleBtn.closest('tr');
    const statusCell = row.querySelector('td:nth-child(3)');
    const actionCell = row.querySelector('td:nth-child(4)');

    fetch(targetUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.new_status === 'open') {
                    statusCell.innerHTML = '<span class="role-badge status-open">🟢 Đang mở thi</span>';
                    actionCell.innerHTML = `<a href="admin_dashboard.php?toggle_subject=${encodeURIComponent(subject)}" class="btn-toggle-off btn-toggle-exam" data-subject="${subject}">🔒 Khóa đề</a>`;
                } else {
                    statusCell.innerHTML = '<span class="role-badge status-closed">🔴 Đang khóa</span>';
                    actionCell.innerHTML = `<a href="admin_dashboard.php?toggle_subject=${encodeURIComponent(subject)}" class="btn-toggle-on btn-toggle-exam" data-subject="${subject}">🚀 Mở thi</a>`;
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Đã cập nhật trạng thái đề ' + data.subject + '!',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Lỗi!', text: 'Không thể cập nhật trạng thái đề thi.' });
        });
});

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

<?php if(isset($_GET['error_delete']) && $_GET['error_delete'] === 'default'): ?>
Swal.fire({ icon: 'error', title: 'Lỗi Bảo Mật!', text: 'Không được phép xóa tài khoản [student] mặc định của hệ thống dưới mọi hình thức!', confirmButtonColor: '#ef4444' });
<?php endif; ?>

<?php if(isset($_GET['error_delete']) && $_GET['error_delete'] === 'self'): ?>
Swal.fire({ icon: 'error', title: 'Lỗi!', text: 'Bạn không thể tự xóa tài khoản của chính mình khi đang đăng nhập!', confirmButtonColor: '#ef4444' });
<?php endif; ?>

<?php if(isset($_GET['success_delete'])): ?>
Swal.fire({ icon: 'success', title: 'Thành công!', text: 'Đã xóa tài khoản ra khỏi hệ thống.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
<?php endif; ?>

<?php if(isset($_GET['success_toggle'])): ?>
Swal.fire({ icon: 'success', title: 'Đã cập nhật trạng thái đề <?php echo htmlspecialchars($_GET['success_toggle']); ?>!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
<?php endif; ?>

<?php if(isset($_GET['success_kick'])): ?>
Swal.fire({ icon: 'info', title: 'Đã hủy phiên thi của tài khoản <?php echo htmlspecialchars($_GET['success_kick']); ?>!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
<?php endif; ?>

<?php if(isset($_GET['error_add']) && $_GET['error_add'] === 'exists'): ?>
Swal.fire({ icon: 'error', title: 'Tạo thất bại!', text: 'Tên tài khoản đăng nhập này đã tồn tại trên hệ thống.', confirmButtonColor: '#ef4444' });
<?php endif; ?>

<?php if(isset($_GET['success_add'])): ?>
Swal.fire({ icon: 'success', title: 'Thành công!', text: 'Tài khoản thành viên mới đã được lưu trữ vĩnh viễn.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
<?php endif; ?>
</script>
</body>
</html>