<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';

$allowedRoles = ['admin', 'teacher', 'student'];

if ($username === '' || !in_array($role, $allowedRoles, true)) {
    header("Location: login.php");
    exit();
}

$roleLabels = [
    'admin' => 'Quản trị viên',
    'teacher' => 'Giáo viên',
    'student' => 'Học sinh'
];

$roleColors = [
    'admin' => 'role-admin',
    'teacher' => 'role-teacher',
    'student' => 'role-student'
];

$roleLabel = $roleLabels[$role] ?? strtoupper($role);
$roleClass = $roleColors[$role] ?? 'role-student';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Box</title>
    <style>
        :root{
            --bg-1:#eff6ff;
            --bg-2:#eef2ff;
            --card:rgba(255,255,255,.78);
            --border:rgba(226,232,240,.9);
            --text:#0f172a;
            --muted:#64748b;
            --primary:#2563eb;
            --primary-2:#0ea5e9;
            --shadow:0 24px 60px rgba(15,23,42,.12);
        }
        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
            font-family:'Inter','Segoe UI',system-ui,sans-serif;
        }
        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:20px;
            color:var(--text);
            background:
                radial-gradient(circle at top left, rgba(56,189,248,.18), transparent 26%),
                radial-gradient(circle at bottom right, rgba(168,85,247,.16), transparent 24%),
                linear-gradient(135deg,var(--bg-1) 0%,var(--bg-2) 100%);
        }
        .chat-wrap{
            width:min(980px,100%);
            background:var(--card);
            backdrop-filter:blur(18px);
            border-radius:14px;
            box-shadow:var(--shadow);
            overflow:hidden;
            border:1px solid var(--border);
        }
        .chat-head{
            background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#0ea5e9 100%);
            color:#fff;
            padding:18px 22px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:14px;
            flex-wrap:wrap;
        }
        .chat-title{
            display:flex;
            flex-direction:column;
            gap:6px;
        }
        .chat-head h2{
            font-size:18px;
            font-weight:800;
            letter-spacing:.2px;
        }
        .meta{
            font-size:13px;
            opacity:.95;
        }
        .role-pill{
            padding:8px 14px;
            border-radius:999px;
            background:rgba(255,255,255,.14);
            border:1px solid rgba(255,255,255,.16);
            font-size:12px;
            font-weight:800;
            white-space:nowrap;
        }
        .chat-body{
            display:flex;
            flex-direction:column;
            height:min(75vh,760px);
            background:#fff;
        }
        #chatMessages{
            flex:1;
            display:flex;
            flex-direction:column;
            gap:4px;
            padding:20px;
            overflow-y:auto;
            background:linear-gradient(180deg, rgba(248,250,252,.95), rgba(248,250,252,.98));
        }
        .msg{
            display:inline-block;
            width:fit-content;
            max-width:75%;
            padding:12px 14px;
            margin-bottom:12px;
            border-radius:16px;
            line-height:1.5;
            font-size:14px;
            box-shadow:0 4px 12px rgba(15,23,42,.05);
            word-wrap:break-word;
            overflow-wrap:break-word;
            white-space:normal;
            border:1px solid transparent;
            transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease, background-color .22s ease;
            will-change:transform;
            backface-visibility:hidden;
            -webkit-font-smoothing:antialiased;
            position:relative;
        }
        .msg.me{
            margin-left:auto;
            background:linear-gradient(135deg,#dbeafe 0%,#eff6ff 100%);
            border-color:#bfdbfe;
        }
        .msg.other{
            margin-right:auto;
            background:#fff;
            border-color:#e2e8f0;
        }
        .msg:hover{
            transform:translateY(-3px);
            box-shadow:0 14px 26px rgba(15,23,42,.10);
            border-color:#93c5fd;
        }
        .msg .info{
            display:flex;
            justify-content:space-between;
            gap:12px;
            font-size:12px;
            margin-bottom:8px;
            color:var(--muted);
        }
        .msg .name{
            font-weight:800;
            color:var(--text);
        }
        .msg-content{
            white-space:pre-wrap;
            overflow-wrap:anywhere;
            word-break:break-word;
        }
        .msg-revoked{
            margin-top:6px;
            font-size:12px;
            color:#94a3b8;
            font-style:italic;
        }
        .chat-form{
            border-top:1px solid #e2e8f0;
            padding:14px;
            background:#fff;
        }
        .input-bar{
            display:flex;
            align-items:stretch;
            width:100%;
            border:1.5px solid #cbd5e1;
            border-radius:12px;
            overflow:hidden;
            background:#f8fafc;
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
        }
        .input-bar:hover{
            transform:translateY(-2px);
            border-color:#93c5fd;
            box-shadow:0 10px 20px rgba(37,99,235,.10);
            background:#fff;
        }
        .input-bar:focus-within{
            border-color:#60a5fa;
            background:#fff;
            box-shadow:0 0 0 4px rgba(96,165,250,.15);
        }
        .input-btn{
            width:52px;
            min-width:52px;
            border:none;
            border-radius:12px;
            background:#e5f0ff;
            color:#1d4ed8;
            font-weight:800;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
            transition:transform .2s ease, box-shadow .2s ease, background-color .2s ease;
            flex-shrink:0;
        }
        .input-btn.send{
            background:linear-gradient(135deg,var(--primary) 0%,var(--primary-2) 100%);
            color:#fff;
        }
        .input-btn:hover{
            transform:translateY(-1px);
        }
        .input-btn.send:hover{
            box-shadow:0 16px 28px rgba(37,99,235,.28);
        }
        .input-bar textarea{
            flex:1;
            resize:none;
            min-height:48px;
            max-height:150px;
            padding:12px 14px;
            border:none;
            outline:none;
            background:transparent;
            font-size:14px;
            color:var(--text);
            border-left:1px solid #dbeafe;
            border-right:1px solid #dbeafe;
            overflow-y:auto;
            field-sizing:content;
        }
        .image-grid{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:10px;
        }
        .image-card{
            position:relative;
            width:120px;
            height:100px;
            border-radius:12px;
            overflow:hidden;
            border:1px solid #e2e8f0;
            background:#fff;
            cursor:zoom-in;
            flex:0 0 auto;
        }
        .image-card img{
            width:100%;
            height:100%;
            object-fit:cover;
            display:block;
        }
        .remove-x{
            position:absolute;
            top:6px;
            right:6px;
            width:22px;
            height:22px;
            border:none;
            border-radius:999px;
            background:rgba(15,23,42,.75);
            color:#fff;
            font-size:14px;
            font-weight:800;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
            line-height:1;
        }
        .remove-x:hover{
            background:rgba(220,38,38,.9);
        }
        .msg-images{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:8px;
        }
        .chat-msg-image{
            width:120px;
            height:96px;
            object-fit:cover;
            border-radius:12px;
            border:1px solid #e2e8f0;
            cursor:zoom-in;
        }
        .image-modal{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.45);
            z-index:9999;
            align-items:center;
            justify-content:center;
            padding:20px;
        }
        .image-modal img{
            max-width:min(78vw,900px);
            max-height:min(78vh,650px);
            border-radius:14px;
            box-shadow:0 20px 60px rgba(0,0,0,.4);
            background:#fff;
        }
        .close-modal{
            position:absolute;
            top:18px;
            right:24px;
            color:#fff;
            font-size:38px;
            cursor:pointer;
            font-weight:700;
            line-height:1;
        }
        .empty-state{
            color:var(--muted);
            text-align:center;
            padding:24px;
            font-size:14px;
        }
        .notice-modal{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(15,23,42,.45);
            z-index:10000;
            align-items:center;
            justify-content:center;
            padding:20px;
        }
        .notice-card{
            width:min(420px,100%);
            background:#fff;
            border-radius:18px;
            padding:22px 20px 18px;
            box-shadow:0 24px 70px rgba(15,23,42,.28);
            border:1px solid #e2e8f0;
            text-align:center;
            animation:popIn .18s ease-out;
        }
        .notice-icon{
            width:56px;
            height:56px;
            margin:0 auto 12px;
            border-radius:999px;
            background:linear-gradient(135deg,#fee2e2,#fecaca);
            color:#dc2626;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:30px;
            font-weight:900;
        }
        .notice-title{
            font-size:18px;
            font-weight:800;
            color:#0f172a;
            margin-bottom:8px;
        }
        .notice-text{
            font-size:14px;
            color:#475569;
            line-height:1.6;
            margin-bottom:16px;
        }
        .notice-close{
            border:none;
            background:linear-gradient(135deg,#2563eb,#0ea5e9);
            color:#fff;
            padding:10px 18px;
            border-radius:12px;
            font-weight:800;
            cursor:pointer;
        }
        .context-menu{
            display:none;
            position:fixed;
            z-index:10001;
            min-width:190px;
            background:#fff;
            border:1px solid #e2e8f0;
            border-radius:12px;
            box-shadow:0 12px 30px rgba(0,0,0,.15);
            overflow:hidden;
        }
        .context-menu button{
            width:100%;
            padding:12px 14px;
            border:none;
            background:#fff;
            cursor:pointer;
            text-align:left;
            font-weight:700;
            color:#dc2626;
        }
        .context-menu button:hover{
            background:#fef2f2;
        }
        @keyframes popIn{
            from{transform:scale(.94); opacity:.4;}
            to{transform:scale(1); opacity:1;}
        }
        @media (max-width: 640px){
            body{padding:10px;}
            .chat-head{padding:16px;}
            .chat-body{height:82vh;}
            #chatMessages{padding:14px;}
            .msg{max-width:88%;}
            .chat-form{padding:12px;}
            .input-bar{flex-direction:column;align-items:stretch;}
            .input-btn{width:100%;}
            .image-card{width:110px;height:92px;}
        }
    </style>
</head>
<body>
<div class="chat-wrap">
    <div class="chat-head">
        <div class="chat-title">
            <h2>Chat hỗ trợ học sinh - giáo viên</h2>
            <div class="meta">Tài khoản: <b><?php echo htmlspecialchars($username); ?></b></div>
        </div>
        <div class="role-pill <?php echo htmlspecialchars($roleClass); ?>">Vai trò: <?php echo htmlspecialchars($roleLabel); ?></div>
    </div>

    <div class="chat-body">
        <div id="chatMessages"></div>

        <form class="chat-form" id="chatForm" autocomplete="off">
            <input type="hidden" id="sender" value="<?php echo htmlspecialchars($username); ?>">
            <input type="hidden" id="role" value="<?php echo htmlspecialchars($role); ?>">
            <input type="hidden" id="chatToken" value="">
            <input type="file" id="imageInput" accept="image/*" hidden multiple>

            <div style="width:100%;">
                <div class="input-bar">
                    <button type="button" class="input-btn" id="pickImageBtn">🖼️</button>
                    <textarea id="message" placeholder="Nhập tin nhắn..." wrap="soft"></textarea>
                    <button type="submit" class="input-btn send">Gửi</button>
                </div>
                <div class="image-grid" id="imageGrid"></div>
            </div>
        </form>
    </div>
</div>

<div class="image-modal" id="imageModal">
    <span class="close-modal" id="closeModalBtn">&times;</span>
    <img id="modalImage" alt="Ảnh phóng to">
</div>

<div class="notice-modal" id="noticeModal">
    <div class="notice-card">
        <div class="notice-icon">!</div>
        <div class="notice-title">Ảnh vượt dung lượng</div>
        <div class="notice-text" id="noticeText"></div>
        <button type="button" class="notice-close" id="noticeCloseBtn">Đã hiểu</button>
    </div>
</div>

<div class="context-menu" id="contextMenu">
    <button type="button" id="revokeMsgBtn">Thu hồi tin nhắn</button>
</div>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const messageInput = document.getElementById('message');
const senderInput = document.getElementById('sender');
const roleInput = document.getElementById('role');
const chatTokenInput = document.getElementById('chatToken');
const imageInput = document.getElementById('imageInput');
const pickImageBtn = document.getElementById('pickImageBtn');
const imageGrid = document.getElementById('imageGrid');
const imageModal = document.getElementById('imageModal');
const modalImage = document.getElementById('modalImage');
const closeModalBtn = document.getElementById('closeModalBtn');
const noticeModal = document.getElementById('noticeModal');
const noticeText = document.getElementById('noticeText');
const noticeCloseBtn = document.getElementById('noticeCloseBtn');
const contextMenu = document.getElementById('contextMenu');
const revokeMsgBtn = document.getElementById('revokeMsgBtn');

let selectedFiles = [];
let activeMsgId = '';
let activeMsgCreatedAt = 0;
let stickToBottom = true;
let lastSeenSyncAt = 0;

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text ?? '';
    return div.innerHTML;
}

function autoResizeTextarea(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 180) + 'px';
}

