<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Kiểm tra quyền Admin (Đồng bộ biến với login.php)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

$jsonFile = 'data/questions.json';
$resultsFile = 'data/results.json';
$examStatusFile = 'data/exam_status.json';
$activeSessionsFile = 'data/active_sessions.json'; // CẬP NHẬT: File theo dõi thí sinh đang làm bài

$questions = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
if (!is_array($questions)) $questions = [];

$results = file_exists($resultsFile) ? json_decode(file_get_contents($resultsFile), true) : [];
if (!is_array($results)) $results = [];

$examStatus = file_exists($examStatusFile) ? json_decode(file_get_contents($examStatusFile), true) : [];
if (!is_array($examStatus)) $examStatus = [];

$activeSessions = file_exists($activeSessionsFile) ? json_decode(file_get_contents($activeSessionsFile), true) : [];
if (!is_array($activeSessions)) $activeSessions = [];

// TỰ ĐỘNG GOM CÁC MÔN HỌC ĐÃ DUYỆT ĐỂ LẬP DANH SÁCH ĐỀ THI
$subjectsList = [];
foreach ($questions as $q) {
    if (!isset($q['status']) || $q['status'] === 'approved') {
        $subName = strtoupper(trim($q['subjectId']));
        if (!in_array($subName, $subjectsList) && $subName !== '') {
            $subjectsList[] = $subName;
        }
    }
}

