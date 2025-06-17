<?php
session_start();
include 'includes/db.php';

// Record logout time if we have a login history ID
if (isset($_SESSION['login_history_id'])) {
    $logout_time = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("UPDATE user_login_history SET logout_time = ? WHERE id = ?");
    $stmt->execute([$logout_time, $_SESSION['login_history_id']]);
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>