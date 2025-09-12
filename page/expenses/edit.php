<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pengeluaran dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/expenses/list.php');
    exit();
}
$expense_id = $_GET['id'];

// Ambil data pengeluaran
$stmt = $pdo->prepare("SELECT id, description, amount, expense_date FROM expenses WHERE id = ?");
$stmt->execute([$expense_id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$expense) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/expenses/list.php');
    exit();
}

// Proses form jika metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $expense_date = $_POST['expense_date'];

    // Validasi input
    if (empty($description) || empty($amount) || empty($expense_date)) {
        $error = "Semua field wajib diisi.";
    } elseif ($amount <= 0) {
        $error = "Jumlah harus lebih dari 0.";
    } elseif (strlen($description) > 200) {
        $error = "Deskripsi tidak boleh lebih dari 200 karakter.";
    } else {
        try {
            // Update data pengeluaran
            $stmt = $pdo->prepare("UPDATE expenses SET description = ?, amount = ?, expense_date = ? WHERE id = ?");
            $stmt->execute([$description, $amount, $expense_date, $expense_id]);

            // Catat log aktivitas
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], "Mengedit pengeluaran ID: $expense_id"]);

            header('Location: ' . $_ENV['BASE_URL'] . '/page/expenses/list.php');
            exit();
        } catch (PDOException $e) {
            $error = "Gagal mengedit pengeluaran: " . $e->getMessage();
        }
    }
}

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit Pengeluaran</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($expense['description']); ?>" maxlength="200" required>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Jumlah</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="expense_date" class="form-label">Tanggal Pengeluaran</label>
            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo htmlspecialchars($expense['expense_date']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/expenses/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>