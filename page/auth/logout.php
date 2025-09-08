<?php
session_start();
require_once __DIR__.'/../../config/db.php';

// Catat log aktivitas logout
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Logout pengguna {$_SESSION['username']}"]);
}

session_unset();
session_destroy();
header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
exit();
?>