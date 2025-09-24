<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_distributions', 'read_distributions', 'update_distributions', 'delete_distributions'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar distribusi"]);

// Proses filter dan paginasi
$filter_status = $_GET['status'] ?? '';
$filter_product_id = $_GET['product_id'] ?? '';
$filter_date = $_GET['created_at'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah distribusi per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT d.id, d.product_id, d.quantity, d.destination, d.status, d.created_at, fg.product_name 
          FROM distributions d 
          JOIN finished_goods fg ON d.product_id = fg.id 
          WHERE 1=1";
$params = [];

if ($filter_status) {
    $query .= " AND d.status = ?";
    $params[] = $filter_status;
}
if ($filter_product_id) {
    $query .= " AND d.product_id = ?";
    $params[] = $filter_product_id;
}
if ($filter_date) {
    $query .= " AND DATE(d.created_at) = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY d.created_at DESC LIMIT ? OFFSET ?";

// Persiapkan statement
$stmt = $pdo->prepare($query);

// Ikat parameter filter
$param_count = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_count, $param, PDO::PARAM_STR);
    $param_count++;
}

// Ikat parameter LIMIT dan OFFSET sebagai integer
$stmt->bindValue($param_count, (int)$limit, PDO::PARAM_INT);
$stmt->bindValue($param_count + 1, (int)$offset, PDO::PARAM_INT);

// Eksekusi query
$stmt->execute();
$distributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total distribusi untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM distributions WHERE 1=1";
$count_params = [];
if ($filter_status) {
    $count_query .= " AND status = ?";
    $count_params[] = $filter_status;
}
if ($filter_product_id) {
    $count_query .= " AND product_id = ?";
    $count_params[] = $filter_product_id;
}
if ($filter_date) {
    $count_query .= " AND DATE(created_at) = ?";
    $count_params[] = $filter_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_distributions = $stmt->fetchColumn();
$total_pages = ceil($total_distributions / $limit);

// Ambil data untuk dropdown filter
$products = $pdo->query("SELECT id, product_name FROM finished_goods")->fetchAll(PDO::FETCH_ASSOC);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Manajemen Distribusi</h1>
    <?php if (hasPermission($role, ['create_all', 'create_distributions'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/distributions/add.php" class="btn btn-success mb-3">Tambah Distribusi</a>
    <?php endif; ?>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="shipped" <?php echo $filter_status == 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                    <option value="delivered" <?php echo $filter_status == 'delivered' ? 'selected' : ''; ?>>Terkirim</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="product_id" class="form-label">Produk</label>
                <select class="form-select" id="product_id" name="product_id">
                    <option value="">Semua Produk</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo $filter_product_id == $product['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($product['product_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="created_at" class="form-label">Tanggal Dibuat</label>
                <input type="date" class="form-control" id="created_at" name="created_at" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/distributions/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Distribusi -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Tujuan</th>
                <th>Status</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($distributions)): ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data distribusi.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($distributions as $index => $distribution): ?>
                    <tr>
                        <td><?php echo ($offset + $index + 1); ?></td>
                        <td><?php echo htmlspecialchars($distribution['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($distribution['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($distribution['destination']); ?></td>
                        <td><?php echo htmlspecialchars($distribution['status'] == 'pending' ? 'Pending' : ($distribution['status'] == 'shipped' ? 'Dikirim' : 'Terkirim')); ?></td>
                        <td><?php echo htmlspecialchars($distribution['created_at']); ?></td>
                        <td>
                            <?php if (hasPermission($role, ['update_all', 'update_distributions'])): ?>
                                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/distributions/edit.php?id=<?php echo $distribution['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <?php endif; ?>
                            <?php if (hasPermission($role, ['delete_all', 'delete_distributions'])): ?>
                                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/distributions/delete.php?id=<?php echo $distribution['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus distribusi ini?')">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginasi -->
    <nav aria-label="Pagination">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&product_id=<?php echo urlencode($filter_product_id); ?>&created_at=<?php echo urlencode($filter_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>