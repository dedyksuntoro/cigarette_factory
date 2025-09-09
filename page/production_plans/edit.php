<?php
session_start();
require_once __DIR__.'/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin atau supervisor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID rencana dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/production_plans/list.php');
    exit();
}
$plan_id = $_GET['id'];

// Ambil data rencana
$stmt = $pdo->prepare("SELECT id, created_by, plan_date, target_quantity FROM production_plans WHERE id = ?");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    header('Location: '.$_ENV['BASE_URL'].'/page/production_plans/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $created_by = $_SESSION['user_id']; // Gunakan user_id dari sesi sebagai created_by
    $plan_date = $_POST['plan_date'];
    $target_quantity = $_POST['target_quantity'];

    try {
        $stmt = $pdo->prepare("UPDATE production_plans SET created_by = ?, plan_date = ?, target_quantity = ? WHERE id = ?");
        $stmt->execute([$created_by, $plan_date, $target_quantity, $plan_id]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Mengedit rencana produksi untuk tanggal $plan_date"]);

        header('Location: '.$_ENV['BASE_URL'].'/page/production_plans/list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal mengedit rencana produksi: " . $e->getMessage();
    }
}

require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit Rencana Produksi</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="plan_date" class="form-label">Tanggal Rencana</label>
            <input type="date" class="form-control" id="plan_date" name="plan_date" value="<?php echo htmlspecialchars($plan['plan_date']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="target_quantity" class="form-label">Jumlah Target</label>
            <input type="number" class="form-control" id="target_quantity" name="target_quantity" value="<?php echo htmlspecialchars($plan['target_quantity']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
