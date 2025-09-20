<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_revenues'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pendapatan dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/revenues/list.php');
    exit();
}
$revenue_id = $_GET['id'];

// Ambil data pendapatan untuk log
$stmt = $pdo->prepare("SELECT id, description FROM revenues WHERE id = ?");
$stmt->execute([$revenue_id]);
$revenue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$revenue) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/revenues/list.php');
    exit();
}

try {
    // Hapus data pendapatan
    $stmt = $pdo->prepare("DELETE FROM revenues WHERE id = ?");
    $stmt->execute([$revenue_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus pendapatan ID: $revenue_id"]);

    header('Location: ' . $_ENV['BASE_URL'] . '/page/revenues/list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus pendapatan: " . $e->getMessage();
}
?>