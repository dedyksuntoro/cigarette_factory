<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_quality_controls', 'read_quality_controls', 'update_quality_controls', 'delete_quality_controls'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar kontrol kualitas"]);

// Proses filter dan paginasi
$filter_product_id = $_GET['product_id'] ?? '';
$filter_result = $_GET['result'] ?? '';
$filter_batch_no = $_GET['batch_no'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT qc.id, qc.product_id, qc.batch_no, qc.result, qc.notes, qc.qc_date, fg.product_name 
          FROM quality_controls qc 
          JOIN finished_goods fg ON qc.product_id = fg.id 
          WHERE 1=1";
$params = [];

if ($filter_product_id) {
    $query .= " AND qc.product_id = ?";
    $params[] = $filter_product_id;
}
if ($filter_result) {
    $query .= " AND qc.result = ?";
    $params[] = $filter_result;
}
if ($filter_batch_no) {
    $query .= " AND qc.batch_no LIKE ?";
    $params[] = "%$filter_batch_no%";
}

$query .= " ORDER BY qc.qc_date DESC LIMIT ? OFFSET ?";

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
$quality_controls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total data untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM quality_controls qc JOIN finished_goods fg ON qc.product_id = fg.id WHERE 1=1";
$count_params = [];
if ($filter_product_id) {
    $count_query .= " AND qc.product_id = ?";
    $count_params[] = $filter_product_id;
}
if ($filter_result) {
    $count_query .= " AND qc.result = ?";
    $count_params[] = $filter_result;
}
if ($filter_batch_no) {
    $count_query .= " AND qc.batch_no LIKE ?";
    $count_params[] = "%$filter_batch_no%";
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_quality_controls = $stmt->fetchColumn();
$total_pages = ceil($total_quality_controls / $limit);

// Ambil data untuk dropdown filter
$products = $pdo->query("SELECT id, product_name FROM finished_goods")->fetchAll(PDO::FETCH_ASSOC);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';

// URL parameter untuk mempertahankan filter
$base_url = "?product_id=" . urlencode($filter_product_id) . "&result=" . urlencode($filter_result) . "&batch_no=" . urlencode($filter_batch_no) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Manajemen Kontrol Kualitas</h1>
    <?php if (hasPermission($role, ['create_all', 'create_quality_controls'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/quality_controls/add.php" class="btn btn-success mb-3">Tambah Kontrol Kualitas</a>
    <?php endif; ?>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4 col-sm-6">
                <label for="product_id" class="form-label">Produk</label>
                <select class="form-select" id="product_id" name="product_id">
                    <option value="">Semua Produk</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo $filter_product_id == $product['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($product['product_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <label for="result" class="form-label">Hasil</label>
                <select class="form-select" id="result" name="result">
                    <option value="">Semua Hasil</option>
                    <option value="passed" <?php echo $filter_result == 'passed' ? 'selected' : ''; ?>>Lulus</option>
                    <option value="failed" <?php echo $filter_result == 'failed' ? 'selected' : ''; ?>>Gagal</option>
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <label for="batch_no" class="form-label">Nomor Batch</label>
                <input type="text" class="form-control" id="batch_no" name="batch_no" value="<?php echo htmlspecialchars($filter_batch_no); ?>">
            </div>
            <div class="col-md-4 col-sm-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/quality_controls/list.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Tabel Kontrol Kualitas -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Nomor Batch</th>
                    <th>Hasil</th>
                    <th>Catatan</th>
                    <th>Tanggal QC</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quality_controls)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data kontrol kualitas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($quality_controls as $index => $qc): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($qc['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($qc['batch_no']); ?></td>
                            <td><?php echo htmlspecialchars($qc['result'] == 'passed' ? 'Lulus' : 'Gagal'); ?></td>
                            <td><?php echo htmlspecialchars($qc['notes'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($qc['qc_date']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission($role, ['update_all', 'update_quality_controls'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/quality_controls/edit.php?id=<?php echo $qc['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (hasPermission($role, ['delete_all', 'delete_quality_controls'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/quality_controls/delete.php?id=<?php echo $qc['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data kontrol kualitas ini?')">Hapus</a>
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
                Menampilkan <?php echo count($quality_controls); ?> dari <?php echo $total_quality_controls; ?> data
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