<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_invoices', 'read_invoices', 'update_invoices', 'delete_invoices'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar faktur"]);

// Proses filter dan paginasi
$filter_status = $_GET['status'] ?? '';
$filter_distribution_id = $_GET['distribution_id'] ?? '';
$filter_date = $_GET['created_at'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah faktur per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT i.id, i.distribution_id, i.amount, i.status, i.created_at, d.destination 
          FROM invoices i 
          JOIN distributions d ON i.distribution_id = d.id 
          WHERE 1=1";
$params = [];

if ($filter_status) {
    $query .= " AND i.status = ?";
    $params[] = $filter_status;
}
if ($filter_distribution_id) {
    $query .= " AND i.distribution_id = ?";
    $params[] = $filter_distribution_id;
}
if ($filter_date) {
    $query .= " AND DATE(i.created_at) = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";

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
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total faktur untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM invoices i JOIN distributions d ON i.distribution_id = d.id WHERE 1=1";
$count_params = [];
if ($filter_status) {
    $count_query .= " AND i.status = ?";
    $count_params[] = $filter_status;
}
if ($filter_distribution_id) {
    $count_query .= " AND i.distribution_id = ?";
    $count_params[] = $filter_distribution_id;
}
if ($filter_date) {
    $count_query .= " AND DATE(i.created_at) = ?";
    $count_params[] = $filter_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_invoices = $stmt->fetchColumn();
$total_pages = ceil($total_invoices / $limit);

// Ambil data untuk dropdown filter
$distributions = $pdo->query("SELECT id, destination FROM distributions")->fetchAll(PDO::FETCH_ASSOC);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';

// URL parameter untuk mempertahankan filter
$base_url = "?status=" . urlencode($filter_status) . "&distribution_id=" . urlencode($filter_distribution_id) . "&created_at=" . urlencode($filter_date) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Faktur</h1>
    <?php if (hasPermission($role, ['create_all', 'create_invoices'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/invoices/add.php" class="btn btn-success mb-3">Tambah Faktur</a>
    <?php endif; ?>
    <a class="btn btn-primary mb-3" data-bs-toggle="collapse" href="#collapsePencarian" role="button" aria-expanded="false" aria-controls="collapsePencarian">
        Filter Data
    </a>

    <!-- Form Filter -->
    <div class="collapse pb-3" id="collapsePencarian">
        <div class="card card-body shadow">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4 col-sm-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="unpaid" <?php echo $filter_status == 'unpaid' ? 'selected' : ''; ?>>Belum Dibayar</option>
                            <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label for="distribution_id" class="form-label">Distribusi</label>
                        <select class="form-select" id="distribution_id" name="distribution_id">
                            <option value="">Semua Distribusi</option>
                            <?php foreach ($distributions as $distribution): ?>
                                <option value="<?php echo $distribution['id']; ?>" <?php echo $filter_distribution_id == $distribution['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($distribution['destination']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label for="created_at" class="form-label">Tanggal Dibuat</label>
                        <input type="date" class="form-control" id="created_at" name="created_at" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <hr />
                    <div class="col-md-4 col-sm-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/invoices/list.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Faktur -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Distribusi</th>
                    <th>Jumlah Tagihan</th>
                    <th>Status</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data faktur.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $index => $invoice): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($invoice['destination']); ?></td>
                            <td><?php echo number_format($invoice['amount'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($invoice['status'] == 'unpaid' ? 'Belum Dibayar' : 'Sudah Dibayar'); ?></td>
                            <td><?php echo htmlspecialchars($invoice['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission($role, ['update_all', 'update_invoices'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/invoices/edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (hasPermission($role, ['delete_all', 'delete_invoices'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/invoices/delete.php?id=<?php echo $invoice['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus faktur ini?')">Hapus</a>
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
                Menampilkan <?php echo count($invoices); ?> dari <?php echo $total_invoices; ?> data
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