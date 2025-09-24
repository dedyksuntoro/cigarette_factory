<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_finished_goods', 'read_finished_goods', 'update_finished_goods', 'delete_finished_goods'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar manajemen barang jadi"]);

// Proses filter dan paginasi
$filter_product_name = $_GET['product_name'] ?? '';
$filter_unit = $_GET['unit'] ?? '';
$filter_stock = $_GET['stock'] ?? '';
$filter_created_date = $_GET['created_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah barang jadi per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, product_name, unit, stock, created_at 
          FROM finished_goods 
          WHERE 1=1";
$params = [];

if ($filter_product_name) {
    $query .= " AND product_name LIKE ?";
    $params[] = '%' . $filter_product_name . '%';
}
if ($filter_unit) {
    $query .= " AND unit = ?";
    $params[] = $filter_unit;
}
if ($filter_stock) {
    $query .= " AND stock = ?";
    $params[] = $filter_stock;
}
if ($filter_created_date) {
    $query .= " AND DATE(created_at) = ?";
    $params[] = $filter_created_date;
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

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
$finished_goods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total barang jadi untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM finished_goods WHERE 1=1";
$count_params = [];
if ($filter_product_name) {
    $count_query .= " AND product_name LIKE ?";
    $count_params[] = '%' . $filter_product_name . '%';
}
if ($filter_unit) {
    $count_query .= " AND unit = ?";
    $count_params[] = $filter_unit;
}
if ($filter_stock) {
    $count_query .= " AND stock = ?";
    $count_params[] = $filter_stock;
}
if ($filter_created_date) {
    $count_query .= " AND DATE(created_at) = ?";
    $count_params[] = $filter_created_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_goods = $stmt->fetchColumn();
$total_pages = ceil($total_goods / $limit);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';

// URL parameter untuk mempertahankan filter
$base_url = "?product_name=" . urlencode($filter_product_name) . "&unit=" . urlencode($filter_unit) . "&stock=" . urlencode($filter_stock) . "&created_date=" . urlencode($filter_created_date) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Manajemen Barang Jadi</h1>
    <?php if (hasPermission($role, ['create_all', 'create_finished_goods'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/add.php" class="btn btn-success mb-3">Tambah Barang Jadi</a>
    <?php endif; ?>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <label for="product_name" class="form-label">Nama Produk</label>
                <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($filter_product_name); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="unit" class="form-label">Unit</label>
                <input type="text" class="form-control" id="unit" name="unit" value="<?php echo htmlspecialchars($filter_unit); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="stock" class="form-label">Stok</label>
                <input type="number" step="0.01" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($filter_stock); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="created_date" class="form-label">Tanggal Dibuat</label>
                <input type="date" class="form-control" id="created_date" name="created_date" value="<?php echo htmlspecialchars($filter_created_date); ?>">
            </div>
            <div class="col-md-3 col-sm-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/list.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Tabel Barang Jadi -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Produk</th>
                    <th>Unit</th>
                    <th>Stok</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($finished_goods)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data barang jadi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($finished_goods as $index => $good): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($good['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($good['unit'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($good['stock'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($good['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission($role, ['update_all', 'update_finished_goods'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/edit.php?id=<?php echo $good['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (hasPermission($role, ['delete_all', 'delete_finished_goods'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/delete.php?id=<?php echo $good['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus barang jadi ini?')">Hapus</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginasi -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Pagination" class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Menampilkan <?php echo count($finished_goods); ?> dari <?php echo $total_goods; ?> data
            </div>
            <ul class="pagination mb-0 flex-wrap">
                <!-- Previous Button -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $page > 1 ? $base_url . ($page - 1) : '#'; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <!-- First Page -->
                <?php if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url . '1'; ?>">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Last Page -->
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url . $total_pages; ?>"><?php echo $total_pages; ?></a>
                    </li>
                <?php endif; ?>

                <!-- Next Button -->
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $page < $total_pages ? $base_url . ($page + 1) : '#'; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>