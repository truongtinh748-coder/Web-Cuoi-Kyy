<?php
$data = json_decode(file_get_contents('data.json'), true);

$name = $_POST['student_name']; 
$data[$name] = [
    "vCount" => 0,      
    "submitted" => true 
];

file_put_contents('data.json', json_encode($data));
?>
