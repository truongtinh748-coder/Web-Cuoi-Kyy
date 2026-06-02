<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_de'])) {
    $file_tmp = $_FILES['file_de']['tmp_name'];
    $data = json_decode(file_get_contents($file_tmp), true);

    if (isset($data['questions']) && count($data['questions']) == 24) {
        move_uploaded_file($file_tmp, "uploads/" . $_FILES['file_de']['name']);
        echo "Đã tải lên thành công!";
    } else {
        die("LỖI: Đề phải có đúng 24 câu. Hiện tại bạn đang tải lên " . count($data['questions']) . " câu!");
    }
}
?>