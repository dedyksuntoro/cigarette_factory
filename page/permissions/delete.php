<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_permissions'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID permissions dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/permissions/list.php');
    exit();
}
$permission_id = $_GET['id'];

// Ambil name permissions untuk log
$stmt = $pdo->prepare("SELECT name FROM permissions WHERE id = ?");
$stmt->execute([$permission_id]);
$permission = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM permissions WHERE id = ?");
    $stmt->execute([$permission_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus permission: " . ($permission['name'] ?? 'ID ' . $permission_id)]);

    header('Location: '.$_ENV['BASE_URL'].'/page/permissions/list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus permission: " . $e->getMessage();
}
?>