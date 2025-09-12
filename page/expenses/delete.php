<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pengeluaran dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/expenses/list.php');
    exit();
}
$expense_id = $_GET['id'];

// Ambil data pengeluaran untuk log
$stmt = $pdo->prepare("SELECT id, description FROM expenses WHERE id = ?");
$stmt->execute([$expense_id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$expense) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/expenses/list.php');
    exit();
}

try {
    // Hapus data pengeluaran
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$expense_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus pengeluaran ID: $expense_id"]);

    header('Location: ' . $_ENV['BASE_URL'] . '/page/expenses/list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus pengeluaran: " . $e->getMessage();
}
?>