<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_roles'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID peran dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/roles/list.php');
    exit();
}
$role_id = $_GET['id'];

// Ambil name peran pengguna untuk log
$stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->execute([$role_id]);
$peran = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus peran pengguna: " . ($peran['name'] ?? 'ID ' . $role_id)]);

    header('Location: '.$_ENV['BASE_URL'].'/page/roles/list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus peran pengguna: " . $e->getMessage();
}
?>
