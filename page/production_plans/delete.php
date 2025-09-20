<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['delete_all', 'delete_production_plans'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID rencana dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/production_plans/list.php');
    exit();
}
$plan_id = $_GET['id'];

// Ambil data rencana untuk log
$stmt = $pdo->prepare("SELECT plan_date FROM production_plans WHERE id = ?");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("DELETE FROM production_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    
    // Catat log aktivitas
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], "Menghapus rencana produksi untuk tanggal " . ($plan['plan_date'] ?? 'ID ' . $plan_id)]);
    
    header('Location: '.$_ENV['BASE_URL'].'/page/production_plans/list.php');
    exit();
} catch (PDOException $e) {
    $error = "Gagal menghapus rencana produksi: " . $e->getMessage();
    ?>
<div class="container mt-4">
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/list.php" class="btn btn-secondary">Kembali</a>
</div>
<?php
}
require_once __DIR__.'/../templates/header.php';
?>
