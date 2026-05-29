<?php
session_start();
if (isset($_GET['user'])) {
    $user = $_GET['user'];
    $_SESSION['cheaters'][$user] = date("H:i:s"); 
}
?>
