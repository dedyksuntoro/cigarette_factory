<?php
session_start();
require_once __DIR__.'/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin atau gudang
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'gudang'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID bahan dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/materials/list.php');
    exit();
}
$material_id = $_GET['id'];

// Ambil nama bahan untuk log
$stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
    $stmt->execute([$material_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus bahan baku: " . ($material['name'] ?? 'ID ' . $material_id)]);

    header('Location: '.$_ENV['BASE_URL'].'/page/materials/list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus bahan baku: " . $e->getMessage();
}
?>
