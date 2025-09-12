<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID distribusi dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/distributions/list.php');
    exit();
}
$distribution_id = $_GET['id'];

// Ambil data distribusi
$stmt = $pdo->prepare("SELECT id, product_id, quantity, destination, status FROM distributions WHERE id = ?");
$stmt->execute([$distribution_id]);
$distribution = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$distribution) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/distributions/list.php');
    exit();
}

// Ambil data untuk dropdown
$products = $pdo->query("SELECT id, product_name, stock FROM finished_goods")->fetchAll(PDO::FETCH_ASSOC);

// Proses form jika metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $destination = $_POST['destination'];
    $status = $_POST['status'];

    // Validasi input
    if (empty($product_id) || empty($quantity) || empty($destination)) {
        $error = "Semua field wajib diisi.";
    } elseif ($quantity <= 0) {
        $error = "Jumlah harus lebih dari 0.";
    } else {
        // Cek stok produk
        $stmt = $pdo->prepare("SELECT stock FROM finished_goods WHERE id = ?");
        $stmt->execute([$product_id]);
        $current_stock = $stmt->fetchColumn();

        // Hitung stok yang tersedia setelah mengembalikan jumlah lama
        $available_stock = $current_stock + $distribution['quantity'];

        if ($quantity > $available_stock) {
            $error = "Jumlah distribusi melebihi stok yang tersedia.";
        } else {
            try {
                // Mulai transaksi
                $pdo->beginTransaction();

                // Kembalikan stok lama ke barang jadi
                $stmt = $pdo->prepare("UPDATE finished_goods SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$distribution['quantity'], $distribution['product_id']]);

                // Update data distribusi
                $stmt = $pdo->prepare("UPDATE distributions SET product_id = ?, quantity = ?, destination = ?, status = ? WHERE id = ?");
                $stmt->execute([$product_id, $quantity, $destination, $status, $distribution_id]);

                // Kurangi stok baru dari barang jadi
                $stmt = $pdo->prepare("UPDATE finished_goods SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);

                // Catat log aktivitas
                $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], "Mengedit distribusi ID: $distribution_id"]);

                $pdo->commit();
                header('Location: ' . $_ENV['BASE_URL'] . '/page/distributions/list.php');
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Gagal mengedit distribusi: " . $e->getMessage();
            }
        }
    }
}

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit Distribusi</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="product_id" class="form-label">Produk</label>
            <select class="form-select" id="product_id" name="product_id" required>
                <option value="">Pilih Produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" <?php echo $distribution['product_id'] == $product['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($product['product_name']) . " (Stok: {$product['stock']})"; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Jumlah</label>
            <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($distribution['quantity']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="destination" class="form-label">Tujuan</label>
            <input type="text" class="form-control" id="destination" name="destination" value="<?php echo htmlspecialchars($distribution['destination']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="pending" <?php echo $distribution['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="shipped" <?php echo $distribution['status'] == 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                <option value="delivered" <?php echo $distribution['status'] == 'delivered' ? 'selected' : ''; ?>>Terkirim</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/distributions/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>