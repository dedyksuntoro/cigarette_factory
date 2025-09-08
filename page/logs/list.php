<?php
session_start();
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../templates/header.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
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
?>

<div class="container mt-4">
    <h1>Log Aktivitas</h1>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Tanggal Awal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            </div>
            <div class="col-md-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($filter_username); ?>">
            </div>
            <div class="col-md-3">
                <label for="action" class="form-label">Aksi (Kata Kunci)</label>
                <input type="text" class="form-control" id="action" name="action" value="<?php echo htmlspecialchars($filter_action); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/logs/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Log -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Pengguna</th>
                <th>Aksi</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" class="text-center">Tidak ada data log.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                        <td><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['log_time']); ?></td>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo urlencode($filter_start_date); ?>&end_date=<?php echo urlencode($filter_end_date); ?>&username=<?php echo urlencode($filter_username); ?>&action=<?php echo urlencode($filter_action); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>