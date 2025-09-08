<?php
require_once '../../config/db.php';
require_once '../templates/header.php';

// Cek apakah pengguna sudah login dan memiliki peran admin atau supervisor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header('Location: ../../page/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $created_by = $_SESSION['user_id']; // Gunakan user_id dari sesi sebagai created_by
    $plan_date = $_POST['plan_date'];
    $target_quantity = $_POST['target_quantity'];

    try {
        $stmt = $pdo->prepare("INSERT INTO production_plans (created_by, plan_date, target_quantity, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$created_by, $plan_date, $target_quantity]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Menambahkan rencana produksi untuk tanggal $plan_date"]);

        header('Location: list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menambah rencana produksi: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h1>Tambah Rencana Produksi</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="plan_date" class="form-label">Tanggal Rencana</label>
            <input type="date" class="form-control" id="plan_date" name="plan_date" required>
        </div>
        <div class="mb-3">
            <label for="target_quantity" class="form-label">Jumlah Target</label>
            <input type="number" class="form-control" id="target_quantity" name="target_quantity" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>