function showNotice(msg) {
    noticeText.textContent = msg;
    noticeModal.style.display = 'flex';
}

function hideNotice() {
    noticeModal.style.display = 'none';
}

function renderSelectedImages() {
    imageGrid.innerHTML = selectedFiles.map((item, idx) => `
        <div class="image-card" data-idx="${idx}">
            <img src="${item.dataUrl}" alt="preview">
            <button type="button" class="remove-x" data-remove="${idx}">×</button>
        </div>
    `).join('');
}

function clearSelectedImages() {
    selectedFiles = [];
    imageInput.value = '';
    imageGrid.innerHTML = '';
}

function renderMessages(data, shouldScroll = false) {
    if (!Array.isArray(data) || data.length === 0) {
        chatMessages.innerHTML = '<div class="empty-state">Chưa có tin nhắn nào.</div>';
        if (shouldScroll) chatMessages.scrollTop = chatMessages.scrollHeight;
        return;
    }

    chatMessages.innerHTML = data.map(m => {
        const isMe = m.sender === senderInput.value;
        const revoked = !!m.revoked;
        const images = Array.isArray(m.images) ? m.images : (m.image_url ? [m.image_url] : []);

        let bodyHtml = '';
        if (revoked) {
            bodyHtml = `
                <div class="msg-content" style="color:#94a3b8;font-style:italic;">
                    ${isMe ? 'Bạn đã thu hồi 1 tin nhắn' : `${escapeHtml(m.sender)} đã thu hồi 1 tin nhắn`}
                </div>
            `;
        } else {
            bodyHtml = `
                <div class="msg-content">${escapeHtml(m.message)}</div>
                ${images.length ? `
                    <div class="msg-images">
                        ${images.map(src => `
                            <img class="chat-msg-image" src="${escapeHtml(src)}" alt="Ảnh tin nhắn">
                        `).join('')}
                    </div>
                ` : ''}
${isMe ? `
    <div class="msg-revoked" style="font-style:normal;color:#64748b;">
        ${m.seen ? 'Đã xem' : 'Đã gửi'}
    </div>
` : ''}
            `;
        }

        return `
            <div class="msg ${isMe ? 'me' : 'other'}"
                 data-id="${escapeHtml(m.id || '')}"
                 data-created-at="${escapeHtml(m.created_at || '')}">
                <div class="info">
                    <span class="name">${escapeHtml(m.sender)} (${escapeHtml(m.role)})</span>
                    <span>${escapeHtml(m.time)}</span>
                </div>
                ${bodyHtml}
            </div>
        `;
    }).join('');

    if (shouldScroll) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

async function ensureChatToken() {
    const res = await fetch('chat_token.php', { cache: 'no-store' });
    const data = await res.json();

    if (!res.ok || data.status !== 'ok' || !data.chat_token) {
        throw new Error('Cannot get chat token');
    }

    chatTokenInput.value = data.chat_token;
    return data.chat_token;
}

async function loadMessagesOnce() {
    try {
        const token = chatTokenInput.value || 'global_room';
        const nearBottom = (chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) < 80;

        const prevScrollTop = chatMessages.scrollTop;
        const prevScrollHeight = chatMessages.scrollHeight;

        const res = await fetch('chat_fetch.php?chat_token=' + encodeURIComponent(token), { cache: 'no-store' });
        const data = await res.json();

        if (!res.ok || !Array.isArray(data)) {
            throw new Error('Invalid message data');
        }

        renderMessages(data, nearBottom);

        if (!nearBottom) {
            const newScrollHeight = chatMessages.scrollHeight;
            chatMessages.scrollTop = prevScrollTop + (newScrollHeight - prevScrollHeight);
        }
    } catch (error) {
        chatMessages.innerHTML = '<div class="empty-state">Không tải được tin nhắn.</div>';
    }
}

async function markMessagesAsSeen() {
    try {
        const token = chatTokenInput.value || 'global_room';
        await fetch('chat_seen.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: 'chat_token=' + encodeURIComponent(token),
            cache: 'no-store'
        });
    } catch (e) {}
}

