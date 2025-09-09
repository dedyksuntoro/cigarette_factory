<?php
session_start();
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../templates/header.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pergerakan stok dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/stock_movements/list.php');
    exit();
}
$movement_id = $_GET['id'];

// Ambil data pergerakan stok
$stmt = $pdo->prepare("SELECT id, type, material_id, finished_good_id, quantity, movement_date FROM stock_movements WHERE id = ?");
$stmt->execute([$movement_id]);
$movement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movement) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/stock_movements/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $material_id = $_POST['material_id'] ?: null;
    $finished_good_id = $_POST['finished_good_id'] ?: null;
    $quantity = $_POST['quantity'];

    // Validasi: salah satu dari material_id atau finished_good_id harus diisi
    if (!$material_id && !$finished_good_id) {
        $error = "Pilih bahan baku atau barang jadi.";
    } else {
        try {
            // Mulai transaksi untuk memastikan konsistensi stok
            $pdo->beginTransaction();

            // Ambil data lama untuk membatalkan efek stok
            $old_quantity = $movement['quantity'];
            $old_type = $movement['type'];
            $old_material_id = $movement['material_id'];
            $old_finished_good_id = $movement['finished_good_id'];

            // Batalkan efek stok lama
            if ($old_material_id) {
                $column = 'stock';
                $table = 'materials';
                $id = $old_material_id;
            } elseif ($old_finished_good_id) {
                $column = 'stock';
                $table = 'finished_goods';
                $id = $old_finished_good_id;
            }

            if ($old_type == 'in') {
                $stmt = $pdo->prepare("UPDATE $table SET $column = $column - ? WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE $table SET $column = $column + ? WHERE id = ?");
            }
            $stmt->execute([$old_quantity, $id]);

            // Update data pergerakan stok
            $stmt = $pdo->prepare("UPDATE stock_movements SET type = ?, material_id = ?, finished_good_id = ?, quantity = ? WHERE id = ?");
            $stmt->execute([$type, $material_id, $finished_good_id, $quantity, $movement_id]);

            // Terapkan efek stok baru
            if ($material_id) {
                $column = 'stock';
                $table = 'materials';
                $id = $material_id;
            } elseif ($finished_good_id) {
                $column = 'stock';
                $table = 'finished_goods';
                $id = $finished_good_id;
            }

            if ($type == 'in') {
                $stmt = $pdo->prepare("UPDATE $table SET $column = $column + ? WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE $table SET $column = $column - ? WHERE id = ?");
            }
            $stmt->execute([$quantity, $id]);

            // Catat log aktivitas
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], "Mengedit pergerakan stok ID: $movement_id"]);

            $pdo->commit();
            header('Location: ' . $_ENV['BASE_URL'] . '/page/stock_movements/list.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal mengedit pergerakan stok: " . $e->getMessage();
        }
    }
}

// Ambil data untuk dropdown
$materials = $pdo->query("SELECT id, name FROM materials")->fetchAll(PDO::FETCH_ASSOC);
$finished_goods = $pdo->query("SELECT id, product_name FROM finished_goods")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h1>Edit Pergerakan Stok</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="type" class="form-label">Tipe</label>
            <select class="form-select" id="type" name="type" required>
                <option value="in" <?php echo $movement['type'] == 'in' ? 'selected' : ''; ?>>Masuk</option>
                <option value="out" <?php echo $movement['type'] == 'out' ? 'selected' : ''; ?>>Keluar</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="material_id" class="form-label">Bahan Baku</label>
            <select class="form-select" id="material_id" name="material_id">
                <option value="">Pilih Bahan Baku (opsional)</option>
                <?php foreach ($materials as $material): ?>
                    <option value="<?php echo $material['id']; ?>" <?php echo $movement['material_id'] == $material['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($material['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="finished_good_id" class="form-label">Barang Jadi</label>
            <select class="form-select" id="finished_good_id" name="finished_good_id">
                <option value="">Pilih Barang Jadi (opsional)</option>
                <?php foreach ($finished_goods as $fg): ?>
                    <option value="<?php echo $fg['id']; ?>" <?php echo $movement['finished_good_id'] == $fg['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($fg['product_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Jumlah</label>
            <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($movement['quantity']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>