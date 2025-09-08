<?php
session_start();
require_once '../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../page/auth/login.php');
    exit();
}

// Ambil ID pengguna dari URL
if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit();
}
$user_id = $_GET['id'];

// Ambil username untuk log
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus pengguna: " . ($user['username'] ?? 'ID ' . $user_id)]);

    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus pengguna: " . $e->getMessage();
}
?>