chatMessages.addEventListener('scroll', () => {
    stickToBottom = (chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) < 80;
});

ensureChatToken()
    .then(() => {
        loadMessagesOnce();
        markMessagesAsSeen();
        setInterval(loadMessagesOnce, 3000);
        setInterval(markMessagesAsSeen, 5000);
    })
    .catch(() => {
        chatMessages.innerHTML = '<div class="empty-state">Không tải được token chat.</div>';
    });

autoResizeTextarea(messageInput);

pickImageBtn.addEventListener('click', () => {
    imageInput.click();
});

imageInput.addEventListener('change', () => {
    const files = Array.from(imageInput.files || []);
    if (!files.length) {
        clearSelectedImages();
        return;
    }

    const maxCount = 5;
    const maxSize = 2 * 1024 * 1024;
    const validFiles = files.slice(0, maxCount);
    selectedFiles = [];

    let processed = 0;

    validFiles.forEach((file, index) => {
        const displayIndex = index + 1;

        if (!file.type.startsWith('image/')) {
            processed++;
            if (processed === validFiles.length) renderSelectedImages();
            return;
        }

        if (file.size > maxSize) {
            showNotice(`Ảnh thứ ${displayIndex} từ trái qua vượt dung lượng cho phép.`);
            processed++;
            if (processed === validFiles.length) renderSelectedImages();
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            selectedFiles.push({
                file,
                dataUrl: e.target.result
            });

            processed++;
            if (processed === validFiles.length) {
                renderSelectedImages();
            }
        };
        reader.readAsDataURL(file);
    });

    if (validFiles.length === 0) clearSelectedImages();
});

