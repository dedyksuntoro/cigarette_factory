<?php
session_start();
require_once __DIR__.'/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin atau gudang
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'gudang'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $unit = $_POST['unit'];
    $stock = $_POST['stock'] ?: 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO materials (name, unit, stock, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $unit, $stock]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Menambahkan bahan baku baru: $name"]);

        header('Location: '.$_ENV['BASE_URL'].'/page/materials/list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menambah bahan baku: " . $e->getMessage();
    }
}

require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Tambah Bahan Baku</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Nama Bahan</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="unit" class="form-label">Unit</label>
            <input type="text" class="form-control" id="unit" name="unit">
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stok Awal</label>
            <input type="number" step="0.01" class="form-control" id="stock" name="stock" value="0">
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/materials/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
