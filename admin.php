<?php
include 'connect.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') { header("Location: login.php"); exit(); }

$msg = "";
if (isset($_POST['add_question'])) {
    $new_q = [
        'subject' => $_POST['subject'],
        'id_de' => 'de_01',
        'question' => trim($_POST['question_text']),
        'A' => trim($_POST['ans_A']),
        'B' => trim($_POST['ans_B']),
        'C' => trim($_POST['ans_C']),
        'D' => trim($_POST['ans_D']),
        'correct' => $_POST['correct_ans']
    ];
    $_SESSION['global_questions'][] = $new_q;
    $msg = "📥 Đã đồng bộ câu hỏi mới lên máy chủ thành công!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cổng quản trị dành cho Giáo viên</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .section-box { background: #fff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; }
        .badge-web { background-color: #3182ce; }
        .badge-net { background-color: #dd6b20; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <div class="header-bar">
            <h2><div class="brand-logo" style="display:inline-block;">NGUYÊN <span>HỘI</span></div> - Khung Quản Trị Giáo Viên</h2>
            <a href="login.php" class="btn btn-danger">Đăng xuất</a>
        </div>

        <?php if($msg != "") echo "<div class='alert alert-success'>$msg</div>"; ?>

        <div class="section-box">
            <h3 style="margin-bottom: 15px; color: #2d3748; border-left: 4px solid #4c51bf; padding-left: 10px;">📤 Thêm Câu Hỏi Đề Thi Mới</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Phân loại môn học</label>
                    <select name="subject">
                        <option value="web">Lập trình Web</option>
                        <option value="net">Mạng máy tính</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nội dung câu hỏi</label>
                    <textarea name="question_text" rows="2" placeholder="Nhập câu hỏi trắc nghiệm cần thêm..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Nội dung 4 đáp án lựa chọn</label>
                    <div class="grid-options">
                        <input type="text" name="ans_A" placeholder="Nội dung đáp án A..." required>
                        <input type="text" name="ans_B" placeholder="Nội dung đáp án B..." required>
                        <input type="text" name="ans_C" placeholder="Nội dung đáp án C..." required>
                        <input type="text" name="ans_D" placeholder="Nội dung đáp án D..." required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Thiết lập đáp án chính xác</label>
                    <select name="correct_ans">
                        <option value="A">Đáp án A</option>
                        <option value="B">Đáp án B</option>
                        <option value="C">Đáp án C</option>
                        <option value="D">Đáp án D</option>
                    </select>
                </div>
                <button type="submit" name="add_question" class="btn btn-primary" style="margin-top:5px;">🚀 Đăng Tải Lên Hệ Thống</button>
            </form>
        </div>

        <div class="section-box">
            <h3 style="margin-bottom: 12px; color: #2d3748; border-left: 4px solid #3182ce; padding-left: 10px;">📚 Kho Câu Hỏi Hệ Thống Đang Sở Hữu</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Môn</th>
                        <th>Nội dung câu hỏi</th>
                        <th style="width: 15%; text-align: center;">Đáp án đúng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['global_questions'] as $q): ?>
                    <tr>
                        <td>
                            <span class="badge <?php echo $q['subject']=='web'?'badge-web':'badge-net'; ?>">
                                <?php echo $q['subject']=='web'?'Web':'Mạng'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($q['question']); ?></td>
                        <td style="font-weight: bold; color: #38a169; text-align: center;"><?php echo $q['correct']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section-box">
            <h3 style="margin-bottom: 12px; color: #2d3748; border-left: 4px solid #38a169; padding-left: 10px;">📊 Bảng Quản Lý Điểm Số Toàn Bộ Sinh Viên</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tên Học Sinh / Thí Sinh</th>
                        <th>Môn Học Đã Thi</th>
                        <th>Điểm Số Đạt Được</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['global_history'] as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                        <td style="color: #e53e3e; font-weight: bold;"><?php echo $row['score']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
    <footer>Đồ án quản lý độc quyền thương hiệu <span class="brand-logo" style="font-size:12px;">NGUYÊN <span>HỘI</span></span> - © 2026</footer>
</body>
</html>