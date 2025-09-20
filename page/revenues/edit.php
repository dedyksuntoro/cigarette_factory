<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['update_all', 'update_revenues'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pendapatan dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/revenues/list.php');
    exit();
}
$revenue_id = $_GET['id'];

// Ambil data pendapatan
$stmt = $pdo->prepare("SELECT id, description, amount, revenue_date FROM revenues WHERE id = ?");
$stmt->execute([$revenue_id]);
$revenue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$revenue) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/revenues/list.php');
    exit();
}

// Proses form jika metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $revenue_date = $_POST['revenue_date'];

    // Validasi input
    if (empty($description) || empty($amount) || empty($revenue_date)) {
        $error = "Semua field wajib diisi.";
    } elseif ($amount <= 0) {
        $error = "Jumlah harus lebih dari 0.";
    } elseif (strlen($description) > 200) {
        $error = "Deskripsi tidak boleh lebih dari 200 karakter.";
    } else {
        try {
            // Update data pendapatan
            $stmt = $pdo->prepare("UPDATE revenues SET description = ?, amount = ?, revenue_date = ? WHERE id = ?");
            $stmt->execute([$description, $amount, $revenue_date, $revenue_id]);

            // Catat log aktivitas
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], "Mengedit pendapatan ID: $revenue_id"]);

            header('Location: ' . $_ENV['BASE_URL'] . '/page/revenues/list.php');
            exit();
        } catch (PDOException $e) {
            $error = "Gagal mengedit pendapatan: " . $e->getMessage();
        }
    }
}

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit Pendapatan</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($revenue['description']); ?>" maxlength="200" required>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Jumlah</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?php echo htmlspecialchars($revenue['amount']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="revenue_date" class="form-label">Tanggal Pendapatan</label>
            <input type="date" class="form-control" id="revenue_date" name="revenue_date" value="<?php echo htmlspecialchars($revenue['revenue_date']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>