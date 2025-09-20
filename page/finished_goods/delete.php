<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_finished_goods'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID barang jadi dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/finished_goods/list.php');
    exit();
}
$good_id = $_GET['id'];

// Ambil nama produk untuk log
$stmt = $pdo->prepare("SELECT product_name FROM finished_goods WHERE id = ?");
$stmt->execute([$good_id]);
$good = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM finished_goods WHERE id = ?");
    $stmt->execute([$good_id]);

    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus barang jadi: " . ($good['product_name'] ?? 'ID ' . $good_id)]);

    header('Location: ' . $_ENV['BASE_URL'] . '/page/finished_goods/list.php');
    exit();
} catch (PDOException $e) {
    $error = "Gagal menghapus barang jadi: " . $e->getMessage();
?>
<div class="container mt-4">
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <a href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/list.php" class="btn btn-secondary">Kembali</a>
</div>
<?php
}
?>
