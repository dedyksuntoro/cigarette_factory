<?php
session_start();
require_once __DIR__.'/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pergerakan stok dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/stock_movements/list.php');
    exit();
}
$movement_id = $_GET['id'];

// Ambil data pergerakan stok untuk log dan pembatalan stok
$stmt = $pdo->prepare("SELECT type, material_id, finished_good_id, quantity FROM stock_movements WHERE id = ?");
$stmt->execute([$movement_id]);
$movement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movement) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/stock_movements/list.php');
    exit();
}

try {
    // Mulai transaksi untuk memastikan konsistensi stok
    $pdo->beginTransaction();

    // Batalkan efek stok
    if ($movement['material_id']) {
        $column = 'stock';
        $table = 'materials';
        $id = $movement['material_id'];
    } elseif ($movement['finished_good_id']) {
        $column = 'stock';
        $table = 'finished_goods';
        $id = $movement['finished_good_id'];
    }

    if ($movement['type'] == 'in') {
        $stmt = $pdo->prepare("UPDATE $table SET $column = $column - ? WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE $table SET $column = $column + ? WHERE id = ?");
    }
    $stmt->execute([$movement['quantity'], $id]);

    // Hapus data pergerakan stok
    $stmt = $pdo->prepare("DELETE FROM stock_movements WHERE id = ?");
    $stmt->execute([$movement_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus pergerakan stok ID: $movement_id"]);

    $pdo->commit();
    header('Location: ' . $_ENV['BASE_URL'] . '/page/stock_movements/list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Gagal menghapus pergerakan stok: " . $e->getMessage();
}
?>