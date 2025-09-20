<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_employees'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID karyawan dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'list.php');
    exit();
}
$employee_id = $_GET['id'];

// Ambil nama karyawan untuk log
$stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus karyawan: " . ($employee['name'] ?? 'ID ' . $employee_id)]);

    header('Location: '.$_ENV['BASE_URL'].'/page/employees/list.php');
    exit();
} catch (PDOException $e) {
    // Tampilkan error tanpa include header.php untuk menghindari output
    die("Gagal menghapus karyawan: " . $e->getMessage());
}
?>