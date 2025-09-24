<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_production_plans', 'read_production_plans', 'update_production_plans', 'delete_production_plans'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar perencanaan produksi"]);

// Proses filter dan paginasi
$filter_name = $_GET['name'] ?? '';
$filter_plan_date = $_GET['plan_date'] ?? '';
$filter_username = $_GET['username'] ?? '';
$filter_target_quantity = $_GET['target_quantity'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah rencana per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT pp.id, pp.created_by, u.username, pp.plan_date, pp.name, pp.target_quantity, pp.created_at 
          FROM production_plans pp 
          LEFT JOIN users u ON pp.created_by = u.id 
          WHERE 1=1";
$params = [];

if ($filter_name) {
    $query .= " AND pp.name LIKE ?";
    $params[] = '%' . $filter_name . '%';
}
if ($filter_plan_date) {
    $query .= " AND pp.plan_date = ?";
    $params[] = $filter_plan_date;
}
if ($filter_username) {
    $query .= " AND u.username LIKE ?";
    $params[] = '%' . $filter_username . '%';
}
if ($filter_target_quantity) {
    $query .= " AND pp.target_quantity = ?";
    $params[] = $filter_target_quantity;
}

$query .= " ORDER BY pp.created_at DESC LIMIT ? OFFSET ?";

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
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total rencana untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM production_plans pp LEFT JOIN users u ON pp.created_by = u.id WHERE 1=1";
$count_params = [];

if ($filter_name) {
    $count_query .= " AND pp.name LIKE ?";
    $count_params[] = '%' . $filter_name . '%';
}
if ($filter_plan_date) {
    $count_query .= " AND pp.plan_date = ?";
    $count_params[] = $filter_plan_date;
}
if ($filter_username) {
    $count_query .= " AND u.username LIKE ?";
    $count_params[] = '%' . $filter_username . '%';
}
if ($filter_target_quantity) {
    $count_query .= " AND pp.target_quantity = ?";
    $count_params[] = $filter_target_quantity;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_plans = $stmt->fetchColumn();
$total_pages = ceil($total_plans / $limit);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';

// URL parameter untuk mempertahankan filter
$base_url = "?name=" . urlencode($filter_name) . "&plan_date=" . urlencode($filter_plan_date) . "&username=" . urlencode($filter_username) . "&target_quantity=" . urlencode($filter_target_quantity) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Perencanaan Produksi</h1>
    <?php if (hasPermission($role, ['create_all', 'create_production_plans'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/add.php" class="btn btn-success mb-3">Tambah Rencana Produksi</a>
    <?php endif; ?>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <label for="name" class="form-label">Nama Rencana</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="plan_date" class="form-label">Tanggal Rencana</label>
                <input type="date" class="form-control" id="plan_date" name="plan_date" value="<?php echo htmlspecialchars($filter_plan_date); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="username" class="form-label">Pembuat Rencana</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($filter_username); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="target_quantity" class="form-label">Jumlah Target</label>
                <input type="number" class="form-control" id="target_quantity" name="target_quantity" value="<?php echo htmlspecialchars($filter_target_quantity); ?>">
            </div>
            <div class="col-md-3 col-sm-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/list.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Tabel Rencana Produksi -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Pembuat</th>
                    <th>Nama Rencana</th>
                    <th>Tanggal Rencana</th>
                    <th>Jumlah Target</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plans)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data rencana produksi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($plans as $index => $plan): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($plan['username'] ?? 'Tidak terkait'); ?></td>
                            <td><?php echo htmlspecialchars($plan['name'] ?? 'Tidak terkait'); ?></td>
                            <td><?php echo htmlspecialchars($plan['plan_date']); ?></td>
                            <td><?php echo htmlspecialchars($plan['target_quantity']); ?></td>
                            <td><?php echo htmlspecialchars($plan['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission($role, ['update_all', 'update_production_plans'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/edit.php?id=<?php echo $plan['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (hasPermission($role, ['delete_all', 'delete_production_plans'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/delete.php?id=<?php echo $plan['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus rencana ini?')">Hapus</a>
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
                Menampilkan <?php echo count($plans); ?> dari <?php echo $total_plans; ?> data
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