imageGrid.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('[data-remove]');
    if (removeBtn) {
        const idx = Number(removeBtn.getAttribute('data-remove'));
        selectedFiles.splice(idx, 1);
        if (selectedFiles.length === 0) {
            clearSelectedImages();
        } else {
            renderSelectedImages();
        }
        return;
    }

    const card = e.target.closest('.image-card');
    if (!card) return;

    const idx = Number(card.getAttribute('data-idx'));
    const item = selectedFiles[idx];
    if (!item) return;

    modalImage.src = item.dataUrl;
    imageModal.style.display = 'flex';
});

closeModalBtn.addEventListener('click', () => {
    imageModal.style.display = 'none';
    modalImage.src = '';
});

imageModal.addEventListener('click', (e) => {
    if (e.target === imageModal) {
        imageModal.style.display = 'none';
        modalImage.src = '';
    }
});

noticeCloseBtn.addEventListener('click', hideNotice);
noticeModal.addEventListener('click', (e) => {
    if (e.target === noticeModal) hideNotice();
});

messageInput.addEventListener('input', () => autoResizeTextarea(messageInput));

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const message = messageInput.value.trim();
    if (!message && selectedFiles.length === 0) return;

    const formData = new FormData();
    formData.append('sender', senderInput.value);
    formData.append('role', roleInput.value);
    formData.append('chat_token', chatTokenInput.value || 'global_room');
    formData.append('message', message);

    selectedFiles.forEach(item => {
        formData.append('images[]', item.file);
    });

    try {
        const res = await fetch('chat_send.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json().catch(() => null);

        if (!res.ok || !data || data.status !== 'ok') {
            throw new Error(data?.message || 'send failed');
        }

        messageInput.value = '';
        messageInput.style.height = '48px';
        clearSelectedImages();

        await loadMessagesOnce();
        messageInput.focus();
    } catch (error) {
        alert('Gửi tin nhắn thất bại.');
    }
});

messageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        chatForm.requestSubmit();
    }
});

chatMessages.addEventListener('click', (e) => {
    const img = e.target.closest('.chat-msg-image');
    if (!img) return;
    modalImage.src = img.src;
    imageModal.style.display = 'flex';
});

chatMessages.addEventListener('contextmenu', (e) => {
    const msg = e.target.closest('.msg');
    if (!msg) return;
    if (!msg.classList.contains('me')) return;

    e.preventDefault();

    activeMsgId = msg.getAttribute('data-id') || '';
    activeMsgCreatedAt = Number(msg.getAttribute('data-created-at') || 0);

    if (!activeMsgId || !activeMsgCreatedAt) return;

    const age = Date.now() - activeMsgCreatedAt * 1000;
    if (age > 60000) return;

    contextMenu.style.display = 'block';
    contextMenu.style.left = e.clientX + 'px';
    contextMenu.style.top = e.clientY + 'px';
});

document.addEventListener('click', () => {
    contextMenu.style.display = 'none';
});

revokeMsgBtn.addEventListener('click', async () => {
    if (!activeMsgId) return;

    const formData = new FormData();
    formData.append('id', activeMsgId);
    formData.append('chat_token', chatTokenInput.value || 'global_room');

    try {
        const res = await fetch('chat_revoke.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (!res.ok || data.status !== 'ok') {
            throw new Error(data.message || 'revoke failed');
        }

        contextMenu.style.display = 'none';
        activeMsgId = '';
        activeMsgCreatedAt = 0;
        await loadMessagesOnce();
    } catch (err) {
        alert(err.message || 'Thu hồi thất bại.');
    }
});
</script>
</body>
</html>