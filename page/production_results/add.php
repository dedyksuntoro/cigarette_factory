<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'create_production_results'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil daftar rencana produksi untuk dropdown
$stmt = $pdo->query("SELECT id, plan_date, target_quantity FROM production_plans ORDER BY plan_date DESC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan_id = $_POST['plan_id'] ?: null; // Mengizinkan plan_id kosong
    $actual_quantity = $_POST['actual_quantity'];

    // Ambil target_quantity untuk menghitung efisiensi, jika plan_id ada
    $efficiency = null;
    if ($plan_id) {
        $stmt = $pdo->prepare("SELECT target_quantity FROM production_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        $target_quantity = $plan['target_quantity'] ?? 0;
        $efficiency = $target_quantity > 0 ? ($actual_quantity / $target_quantity) * 100 : null;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO production_results (plan_id, actual_quantity, efficiency, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$plan_id, $actual_quantity, $efficiency]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Menambahkan hasil produksi untuk rencana ID " . ($plan_id ?? 'kosong')]);

        header('Location: '.$_ENV['BASE_URL'].'/page/production_results/list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menambah hasil produksi: " . $e->getMessage();
    }
}

require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Tambah Hasil Produksi</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="plan_id" class="form-label">Rencana Produksi (Opsional)</label>
            <select class="form-control" id="plan_id" name="plan_id">
                <option value="">Tidak terkait rencana</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo $plan['id']; ?>">
                        ID <?php echo $plan['id']; ?> - Tanggal <?php echo htmlspecialchars($plan['plan_date']); ?> (Target: <?php echo $plan['target_quantity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="actual_quantity" class="form-label">Jumlah Aktual</label>
            <input type="number" class="form-control" id="actual_quantity" name="actual_quantity" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
