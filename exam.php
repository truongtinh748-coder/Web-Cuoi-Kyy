<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_logged'])) {
    header("Location: login.php");
    exit();
}


$username = $_SESSION['user_logged'];
$subject = isset($_GET['subject']) ? strtolower(trim($_GET['subject'])) : 'toan';
$_SESSION['current_subject'] = $subject;

$questions = [];
$examLocked = false;


$examStatusFile = 'data/exam_status.json';
$examStatus = file_exists($examStatusFile) ? json_decode(file_get_contents($examStatusFile), true) : [];
if (!is_array($examStatus)) $examStatus = [];
$upperSubject = strtoupper($subject);


if (!isset($examStatus[$upperSubject]) || $examStatus[$upperSubject] !== 'open') {
    $examLocked = true;
}


$activeSessionsFile = 'data/active_sessions.json';
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}
$activeSessions = file_exists($activeSessionsFile) ? json_decode(file_get_contents($activeSessionsFile), true) : [];
if (!is_array($activeSessions)) $activeSessions = [];

function saveActiveExamSession($username, $fullname, $subject, $startTime, $filePath) {
    $activeSessions = [];
if (file_exists($filePath)) {
$activeSessions = json_decode(file_get_contents($filePath), true);
if (!is_array($activeSessions)) $activeSessions = [];
}

$activeSessions[$username] = [
'fullname' => $fullname,
'subject' => strtoupper($subject),
'start_time' => $startTime,
'session_id' => session_id(),
'last_update' => time()
];

file_put_contents($filePath, json_encode($activeSessions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}


if (!$examLocked) {
$jsonFile = 'data/questions.json';
if (file_exists($jsonFile)) {
$fileContent = file_get_contents($jsonFile);
if (!empty($fileContent)) {
$all_questions = json_decode($fileContent, true);
if (is_array($all_questions)) {
$easy_questions = [];
$medium_questions = [];
$hard_questions = [];


foreach ($all_questions as $q) {
$q_subject = isset($q['subjectId']) ? strtolower(trim($q['subjectId'])) : '';
if ($q_subject === $subject && (!isset($q['status']) || $q['status'] === 'approved' || $q['status'] === 'Approved')) {
$difficulty = isset($q['difficulty']) ? strtolower($q['difficulty']) : 'medium';
if ($difficulty === 'easy') {
$easy_questions[] = $q;
} elseif ($difficulty === 'hard') {
$hard_questions[] = $q;
} else {
$medium_questions[] = $q;
}
}
}


shuffle($easy_questions);
shuffle($medium_questions);
shuffle($hard_questions);


$totalQuestions = 25;
$hardCount = (int) ceil($totalQuestions * 0.30);
$easyMediumCount = $totalQuestions - $hardCount;
$easyCount = (int) floor($easyMediumCount * 0.5);
$mediumCount = $easyMediumCount - $easyCount;


$selected_hard = array_slice($hard_questions, 0, min($hardCount, count($hard_questions)));
$selected_easy = array_slice($easy_questions, 0, min($easyCount, count($easy_questions)));
$selected_medium = array_slice($medium_questions, 0, min($mediumCount, count($medium_questions)));


 $questions = array_merge($selected_easy, $selected_medium, $selected_hard);
shuffle($questions);
 $questions = array_slice($questions, 0, $totalQuestions);


 $_SESSION['current_exam_questions'] = $questions;
$_SESSION['exam_stats'] = [
'easy' => count($selected_easy),
'medium' => count($selected_medium),
'hard' => count($selected_hard),
'total' => count($questions)
];
}
  }
 }
}


if (!$examLocked && count($questions) > 0 && !isset($_SESSION['exam_start_time'])) {
 $_SESSION['exam_start_time'] = date('Y-m-d H:i:s');
 $_SESSION['exam_tab_switch'] = 0;
 saveActiveExamSession($username, $_SESSION['fullname'] ?? $username, $subject, $_SESSION['exam_start_time'], $activeSessionsFile);
}

if (!$examLocked && count($questions) > 0 && isset($_SESSION['exam_start_time'])) {
 saveActiveExamSession($username, $_SESSION['fullname'] ?? $username, $subject, $_SESSION['exam_start_time'], $activeSessionsFile);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Phòng Thi Trực Tuyến - Môn <?php echo htmlspecialchars(strtoupper($subject)); ?></title>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
 <style>
  :root{
--primary:#0ea5e9;
--primary-dark:#0284c7;
--danger:#ef4444;
--bg:linear-gradient(135deg,#f0f9ff 0%,#e0e7ff 100%);
--surface:#ffffff;
--text-main:#1e293b;
--text-sub:#475569;
--text-muted:#64748b;
--border:#e2e8f0;
--shadow-md:0 8px 24px rgba(15,23,42,0.06);
  }
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,-apple-system,sans-serif}
  body{background:var(--bg);color:var(--text-main);min-height:100vh;padding-bottom:60px}


  .top-sticky-bar{
position:sticky;top:0;z-index:100;
background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
padding:14px 40px;display:flex;justify-content:space-between;align-items:center;
box-shadow:0 4px 16px rgba(15,23,42,0.15);color:#fff
  }
  .title-area h2{
font-size:16px;font-weight:800;letter-spacing:.3px;
background:linear-gradient(90deg,#38bdf8 0%,#818cf8 100%);
-webkit-background-clip:text;background-clip:text;color:transparent;
text-transform:uppercase
  }
  .right-nav-area{display:flex;align-items:center;gap:18px}
  .timer-box{
background:linear-gradient(135deg,#fef2f2 0%,#fee2e2 100%);
border:1px solid #fca5a5;color:#b91c1c;font-weight:800;font-size:15px;
padding:8px 14px;border-radius:10px;display:flex;align-items:center;gap:6px
  }
  .user-info{font-size:14px;font-weight:600}
  .btn-back{
background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
color:#fff;padding:8px 16px;border-radius:10px;font-weight:700;
font-size:13px;cursor:pointer;border:none
  }


  .exam-content{max-width:900px;width:94%;margin:32px auto}
  .question-card{
background:var(--surface);border:1px solid var(--border);border-radius:16px;
padding:26px 28px;margin-bottom:22px;box-shadow:var(--shadow-md);
position:relative;overflow:hidden;
transition:transform .25s ease, box-shadow .25s ease
  }
  .question-card:hover{
transform:translateY(-7px);
box-shadow:0 18px 36px rgba(15,23,42,0.12)
  }
  .difficulty-badge{
position:absolute;top:16px;right:16px;padding:4px 12px;border-radius:20px;
font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px
  }
  .difficulty-badge.easy{background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#166534;border:1px solid #86efac}
  .difficulty-badge.medium{background:linear-gradient(135deg,#fffbeb,#fef3c7);color:#92400e;border:1px solid #fcd34d}
  .difficulty-badge.hard{background:linear-gradient(135deg,#fef2f2,#fee2e2);color:#991b1b;border:1px solid #fca5a5}


  .question-card p{
font-weight:700;font-size:16px;margin-bottom:16px;color:var(--text-main);
line-height:1.5;padding-right:120px
  }
  .question-card label{
display:block;padding:12px 16px;background:#f8fafc;border:1.5px solid var(--border);
border-radius:12px;margin-bottom:10px;cursor:pointer;transition:.2s ease;
font-size:14.5px;color:var(--text-sub)
  }
  .question-card label:hover{
background:linear-gradient(135deg,#f1f5f9,#e2e8f0);
border-color:var(--primary);
color:var(--text-main)
  }
  .question-card input[type="radio"]{
margin-right:12px;transform:scale(1.15);cursor:pointer
  }


  .lock-card{text-align:center;padding:50px 20px;border-top:5px solid var(--danger)}
  .lock-card .icon{font-size:64px;margin-bottom:10px}
  .lock-card h2{color:var(--danger);margin:10px 0;font-size:24px;font-weight:800}
  .lock-card p{color:var(--text-muted);font-weight:500;font-size:15px;margin-bottom:26px}
  .btn-back-secondary{
display:inline-block;padding:12px 26px;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
color:#fff;text-decoration:none;border-radius:12px;font-weight:700
  }


  .exam-stats{
background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid var(--border);
border-radius:12px;padding:14px 18px;margin-bottom:24px;display:flex;gap:18px;flex-wrap:wrap;justify-content:center
  }
  .stat-badge{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600}
  .stat-badge.easy{color:#166534}
  .stat-badge.medium{color:#92400e}
  .stat-badge.hard{color:#991b1b}


  .btn-submit-exam{
background:linear-gradient(135deg,#0ea5e9 0%,#2563eb 100%);
color:#fff;
border:none;
width:100%;
padding:16px;
border-radius:14px;
font-weight:800;
cursor:pointer;
font-size:15px;
text-transform:uppercase;
letter-spacing:.6px;
box-shadow:0 14px 28px rgba(37,99,235,.22);
transition:transform .25s ease, box-shadow .25s ease, filter .25s ease;
position:relative;
overflow:hidden;
animation:pulseSubmit 1.8s ease-in-out infinite;
  }
  .btn-submit-exam::before,
  .swal2-confirm.exam-swal-confirm::before,
  .swal2-cancel.exam-swal-cancel::before{
content:'';
position:absolute;
top:0;
left:-120%;
width:70%;
height:100%;
background:linear-gradient(120deg, transparent, rgba(255,255,255,.35), transparent);
transform:skewX(-20deg);
transition:left .65s ease;
  }
  .btn-submit-exam:hover::before,
  .swal2-confirm.exam-swal-confirm:hover::before,
  .swal2-cancel.exam-swal-cancel:hover::before{
left:140%;
  }
  .btn-submit-exam:hover{
transform:translateY(-3px) scale(1.01);
box-shadow:0 18px 34px rgba(37,99,235,.30);
filter:brightness(1.03);
  }
  .btn-submit-exam:active{transform:translateY(0) scale(.99)}
  @keyframes pulseSubmit{
0%,100%{box-shadow:0 14px 28px rgba(37,99,235,.22);}
50%{box-shadow:0 14px 36px rgba(37,99,235,.34);}
  }


  .swal2-container{backdrop-filter:blur(10px);}
  .swal2-popup.exam-swal{
width:min(620px, 92vw) !important;
border-radius:30px !important;
padding:0 !important;
overflow:hidden !important;
background:rgba(255,255,255,.82) !important;
border:1px solid rgba(255,255,255,.6) !important;
box-shadow:0 30px 80px rgba(15,23,42,.28) !important;
backdrop-filter: blur(18px);
  }
  .swal2-title.exam-swal-title{display:none !important}
  .swal2-html-container.exam-swal-html{margin:0 !important;padding:0 !important;overflow:visible !important;text-align:left !important;color:#334155 !important;}
  .exam-swal-wrap{
background:
 radial-gradient(circle at top left, rgba(56,189,248,.18), transparent 30%),
 radial-gradient(circle at top right, rgba(99,102,241,.15), transparent 28%),
 linear-gradient(180deg, rgba(255,255,255,.96), rgba(248,250,252,.96));
  }
  .exam-swal-head{
position:relative;
padding:24px 24px 20px;
display:flex;
gap:16px;
align-items:center;
border-bottom:1px solid rgba(148,163,184,.18);
  }
  .exam-swal-head::after{
content:'';
position:absolute;
left:24px;
right:24px;
bottom:-1px;
height:2px;
background:linear-gradient(90deg,#38bdf8 0%,#6366f1 50%,#ef4444 100%);
border-radius:999px;
  }
  .exam-swal-icon{
width:64px;height:64px;min-width:64px;border-radius:20px;
display:flex;align-items:center;justify-content:center;font-size:28px;color:#fff;
background:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%);
box-shadow:0 14px 28px rgba(239,68,68,.22);
transform:rotate(-6deg);
  }
  .exam-swal-head-text h3{margin:0;font-size:22px;font-weight:900;color:#0f172a;letter-spacing:-.3px}
  .exam-swal-head-text p{margin:5px 0 0;font-size:13px;color:#64748b}
  .exam-swal-body{padding:20px 24px 8px}
  .exam-progress{
margin:14px 0 18px;
height:12px;
border-radius:999px;
background:rgba(226,232,240,.9);
overflow:hidden;
box-shadow:inset 0 1px 2px rgba(15,23,42,.08);
  }
  .exam-progress-bar{
height:100%;
border-radius:999px;
background:linear-gradient(90deg,#38bdf8 0%,#6366f1 55%,#a855f7 100%);
box-shadow:0 8px 20px rgba(99,102,241,.25);
  }
  .exam-summary{
display:grid;
grid-template-columns:repeat(2,minmax(0,1fr));
gap:12px;
margin-top:14px;
  }
  .exam-box{
border:1px solid rgba(226,232,240,.95);
border-radius:18px;
padding:15px 16px;
background:rgba(248,250,252,.9);
box-shadow:0 10px 24px rgba(15,23,42,.05);
  }
  .exam-box .label{
display:block;
font-size:12px;
font-weight:800;
color:#64748b;
margin-bottom:5px;
text-transform:uppercase;
letter-spacing:.7px;
  }
  .exam-box .value{font-size:20px;font-weight:900;color:#0f172a}
  .exam-box.warn{
background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);
border-color:#fdba74;
  }
  .exam-box.warn .value{color:#c2410c}
  .exam-swal-note{
margin-top:14px;
padding:12px 14px;
border-left:4px solid #ef4444;
background:linear-gradient(135deg,#fef2f2 0%,#fff1f2 100%);
border-radius:14px;
color:#991b1b;
font-size:13px;
box-shadow:0 10px 22px rgba(239,68,68,.08);
  }
  .swal2-actions.exam-swal-actions{
margin:0 !important;
padding:18px 24px 24px !important;
gap:10px !important;
justify-content:flex-end !important;
  }
  .swal2-confirm.exam-swal-confirm,
  .swal2-cancel.exam-swal-cancel{
border:none !important;
border-radius:14px !important;
padding:12px 22px !important;
font-weight:900 !important;
min-width:140px;
transition:transform .22s ease, box-shadow .22s ease, filter .22s ease !important;
position:relative;
overflow:hidden;
  }
  .swal2-confirm.exam-swal-confirm{
background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%) !important;
box-shadow:0 14px 26px rgba(239,68,68,.24) !important;
animation:pulseDanger 1.8s ease-in-out infinite;
  }
  .swal2-cancel.exam-swal-cancel{
background:linear-gradient(135deg,#0ea5e9 0%,#2563eb 100%) !important;
box-shadow:0 14px 26px rgba(14,165,233,.18) !important;
animation:pulseInfo 1.8s ease-in-out infinite;
  }
  .swal2-confirm.exam-swal-confirm:hover,
  .swal2-cancel.exam-swal-cancel:hover{
transform:translateY(-2px) scale(1.03);
filter:brightness(1.05);
  }
  .swal2-confirm.exam-swal-confirm:active,
  .swal2-cancel.exam-swal-cancel:active{
transform:translateY(0) scale(.99);
  }
  @keyframes pulseDanger{
0%,100%{box-shadow:0 14px 26px rgba(239,68,68,.24);}
50%{box-shadow:0 14px 36px rgba(239,68,68,.36);}
  }
  @keyframes pulseInfo{
0%,100%{box-shadow:0 14px 26px rgba(14,165,233,.18);}
50%{box-shadow:0 14px 36px rgba(14,165,233,.30);}
  }


  .lock-card{text-align:center;padding:50px 20px;border-top:5px solid var(--danger)}
  .lock-card .icon{font-size:64px;margin-bottom:10px}
  .lock-card h2{color:var(--danger);margin:10px 0;font-size:24px;font-weight:800}
  .lock-card p{color:var(--text-muted);font-weight:500;font-size:15px;margin-bottom:26px}
  .btn-back-secondary{
display:inline-block;padding:12px 26px;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
color:#fff;text-decoration:none;border-radius:12px;font-weight:700
  }


  .exam-stats{
background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid var(--border);
border-radius:12px;padding:14px 18px;margin-bottom:24px;display:flex;gap:18px;flex-wrap:wrap;justify-content:center
  }
  .stat-badge{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600}
  .stat-badge.easy{color:#166534}
  .stat-badge.medium{color:#92400e}
  .stat-badge.hard{color:#991b1b}


  @media (max-width:700px){
.top-sticky-bar{padding:12px 20px;flex-direction:column;gap:10px}
.right-nav-area{flex-wrap:wrap;justify-content:center}
.exam-content{width:96%;margin:20px auto}
.question-card{padding:20px 18px}
.question-card p{padding-right:0}
.difficulty-badge{position:static;display:inline-block;margin-bottom:10px}
.title-area h2{font-size:14px}
.exam-stats{flex-direction:column;gap:8px}
.exam-summary{grid-template-columns:1fr}
  }
 </style>
</head>
<body>


<div class="top-sticky-bar">
 <div class="title-area">
  <h2>Môn thi: <?php echo htmlspecialchars(strtoupper($subject)); ?> (25 Câu Ngẫu Nhiên)</h2>
 </div>
 <div class="right-nav-area">
  <div class="timer-box">⏱️ <span id="timeDisplay">--:--</span></div>
  <div class="user-info">Thí sinh: <strong><?php echo htmlspecialchars($username); ?></strong></div>
  <button id="btnBackNavbar" class="btn-back" onclick="confirmBack()">← Quay Lại</button>
 </div>
</div>


<div class="exam-content">
 <?php if ($examLocked): ?>
  <div class="question-card lock-card">
<div class="icon">🔒</div>
<h2>MÔN THI HIỆN ĐANG KHÓA</h2>
<p>Môn học này đã đóng hoặc chưa được Giám thị kích hoạt quyền truy cập. Vui lòng quay lại sau.</p>
<a href="student_dashboard.php" class="btn-back-secondary">Quay Lại Giao Diện Chính</a>
  </div>
 <?php elseif (count($questions) == 0): ?>
  <div class="question-card" style="text-align:center;padding:40px 20px">
<p style="color:var(--text-muted);font-weight:600">
 Không tìm thấy đủ dữ liệu câu hỏi môn này trong file <code>data/questions.json</code>.<br>
 <span style="font-size:13px;margin-top:10px;display:block">
<strong>Lỗi phổ biến:</strong> Kiểm tra file <code>exam_status.json</code> xem môn <?php echo htmlspecialchars(strtoupper($subject)); ?> đã được mở (open) chưa?
 </span>
</p>
  </div>
 <?php else: ?>
  <div class="exam-stats">
<?php $stats = $_SESSION['exam_stats'] ?? ['easy' => 0, 'medium' => 0, 'hard' => 0, 'total' => 0]; ?>
<div class="stat-badge easy">🟢 Dễ: <strong><?php echo $stats['easy']; ?></strong></div>
<div class="stat-badge medium">🟡 Trung bình: <strong><?php echo $stats['medium']; ?></strong></div>
<div class="stat-badge hard">🔴 Khó: <strong><?php echo $stats['hard']; ?></strong></div>
<div class="stat-badge" style="color:var(--primary)">📊 Tổng: <strong><?php echo $stats['total']; ?> câu</strong></div>
  </div>


  <form action="actions/submit_action.php" method="POST" id="mainExamForm">
<input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
<input type="hidden" name="start_time" id="input_start_time" value="">
<input type="hidden" name="tab_switch_count" id="input_tab_switch" value="0">


<?php foreach ($questions as $index => $q): ?>
 <?php $difficulty = isset($q['difficulty']) ? strtolower($q['difficulty']) : 'medium'; ?>
 <div class="question-card">
<span class="difficulty-badge <?php echo $difficulty; ?>">
 <?php echo $difficulty === 'easy' ? '🟢 Dễ' : ($difficulty === 'hard' ? '🔴 Khó' : '🟡 Trung bình'); ?>
</span>
<p>Câu <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($q['question']); ?></p>
<label><input type="radio" name="ans_<?php echo $index; ?>" value="A"> A. <?php echo htmlspecialchars($q['A']); ?></label>
<label><input type="radio" name="ans_<?php echo $index; ?>" value="B"> B. <?php echo htmlspecialchars($q['B']); ?></label>
<label><input type="radio" name="ans_<?php echo $index; ?>" value="C"> C. <?php echo htmlspecialchars($q['C']); ?></label>
<label><input type="radio" name="ans_<?php echo $index; ?>" value="D"> D. <?php echo htmlspecialchars($q['D']); ?></label>
 </div>
<?php endforeach; ?>


<button type="submit" class="btn-submit-exam">NỘP BÀI THI</button>
  </form>
 <?php endif; ?>
</div>


<script>
const isExamLocked = <?php echo $examLocked ? 'true' : 'false'; ?>;
const hasQuestions = <?php echo count($questions) > 0 ? 'true' : 'false'; ?>;
const totalQuestions = <?php echo (int)count($questions); ?>;
const inputStartTime = document.getElementById('input_start_time');
const inputTabSwitch = document.getElementById('input_tab_switch');
const examForm = document.getElementById('mainExamForm');
let allowUnload = false;


<?php if (isset($_SESSION['exam_start_time']) && !$examLocked && count($questions) > 0): ?>
inputStartTime.value = "<?php echo $_SESSION['exam_start_time']; ?>";
<?php endif; ?>


if (examForm) {
 examForm.addEventListener('submit', function () {
  allowUnload = true;
 });
}


function prettyAlert(title, bodyHtml, confirmText, onConfirm) {
 Swal.fire({
  icon: false,
  title: '',
  html: `
<div class="exam-swal-wrap">
 <div class="exam-swal-head">
  <div class="exam-swal-icon">!</div>
  <div class="exam-swal-head-text">
<h3>${title}</h3>
<p>Vui lòng cân nhắc trước khi tiếp tục.</p>
  </div>
 </div>
 <div class="exam-swal-body">
  ${bodyHtml}
  <div class="exam-swal-note">Tác vụ này có thể làm mất dữ liệu bài làm hoặc làm gián đoạn quá trình thi.</div>
 </div>
</div>
  `,
  showCancelButton: true,
  confirmButtonText: confirmText,
  cancelButtonText: 'Ở Lại Làm Bài',
  customClass: {
popup: 'exam-swal',
htmlContainer: 'exam-swal-html',
actions: 'exam-swal-actions',
confirmButton: 'exam-swal-confirm',
cancelButton: 'exam-swal-cancel'
  },
  buttonsStyling: false,
  allowOutsideClick: false,
  allowEscapeKey: false
 }).then((result) => {
  if (result.isConfirmed && typeof onConfirm === 'function') onConfirm();
 });
}


function confirmBack() {
 if (isExamLocked || !hasQuestions) {
  window.history.back();
  return;
 }


 prettyAlert(
  'BẠN CHẮC CHẮN MUỐN QUAY LẠI?',
  `<p style="margin:0 0 8px 0">Bạn đang làm bài thi môn <b style="color:var(--primary);font-size:17px"><?php echo htmlspecialchars(strtoupper($subject)); ?></b>.</p>
   <p style="margin:0">Nếu bạn quay lại, <b>dữ liệu làm bài sẽ bị mất</b> và phải làm lại từ đầu.</p>`,
  'Quay Lại',
  function() {
<?php if (isset($_SESSION['exam_start_time'])): ?>
navigator.sendBeacon('actions/clear_exam_session.php');
<?php endif; ?>
allowUnload = true;
window.history.back();
  }
 );
}


function showIncompleteWarning(unansweredList, onConfirm) {
 const answered = totalQuestions - unansweredList.length;
 const percent = Math.round((answered / totalQuestions) * 100);
 const preview = unansweredList.slice(0, 8).map(n => `Câu ${n}`).join(', ');
 const moreText = unansweredList.length > 8 ? ` và ${unansweredList.length - 8} câu khác` : '';


 Swal.fire({
  icon: false,
  title: '',
  html: `
<div class="exam-swal-wrap">
 <div class="exam-swal-head">
  <div class="exam-swal-icon">?</div>
  <div class="exam-swal-head-text">
<h3>Bạn chưa trả lời đủ đáp án</h3>
<p>Hãy kiểm tra lại trước khi nộp bài.</p>
  </div>
 </div>
 <div class="exam-swal-body">
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:6px;">
<div style="font-size:14px;color:#334155;">
 Đã trả lời <b style="color:#2563eb">${answered}/${totalQuestions}</b> câu
</div>
<div style="font-size:13px;font-weight:800;color:#ef4444;background:#fef2f2;padding:6px 10px;border-radius:999px;">
 Còn ${unansweredList.length} câu trống
</div>
  </div>
  <div class="exam-progress">
<div class="exam-progress-bar" style="width:${percent}%"></div>
  </div>
  <div class="exam-summary">
<div class="exam-box">
 <span class="label">Đã làm</span>
 <span class="value">${answered}</span>
</div>
<div class="exam-box warn">
 <span class="label">Chưa làm</span>
 <span class="value">${unansweredList.length}</span>
</div>
  </div>
  <div style="margin-top:14px;color:#334155;">
<b>Câu chưa làm:</b> ${preview}${moreText}
  </div>
  <div class="exam-swal-note">
Nên hoàn thành toàn bộ câu hỏi để tránh mất điểm đáng tiếc.
  </div>
 </div>
</div>
  `,
  showCancelButton: true,
  confirmButtonText: 'Vẫn Nộp Bài',
  cancelButtonText: 'Quay Lại Làm Tiếp',
  customClass: {
popup: 'exam-swal',
htmlContainer: 'exam-swal-html',
actions: 'exam-swal-actions',
confirmButton: 'exam-swal-confirm',
cancelButton: 'exam-swal-cancel'
  },
  buttonsStyling: false,
  allowOutsideClick: false,
  allowEscapeKey: false
 }).then((result) => {
  if (result.isConfirmed && typeof onConfirm === 'function') onConfirm();
 });
}


function getUnansweredQuestions() {
 const unanswered = [];
 for (let i = 0; i < totalQuestions; i++) {
  const checked = document.querySelector(`input[name="ans_${i}"]:checked`);
  if (!checked) unanswered.push(i + 1);
 }
 return unanswered;
}


if (isExamLocked) {
 document.getElementById('timeDisplay').innerText = "--:--";
} else {
 let duration = 900;
 const display = document.getElementById('timeDisplay');


 const interval = setInterval(() => {
  let min = Math.floor(duration / 60);
  let sec = duration % 60;
  display.innerHTML = `${min}:${sec < 10 ? '0' + sec : sec}`;


  if (duration <= 0) {
clearInterval(interval);
allowUnload = true;
Swal.fire({
 icon: 'warning',
 title: 'Hết giờ làm bài!',
 text: 'Thời gian đã cạn. Hệ thống đang tiến hành thu và nộp bài thi của bạn...',
 timer: 2500,
 showConfirmButton: false,
 timerProgressBar: true,
 allowOutsideClick: false,
 allowEscapeKey: false
}).then(() => {
 examForm.submit();
});
  }
  duration--;
 }, 1000);


 let cheatCount = 0;
 document.addEventListener("visibilitychange", function() {
  if (document.hidden) {
cheatCount++;
inputTabSwitch.value = cheatCount;


if (cheatCount >= 3) {
 allowUnload = true;
 Swal.fire({
  icon: 'error',
  title: 'Hệ Thống Đã Khóa Đề!',
  text: 'Bạn đã vi phạm quy chế phòng thi (chuyển tab quá 3 lần). Bài thi sẽ được nộp tự động ngay lập tức!',
  timer: 3000,
  showConfirmButton: false,
  timerProgressBar: true,
  allowOutsideClick: false,
  allowEscapeKey: false
 }).then(() => {
  examForm.submit();
 });
} else {
 Swal.fire({
  icon: 'warning',
  title: 'CẢNH BÁO VI PHẠM',
  html: `Tuyệt đối không được rời khỏi màn hình phòng thi!<br><br>Ghi nhận vi phạm: <b style="color:#ef4444;font-size:18px">${cheatCount} / 3</b> lần.`,
  confirmButtonText: 'Tôi cam kết không tái phạm',
  confirmButtonColor: '#0ea5e9',
  allowOutsideClick: false
 });
}
  }
 });
}


document.addEventListener('contextmenu', event => event.preventDefault());


document.onkeydown = function(e) {
 if (e.keyCode == 123) return false;
 if (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) return false;
 if (e.ctrlKey && e.keyCode == 85) return false;
 if (e.ctrlKey && (e.keyCode == 67 || e.keyCode == 86)) return false;


 if (e.key === 'F5' || (e.ctrlKey && e.key.toLowerCase() === 'r')) {
  e.preventDefault();
  prettyAlert(
'BẠN CHẮC CHẮN MUỐN TẢI LẠI TRANG?',
`<p style="margin:0 0 8px 0">Bạn đang làm bài thi môn <b style="color:var(--primary);font-size:17px"><?php echo htmlspecialchars(strtoupper($subject)); ?></b>.</p>
 <p style="margin:0">Nếu tải lại trang, <b>dữ liệu làm bài có thể bị mất</b> và bạn có thể phải làm lại từ đầu.</p>`,
'Tải Lại',
function() {
 allowUnload = true;
 window.location.reload();
}
  );
  return false;
 }
};


window.addEventListener('beforeunload', function(e) {
 if (allowUnload) return;
 navigator.sendBeacon('actions/clear_exam_session.php');
 e.preventDefault();
 e.returnValue = '';
});


if (!isExamLocked && hasQuestions) {
 window.history.pushState(null, null, window.location.href);
 window.onpopstate = function() {
  confirmBack();
 };
}


if (examForm) {
 examForm.addEventListener('submit', function(e) {
  const unanswered = getUnansweredQuestions();


  if (unanswered.length > 0) {
e.preventDefault();
showIncompleteWarning(unanswered, function() {
 allowUnload = true;
 navigator.sendBeacon('actions/clear_exam_session.php');
 examForm.submit();
});
  }
 });
}
</script>
</body>
</html>