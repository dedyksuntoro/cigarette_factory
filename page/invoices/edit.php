<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID faktur dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/invoices/list.php');
    exit();
}
$invoice_id = $_GET['id'];

// Ambil data faktur
$stmt = $pdo->prepare("SELECT id, distribution_id, amount, status FROM invoices WHERE id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/invoices/list.php');
    exit();
}

// Ambil data untuk dropdown
$distributions = $pdo->query("SELECT id, destination FROM distributions")->fetchAll(PDO::FETCH_ASSOC);

// Proses form jika metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $distribution_id = $_POST['distribution_id'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];

    // Validasi input
    if (empty($distribution_id) || empty($amount)) {
        $error = "Semua field wajib diisi.";
    } elseif ($amount <= 0) {
        $error = "Jumlah tagihan harus lebih dari 0.";
    } else {
        try {
            // Update data faktur
            $stmt = $pdo->prepare("UPDATE invoices SET distribution_id = ?, amount = ?, status = ? WHERE id = ?");
            $stmt->execute([$distribution_id, $amount, $status, $invoice_id]);

            // Catat log aktivitas
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], "Mengedit faktur ID: $invoice_id"]);

            header('Location: ' . $_ENV['BASE_URL'] . '/page/invoices/list.php');
            exit();
        } catch (PDOException $e) {
            $error = "Gagal mengedit faktur: " . $e->getMessage();
        }
    }
}

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit Faktur</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="distribution_id" class="form-label">Distribusi</label>
            <select class="form-select" id="distribution_id" name="distribution_id" required>
                <option value="">Pilih Distribusi</option>
                <?php foreach ($distributions as $distribution): ?>
                    <option value="<?php echo $distribution['id']; ?>" <?php echo $invoice['distribution_id'] == $distribution['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($distribution['destination']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Jumlah Tagihan</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?php echo htmlspecialchars($invoice['amount']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="unpaid" <?php echo $invoice['status'] == 'unpaid' ? 'selected' : ''; ?>>Belum Dibayar</option>
                <option value="paid" <?php echo $invoice['status'] == 'paid' ? 'selected' : ''; ?>>Sudah Dibayar</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/invoices/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>