// 0. THAO TÁC ĐÓNG / MỞ ĐỀ THI CHO HỌC SINH
if (isset($_GET['toggle_subject'])) {
    $sub_toggle = strtoupper(trim($_GET['toggle_subject']));
    if (in_array($sub_toggle, $subjectsList)) {
        $current_st = isset($examStatus[$sub_toggle]) ? $examStatus[$sub_toggle] : 'closed';
        $examStatus[$sub_toggle] = ($current_st === 'open') ? 'closed' : 'open';
        file_put_contents($examStatusFile, json_encode($examStatus, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php?success_toggle=" . $sub_toggle); exit();
    }
}

// CẬP NHẬT: THAO TÁC KICK / ĐÓNG PHIÊN LÀM BÀI CỦA THÍ SINH
if (isset($_GET['kick_user'])) {
    $user_kick = trim($_GET['kick_user']);
    if (isset($activeSessions[$user_kick])) {
        unset($activeSessions[$user_kick]);
        file_put_contents($activeSessionsFile, json_encode($activeSessions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php?success_kick=" . urlencode($user_kick)); exit();
    }
}

// 1. THAO TÁC XÓA TÀI KHOẢN
if (isset($_GET['delete_user'])) {
    $user_del = trim($_GET['delete_user']);
    if ($user_del !== $_SESSION['username']) { 
        if (isset($_SESSION['system_users']) && is_array($_SESSION['system_users'])) {
            foreach ($_SESSION['system_users'] as $index => $u) {
                if ($u['user'] === $user_del) {
                    unset($_SESSION['system_users'][$index]);
                    $_SESSION['system_users'] = array_values($_SESSION['system_users']);
                    break;
                }
            }
        }
        header("Location: admin_dashboard.php"); exit();
    }
}

// 2. THAO TÁC PHÊ DUYỆT ĐỀ THI
if (isset($_GET['approve_q_id'])) {
    $id = (int)$_GET['approve_q_id'];
    if (isset($questions[$id])) {
        $questions[$id]['status'] = 'approved'; 
        file_put_contents($jsonFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php"); exit();
    }
}

// 3. THAO TÁC XÓA CÂU HỎI ĐỀ THI
if (isset($_GET['delete_q_id'])) {
    $id = (int)$_GET['delete_q_id'];
    if (isset($questions[$id])) {
        unset($questions[$id]);
        $questions = array_values($questions);
        file_put_contents($jsonFile, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php"); exit();
    }
}

// 4. THAO TÁC XÓA ĐIỂM THI
if (isset($_GET['delete_res_id'])) {
    $id = (int)$_GET['delete_res_id'];
    if (isset($results[$id])) {
        unset($results[$id]);
        $results = array_values($results);
        file_put_contents($resultsFile, json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: admin_dashboard.php"); exit();
    }
}

// 5. THAO TÁC SỬA CÂU HỎI
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
        header("Location: admin_dashboard.php"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Quản Trị Hệ Thống - Admin tối cao</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; padding: 0; color: #1e293b; }
        .navbar { background: #0f172a; color: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ef4444; text-decoration: none; font-weight: bold; padding: 8px 16px; background: rgba(239, 68, 68, 0.1); border-radius: 6px; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .header-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; border-left: 6px solid #4f46e5; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .q-table { width: 100%; background: white; border-collapse: collapse; border-radius: 12px; overflow: hidden; margin-bottom: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .q-table th, .q-table td { padding: 14px 15px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .q-table th { background: #1e293b; color: white; font-size: 14px; }
        .btn-edit { background: #3b82f6; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration:none; font-size:13px; font-weight:600; }
        .btn-approve { background: #10b981; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight:600; display: inline-block; }
        .btn-del { background: #ef4444; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight:600; display: inline-block; }
        .btn-toggle-on { background: #10b981; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size:13px; font-weight:600; }
        .btn-toggle-off { background: #f59e0b; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size:13px; font-weight:600; }
        .role-badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: bold; }
        .role-student { background: #e0f2fe; color: #0369a1; }
        .role-teacher { background: #fef3c7; color: #b45309; }
        .role-admin { background: #fce7f3; color: #be185d; }
        .status-open { background: #d1fae5; color: #065f46; }
        .status-closed { background: #fee2e2; color: #991b1b; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 999; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 500px; max-width: 90%; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 4px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
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
            <p>Hệ thống phân quyền tối cao: Phê duyệt đề, điều phối phòng thi và giám sát thí sinh trực tuyến.</p>
        </div>

        <h3 style="color:#ef4444;">⚡ THEO DÕI THÍ SINH ĐANG LÀM BÀI TRỰC TUYẾN</h3>
        <table class="q-table" style="border-left: 4px solid #ef4444;">
            <thead>
                <tr style="background:#b91c1c;">
                    <th width="20%">Tài khoản (Username)</th>
                    <th width="25%">Họ và Tên Thí Sinh</th>
                    <th width="20%">Bài Tập / Môn Thi</th>
                    <th width="20%">Thời Gian Bắt Đầu</th>
                    <th width="15%">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (count($activeSessions) > 0): 
                    foreach ($activeSessions as $username => $sessionData):
                ?>
                <tr style="background: #fff5f5;">
                    <td><strong style="color:#b91c1c;"><?php echo htmlspecialchars($username); ?></strong></td>
                    <td><?php echo htmlspecialchars($sessionData['fullname']); ?></td>
                    <td><span class="role-badge role-teacher" style="text-transform:uppercase;"><?php echo htmlspecialchars($sessionData['subject']); ?></span></td>
                    <td style="color:#64748b; font-size:13px; font-weight:600;"><?php echo htmlspecialchars($sessionData['start_time']); ?></td>
                    <td>
                        <a href="#" class="btn-del" style="padding: 4px 8px; font-size:12px;" onclick="confirmAction(event, 'admin_dashboard.php?kick_user=<?php echo urlencode($username); ?>', 'Hủy phiên làm bài?', 'Trục xuất học sinh này ra khỏi phòng thi, phiên làm bài hiện tại sẽ bị xóa tĩnh!')">Hủy phiên</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center; color:#64748b; padding:20px; font-style:italic;">Hiện tại không có học sinh nào đang trong phòng thi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3 style="color:#4f46e5;">🎯 QUẢN LÝ KÍCH HOẠT ĐỀ THI TRỰC TUYẾN</h3>
        <table class="q-table">
            <thead>
                <tr>
                    <th width="20%">Mã Bộ Đề (Môn Học)</th>
                    <th width="25%">Số Câu Hỏi Hiện Có</th>
                    <th width="25%">Trạng Thái Trên Trang Thi</th>
                    <th width="30%">Thao Tác Cấp Phép</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (count($subjectsList) > 0): 
                    foreach ($subjectsList as $sub):
                        $countQ = 0;
                        foreach($questions as $q) {
                            if(strtoupper(trim($q['subjectId'])) === $sub && (!isset($q['status']) || $q['status'] === 'approved')) { $countQ++; }
                        }
                        $st = isset($examStatus[$sub]) ? $examStatus[$sub] : 'closed';
                ?>
                <tr>
                    <td><strong style="color:#4f46e5; font-size:16px;"><?php echo $sub; ?></strong></td>
                    <td><span style="font-weight:600;"><?php echo $countQ; ?> câu hỏi</span></td>
                    <td>
                        <?php if($st === 'open'): ?>
                            <span class="role-badge status-open">🟢 Đang mở thi (Học sinh nhìn thấy)</span>
                        <?php else: ?>
                            <span class="role-badge status-closed">🔴 Đang khóa (Học sinh không thấy)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($st === 'open'): ?>
                            <a href="admin_dashboard.php?toggle_subject=<?php echo urlencode($sub); ?>" class="btn-toggle-off">🔒 Khóa đề thi</a>
                        <?php else: ?>
                            <a href="admin_dashboard.php?toggle_subject=<?php echo urlencode($sub); ?>" class="btn-toggle-on">🚀 Mở cho học sinh thi</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" style="text-align:center; color:#64748b; padding:20px;">Không có môn học nào đủ điều kiện.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3>👥 QUẢN LÝ TÀI KHOẢN TRÊN HỆ THỐNG</h3>
        <table class="q-table">
            <thead>
                <tr>
                    <th>Tài khoản (Username)</th>
                    <th>Họ và Tên</th>
                    <th>Email liên kết</th>
                    <th>Phân loại chức vụ</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (isset($_SESSION['system_users']) && count($_SESSION['system_users']) > 0): 
                    foreach ($_SESSION['system_users'] as $u): 
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($u['user']); ?></strong></td>
                    <td><?php echo htmlspecialchars(isset($u['fullname']) ? $u['fullname'] : 'Chưa cập nhật'); ?></td>
                    <td><?php echo htmlspecialchars(isset($u['email']) ? $u['email'] : 'Không có'); ?></td>
                    <td>
                        <?php 
                            $role = isset($u['role']) ? $u['role'] : 'student';
                            if($role === 'admin') echo '<span class="role-badge role-admin">Quản trị viên</span>';
                            elseif($role === 'teacher') echo '<span class="role-badge role-teacher">Giáo viên</span>';
                            else echo '<span class="role-badge role-student">Sinh viên</span>';
                        ?>
                    </td>
                    <td>
                        <?php if ($u['user'] !== $_SESSION['username']): ?>
                            <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_user=<?php echo urlencode($u['user']); ?>', 'Xóa tài khoản này?', 'Tài khoản này sẽ bị xóa vĩnh viễn khỏi hệ thống!')">Xóa Tài Khoản</a>
                        <?php else: ?>
                            <span style="color:#64748b; font-size:13px; font-style:italic;">Đang dùng</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center; color:#64748b; padding:20px;">Không tìm thấy danh sách tài khoản.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3 style="color:#dd6b20;">⏳ ĐỀ THI ĐANG CHỜ DUYỆT (TỪ GIÁO VIÊN VỪA UP)</h3>
        <table class="q-table">
            <thead>
                <tr>
                    <th width="10%">Môn</th>
                    <th width="55%">Nội Dung Câu Hỏi</th>
                    <th width="10%">Đáp Án Đúng</th>
                    <th width="25%">Quyết Định Kiểm Duyệt</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $pending_count = 0;
                foreach ($questions as $index => $q): 
                    if (isset($q['status']) && $q['status'] === 'pending'):
                        $pending_count++;
                ?>
                <tr style="background: #fffaf0;">
                    <td><strong style="color:#dd6b20; text-transform:uppercase;"><?php echo htmlspecialchars($q['subjectId']); ?></strong></td>
                    <td><?php echo htmlspecialchars($q['question']); ?></td>
                    <td style="font-weight:bold; color:#dd6b20;"><?php echo htmlspecialchars($q['correct']); ?></td>
                    <td>
                        <a href="admin_dashboard.php?approve_q_id=<?php echo $index; ?>" class="btn-approve">Phê Duyệt Khởi Chạy</a>
                        <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_q_id=<?php echo $index; ?>', 'Từ chối câu hỏi?', 'Câu hỏi này sẽ bị gỡ bỏ và không được duyệt vào hệ thống!')">Xóa bỏ</a>
                    </td>
                </tr>
                <?php endif; endforeach; if ($pending_count === 0): ?>
                <tr><td colspan="4" style="text-align:center; color:#64748b; padding:20px;">Hiện tại không có câu hỏi nào xếp hàng chờ duyệt.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3>📦 KHO CÂU HỎI CHÍNH THỨC TRÊN HỆ THỐNG</h3>
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
                    <td><strong style="color:#4f46e5; text-transform:uppercase;"><?php echo htmlspecialchars($q['subjectId']); ?></strong></td>
                    <td><?php echo htmlspecialchars($q['question']); ?></td>
                    <td style="font-weight:bold; color:#10b981"><?php echo htmlspecialchars($q['correct']); ?></td>
                    <td>
                        <button class="btn-edit" onclick="openEditModal(<?php echo $index; ?>, <?php echo htmlspecialchars(json_encode($q)); ?>)">Sửa</button>
                        <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_q_id=<?php echo $index; ?>', 'Xóa câu hỏi này?', 'Câu hỏi này sẽ bị gỡ vĩnh viễn khỏi ngân hàng đề thi!')">Xóa</a>
                    </td>
                </tr>
                <?php endif; endforeach; if ($approved_count === 0): ?>
                <tr><td colspan="4" style="text-align:center; color:#64748b; padding:20px;">Kho dữ liệu câu hỏi trống.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3>📊 QUẢN LÝ DANH SÁCH ĐIỂM THI CỦA SINH VIÊN</h3>
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
                    <td style="text-transform:uppercase; font-weight:600;"><?php echo htmlspecialchars($res['subject']); ?></td>
                    <td><?php echo htmlspecialchars($res['correct']); ?></td>
                    <td style="font-weight:bold; color:#10b981"><?php echo htmlspecialchars($res['score']); ?>đ</td>
                    <td style="color:#64748b; font-size:13px;"><?php echo htmlspecialchars($res['time']); ?></td>
                    <td>
                        <a href="#" class="btn-del" onclick="confirmAction(event, 'admin_dashboard.php?delete_res_id=<?php echo $index; ?>', 'Xóa điểm số này?', 'Kết quả thi của thí sinh này sẽ bị hủy bỏ hoàn toàn!')">Xóa Điểm</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align:center; color:#64748b; padding:20px;">Chưa có dữ liệu điểm sinh viên.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0; color:#1e293b;">Chỉnh sửa câu hỏi</h3>
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
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeEditModal()" style="padding:8px 15px; border-radius:6px; border:1px solid #cbd5e1; cursor:pointer">Hủy</button>
                    <button type="submit" name="edit_question" class="btn-edit" style="padding:8px 15px;">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(index, data) {
            document.getElementById('modal_index').value = index;
            document.getElementById('modal_subject').value = data.subjectId;
            document.getElementById('modal_question').value = data.question;
            document.getElementById('modal_a').value = data.A;
            document.getElementById('modal_b').value = data.B;
            document.getElementById('modal_c').value = data.C;
            document.getElementById('modal_d').value = data.D;
            document.getElementById('modal_correct').value = data.correct;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

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
                cancelButtonText: 'Hủy bỏ',
                borderRadius: '12px'
            }).then((result) => {
                if (result.isConfirmed) { window.location.href = targetUrl; }
            });
        }

        <?php if(isset($_GET['success_toggle'])): ?>
            Swal.fire({ icon: 'success', title: 'Đã cập nhật trạng thái đề <?php echo htmlspecialchars($_GET['success_toggle']); ?>!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
        <?php endif; ?>

        <?php if(isset($_GET['success_kick'])): ?>
            Swal.fire({ icon: 'info', title: 'Đã hủy phiên thi của tài khoản <?php echo htmlspecialchars($_GET['success_kick']); ?>!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
        <?php endif; ?>
    </script>
</body>
</html>