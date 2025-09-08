<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../templates/header.php';

// Cek apakah pengguna sudah login dan memiliki peran admin atau gudang
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'gudang'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID barang jadi dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/finished_goods/list.php');
    exit();
}
$good_id = $_GET['id'];

// Ambil data barang jadi
$stmt = $pdo->prepare("SELECT id, product_name, unit, stock FROM finished_goods WHERE id = ?");
$stmt->execute([$good_id]);
$good = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$good) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/finished_goods/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $unit = $_POST['unit'] ?: null; // Unit boleh kosong
    $stock = $_POST['stock'] ?: 0; // Stok boleh 0

    try {
        $stmt = $pdo->prepare("UPDATE finished_goods SET product_name = ?, unit = ?, stock = ? WHERE id = ?");
        $stmt->execute([$product_name, $unit, $stock, $good_id]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Mengedit barang jadi: $product_name"]);

        header('Location: ' . $_ENV['BASE_URL'] . '/page/finished_goods/list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal mengedit barang jadi: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h1>Edit Barang Jadi</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="product_name" class="form-label">Nama Produk</label>
            <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($good['product_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="unit" class="form-label">Unit (Opsional)</label>
            <input type="text" class="form-control" id="unit" name="unit" value="<?php echo htmlspecialchars($good['unit'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stok</label>
            <input type="number" step="0.01" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($good['stock']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
