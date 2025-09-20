<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_distributions'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID distribusi dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/distributions/list.php');
    exit();
}
$distribution_id = $_GET['id'];

// Ambil data distribusi untuk log dan pengembalian stok
$stmt = $pdo->prepare("SELECT product_id, quantity FROM distributions WHERE id = ?");
$stmt->execute([$distribution_id]);
$distribution = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$distribution) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/distributions/list.php');
    exit();
}

try {
    // Mulai transaksi
    $pdo->beginTransaction();

    // Kembalikan stok ke barang jadi
    $stmt = $pdo->prepare("UPDATE finished_goods SET stock = stock + ? WHERE id = ?");
    $stmt->execute([$distribution['quantity'], $distribution['product_id']]);

    // Hapus data distribusi
    $stmt = $pdo->prepare("DELETE FROM distributions WHERE id = ?");
    $stmt->execute([$distribution_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus distribusi ID: $distribution_id"]);

    $pdo->commit();
    header('Location: ' . $_ENV['BASE_URL'] . '/page/distributions/list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Gagal menghapus distribusi: " . $e->getMessage();
}
?>