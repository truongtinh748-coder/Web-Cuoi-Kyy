<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['system_users'])) {
    $_SESSION['system_users'] = [
        ['user' => 'admin', 'pass' => '123', 'role' => 'admin'],
        ['user' => 'hoitran120906', 'pass' => '123', 'role' => 'student'],
        ['user' => 'truongtinh748', 'pass' => '123', 'role' => 'student']
    ];
}

if (!isset($_SESSION['global_questions'])) {
    $_SESSION['global_questions'] = [
        [
            'subject' => 'web',
            'id_de' => 'de_01',
            'question' => 'Thẻ nào dùng để tạo một liên kết (siêu văn bản) trong HTML?',
            'A' => '<link>', 'B' => '<a>', 'C' => '<href>', 'D' => '<url>',
            'correct' => 'B'
        ],
        [
            'subject' => 'web',
            'id_de' => 'de_01',
            'question' => 'Trong CSS, thuộc tính nào dùng để đổi màu nền của một phần tử?',
            'A' => 'color', 'B' => 'background-color', 'C' => 'text-color', 'D' => 'bg',
            'correct' => 'B'
        ],
        [
            'subject' => 'net',
            'id_de' => 'de_01',
            'question' => 'Giao thức nào hoạt động ở Tầng ứng dụng (Application Layer) của mô hình OSI?',
            'A' => 'HTTP', 'B' => 'TCP', 'C' => 'IP', 'D' => 'UDP',
            'correct' => 'A'
        ]
    ];
}

if (!isset($_SESSION['global_history'])) {
    $_SESSION['global_history'] = [
        ['name' => 'hoitran120906', 'subject' => 'Lập trình Web', 'score' => '10.0', 'search_tag' => 'hoitran120906 - web'],
        ['name' => 'truongtinh748', 'subject' => 'Mạng máy tính', 'score' => '5.0', 'search_tag' => 'truongtinh748 - net']
    ];
}
?>