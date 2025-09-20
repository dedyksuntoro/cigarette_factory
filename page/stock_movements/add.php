<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'create_stock_movements'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil data untuk dropdown
$materials = $pdo->query("SELECT id, name FROM materials")->fetchAll(PDO::FETCH_ASSOC);
$finished_goods = $pdo->query("SELECT id, product_name FROM finished_goods")->fetchAll(PDO::FETCH_ASSOC);

// Proses form jika metode POST
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

            // Insert data pergerakan stok
            $stmt = $pdo->prepare("INSERT INTO stock_movements (type, material_id, finished_good_id, quantity, movement_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$type, $material_id, $finished_good_id, $quantity]);

            // Update stok sesuai tipe pergerakan
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
            $stmt->execute([$_SESSION['user_id'], "Menambahkan pergerakan stok: $type untuk " . ($material_id ? "bahan baku ID $material_id" : "barang jadi ID $finished_good_id")]);

            $pdo->commit();
            header('Location: ' . $_ENV['BASE_URL'] . '/page/stock_movements/list.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal menambah pergerakan stok: " . $e->getMessage();
        }
    }
}

// Sertakan header setelah logika pengalihan selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Tambah Pergerakan Stok</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="type" class="form-label">Tipe</label>
            <select class="form-select" id="type" name="type" required>
                <option value="in">Masuk</option>
                <option value="out">Keluar</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="material_id" class="form-label">Bahan Baku</label>
            <select class="form-select" id="material_id" name="material_id">
                <option value="">Pilih Bahan Baku (opsional)</option>
                <?php foreach ($materials as $material): ?>
                    <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="finished_good_id" class="form-label">Barang Jadi</label>
            <select class="form-select" id="finished_good_id" name="finished_good_id">
                <option value="">Pilih Barang Jadi (opsional)</option>
                <?php foreach ($finished_goods as $fg): ?>
                    <option value="<?php echo $fg['id']; ?>"><?php echo htmlspecialchars($fg['product_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Jumlah</label>
            <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>