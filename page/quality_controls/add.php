<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'create_quality_controls'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil data untuk dropdown
$products = $pdo->query("SELECT id, product_name FROM finished_goods")->fetchAll(PDO::FETCH_ASSOC);

// Proses form jika metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $batch_no = $_POST['batch_no'];
    $result = $_POST['result'];
    $notes = $_POST['notes'];

    // Validasi input
    if (empty($product_id) || empty($batch_no) || empty($result)) {
        $error = "Produk, nomor batch, dan hasil wajib diisi.";
    } elseif (strlen($batch_no) > 50) {
        $error = "Nomor batch tidak boleh lebih dari 50 karakter.";
    } else {
        try {
            // Insert data kontrol kualitas
            $stmt = $pdo->prepare("INSERT INTO quality_controls (product_id, batch_no, result, notes, qc_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$product_id, $batch_no, $result, $notes]);

            // Catat log aktivitas
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], "Menambahkan kontrol kualitas untuk batch: $batch_no"]);

            header('Location: ' . $_ENV['BASE_URL'] . '/page/quality_controls/list.php');
            exit();
        } catch (PDOException $e) {
            $error = "Gagal menambah kontrol kualitas: " . $e->getMessage();
        }
    }
}

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Tambah Kontrol Kualitas</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="product_id" class="form-label">Produk</label>
            <select class="form-select" id="product_id" name="product_id" required>
                <option value="">Pilih Produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="batch_no" class="form-label">Nomor Batch</label>
            <input type="text" class="form-control" id="batch_no" name="batch_no" maxlength="50" required>
        </div>
        <div class="mb-3">
            <label for="result" class="form-label">Hasil</label>
            <select class="form-select" id="result" name="result" required>
                <option value="">Pilih Hasil</option>
                <option value="passed">Lulus</option>
                <option value="failed">Gagal</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="notes" class="form-label">Catatan</label>
            <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/quality_controls/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>