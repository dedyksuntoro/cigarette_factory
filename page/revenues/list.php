<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_revenues', 'read_revenues', 'update_revenues', 'delete_revenues'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar pendapatan"]);

// Proses filter dan paginasi
$filter_description = $_GET['description'] ?? '';
$filter_revenue_date = $_GET['revenue_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah pendapatan per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, description, amount, revenue_date, created_at 
          FROM revenues 
          WHERE 1=1";
$params = [];

if ($filter_description) {
    $query .= " AND description LIKE ?";
    $params[] = "%$filter_description%";
}
if ($filter_revenue_date) {
    $query .= " AND revenue_date = ?";
    $params[] = $filter_revenue_date;
}

$query .= " ORDER BY revenue_date DESC LIMIT ? OFFSET ?";

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
$revenues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pendapatan untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM revenues WHERE 1=1";
$count_params = [];
if ($filter_description) {
    $count_query .= " AND description LIKE ?";
    $count_params[] = "%$filter_description%";
}
if ($filter_revenue_date) {
    $count_query .= " AND revenue_date = ?";
    $count_params[] = $filter_revenue_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_revenues = $stmt->fetchColumn();
$total_pages = ceil($total_revenues / $limit);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';

// URL parameter untuk mempertahankan filter
$base_url = "?description=" . urlencode($filter_description) . "&revenue_date=" . urlencode($filter_revenue_date) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Pendapatan</h1>
    <?php if (hasPermission($role, ['create_all', 'create_revenues'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/add.php" class="btn btn-success mb-3">Tambah Pendapatan</a>
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
                        <label for="description" class="form-label">Deskripsi</label>
                        <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($filter_description); ?>">
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label for="revenue_date" class="form-label">Tanggal Pendapatan</label>
                        <input type="date" class="form-control" id="revenue_date" name="revenue_date" value="<?php echo htmlspecialchars($filter_revenue_date); ?>">
                    </div>
                    <hr />
                    <div class="col-md-4 col-sm-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/list.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Pendapatan -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Deskripsi</th>
                    <th>Jumlah</th>
                    <th>Tanggal Pendapatan</th>
                    <th>Dibuat Pada</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($revenues)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data pendapatan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($revenues as $index => $revenue): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($revenue['description']); ?></td>
                            <td><?php echo number_format($revenue['amount'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($revenue['revenue_date']); ?></td>
                            <td><?php echo htmlspecialchars($revenue['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission($role, ['update_all', 'update_revenues'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/edit.php?id=<?php echo $revenue['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (hasPermission($role, ['delete_all', 'delete_revenues'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/delete.php?id=<?php echo $revenue['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pendapatan ini?')">Hapus</a>
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
                Menampilkan <?php echo count($revenues); ?> dari <?php echo $total_revenues; ?> data
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