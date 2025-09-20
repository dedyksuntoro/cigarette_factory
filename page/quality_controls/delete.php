<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_quality_controls'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID kontrol kualitas dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/quality_controls/list.php');
    exit();
}
$qc_id = $_GET['id'];

// Ambil data kontrol kualitas untuk log
$stmt = $pdo->prepare("SELECT id, batch_no FROM quality_controls WHERE id = ?");
$stmt->execute([$qc_id]);
$qc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$qc) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/quality_controls/list.php');
    exit();
}

try {
    // Hapus data kontrol kualitas
    $stmt = $pdo->prepare("DELETE FROM quality_controls WHERE id = ?");
    $stmt->execute([$qc_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus kontrol kualitas ID: $qc_id"]);

    header('Location: ' . $_ENV['BASE_URL'] . '/page/quality_controls/list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus kontrol kualitas: " . $e->getMessage();
}
?>