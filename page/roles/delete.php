<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_roles'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID role dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/roles/list.php');
    exit();
}
$role_id = $_GET['id'];

// Ambil nama role untuk log
$stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->execute([$role_id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    // Mulai transaksi
    $pdo->beginTransaction();

    // Hapus entri terkait di role_permissions
    $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);

    // Hapus entri terkait di user_roles
    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE role_id = ?");
    $stmt->execute([$role_id]);

    // Hapus role
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus role: " . ($role['name'] ?? 'ID ' . $role_id)]);

    // Commit transaksi
    $pdo->commit();

    header('Location: ' . $_ENV['BASE_URL'] . '/page/roles/list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Gagal menghapus role: " . $e->getMessage();
}
?>