<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID faktur dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/invoices/list.php');
    exit();
}
$invoice_id = $_GET['id'];

// Ambil data faktur untuk log
$stmt = $pdo->prepare("SELECT id, distribution_id FROM invoices WHERE id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/invoices/list.php');
    exit();
}

try {
    // Hapus data faktur
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus faktur ID: $invoice_id"]);

    header('Location: ' . $_ENV['BASE_URL'] . '/page/invoices/list.php');
    exit();
} catch (PDOException $e) {
    echo "Gagal menghapus faktur: " . $e->getMessage();
}
?>