<?php
session_start();

if (isset($_SESSION['user_id'])) {
    include 'log_user_activity.php';
    
    if (isset($_GET['inactivity']) && $_GET['inactivity'] === 'true') {
        log_user_activity($_SESSION['user_id'], 'logout due to inactivity');
    } else {
        log_user_activity($_SESSION['user_id'], 'logout');
    }
    
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
} else {
    header('Location: login.php');
    exit();
}
?>
