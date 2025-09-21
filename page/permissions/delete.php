<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($_SESSION['role'], ['delete_all', 'delete_permissions'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID permission dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/permissions/list.php');
    exit();
}
$permission_id = $_GET['id'];

// Ambil nama permission untuk log
$stmt = $pdo->prepare("SELECT name FROM permissions WHERE id = ?");
$stmt->execute([$permission_id]);
$permission = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    // Mulai transaksi
    $pdo->beginTransaction();

    // Hapus entri terkait di role_permissions
    $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE permission_id = ?");
    $stmt->execute([$permission_id]);

    // Hapus permission
    $stmt = $pdo->prepare("DELETE FROM permissions WHERE id = ?");
    $stmt->execute([$permission_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus permission: " . ($permission['name'] ?? 'ID ' . $permission_id)]);

    // Commit transaksi
    $pdo->commit();

    header('Location: ' . $_ENV['BASE_URL'] . '/page/permissions/list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Gagal menghapus permission: " . $e->getMessage();
}
?>