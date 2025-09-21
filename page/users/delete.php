<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($_SESSION['role'], ['delete_all', 'delete_users'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pengguna dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/users/list.php');
    exit();
}
$user_id = $_GET['id'];

// Ambil username untuk log
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    // Mulai transaksi
    $pdo->beginTransaction();

    // Hapus entri terkait di user_roles
    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Hapus pengguna
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus pengguna: " . ($user['username'] ?? 'ID ' . $user_id)]);

    // Commit transaksi
    $pdo->commit();

    header('Location: ' . $_ENV['BASE_URL'] . '/page/users/list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Gagal menghapus pengguna: " . $e->getMessage();
}
?>