<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_production_results'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID hasil dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/production_results/list.php');
    exit();
}
$result_id = $_GET['id'];

// Ambil data hasil untuk log
$stmt = $pdo->prepare("SELECT plan_id FROM production_results WHERE id = ?");
$stmt->execute([$result_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM production_results WHERE id = ?");
    $stmt->execute([$result_id]);
    
    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus hasil produksi untuk rencana ID " . ($result['plan_id'] ?? 'kosong')]);
    
    header('Location: '.$_ENV['BASE_URL'].'/page/production_results/list.php');
    exit();
} catch (PDOException $e) {
    $error = "Gagal menghapus hasil produksi: " . $e->getMessage();
    ?>
<div class="container mt-4">
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/list.php" class="btn btn-secondary">Kembali</a>
</div>
<?php
}
require_once __DIR__.'/../templates/header.php';
?>
