<?php
require_once '../../config/db.php';
require_once '../templates/header.php';

// Cek apakah pengguna sudah login dan memiliki peran admin atau supervisor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header('Location: ../../page/auth/login.php');
    exit();
}

// Ambil ID hasil dari URL
if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit();
}
$result_id = $_GET['id'];

// Ambil data hasil produksi
$stmt = $pdo->prepare("SELECT id, plan_id, actual_quantity, efficiency FROM production_results WHERE id = ?");
$stmt->execute([$result_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header('Location: list.php');
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
        $stmt = $pdo->prepare("UPDATE production_results SET plan_id = ?, actual_quantity = ?, efficiency = ? WHERE id = ?");
        $stmt->execute([$plan_id, $actual_quantity, $efficiency, $result_id]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Mengedit hasil produksi untuk rencana ID " . ($plan_id ?? 'kosong')]);

        header('Location: list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal mengedit hasil produksi: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h1>Edit Hasil Produksi</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="plan_id" class="form-label">Rencana Produksi (Opsional)</label>
            <select class="form-control" id="plan_id" name="plan_id">
                <option value="">Tidak terkait rencana</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo $plan['id']; ?>" <?php echo $plan['id'] == $result['plan_id'] ? 'selected' : ''; ?>>
                        ID <?php echo $plan['id']; ?> - Tanggal <?php echo htmlspecialchars($plan['plan_date']); ?> (Target: <?php echo $plan['target_quantity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="actual_quantity" class="form-label">Jumlah Aktual</label>
            <input type="number" class="form-control" id="actual_quantity" name="actual_quantity" value="<?php echo htmlspecialchars($result['actual_quantity']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>