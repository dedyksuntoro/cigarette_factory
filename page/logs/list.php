<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../templates/header.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['read_all', 'read_logs'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Proses filter dan paginasi
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';
$filter_username = $_GET['username'] ?? '';
$filter_action = $_GET['action'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah log per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT l.id, l.user_id, u.username, l.action, l.log_time 
          FROM logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($filter_start_date) {
    $query .= " AND l.log_time >= ?";
    $params[] = $filter_start_date . ' 00:00:00';
}
if ($filter_end_date) {
    $query .= " AND l.log_time <= ?";
    $params[] = $filter_end_date . ' 23:59:59';
}
if ($filter_username) {
    $query .= " AND u.username LIKE ?";
    $params[] = '%' . $filter_username . '%';
}
if ($filter_action) {
    $query .= " AND l.action LIKE ?";
    $params[] = '%' . $filter_action . '%';
}

$query .= " ORDER BY l.log_time DESC LIMIT ? OFFSET ?";

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
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total log untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM logs l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
$count_params = [];
if ($filter_start_date) {
    $count_query .= " AND l.log_time >= ?";
    $count_params[] = $filter_start_date . ' 00:00:00';
}
if ($filter_end_date) {
    $count_query .= " AND l.log_time <= ?";
    $count_params[] = $filter_end_date . ' 23:59:59';
}
if ($filter_username) {
    $count_query .= " AND u.username LIKE ?";
    $count_params[] = '%' . $filter_username . '%';
}
if ($filter_action) {
    $count_query .= " AND l.action LIKE ?";
    $count_params[] = '%' . $filter_action . '%';
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_logs = $stmt->fetchColumn();
$total_pages = ceil($total_logs / $limit);

// URL parameter untuk mempertahankan filter
$base_url = "?start_date=" . urlencode($filter_start_date) . "&end_date=" . urlencode($filter_end_date) . "&username=" . urlencode($filter_username) . "&action=" . urlencode($filter_action) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Log Aktivitas</h1>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <label for="start_date" class="form-label">Tanggal Awal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="end_date" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($filter_username); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="action" class="form-label">Aksi (Kata Kunci)</label>
                <input type="text" class="form-control" id="action" name="action" value="<?php echo htmlspecialchars($filter_action); ?>">
            </div>
            <div class="col-md-3 col-sm-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/logs/list.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Tabel Log -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center">Tidak ada data log.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $index => $log): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['log_time']); ?></td>
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
                Menampilkan <?php echo count($logs); ?> dari <?php echo $total_logs; ?> data
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