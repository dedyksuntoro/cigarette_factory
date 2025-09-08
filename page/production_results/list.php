<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../templates/header.php';

// Cek apakah pengguna sudah login dan memiliki peran admin atau supervisor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar hasil produksi"]);

// Proses filter dan paginasi
$filter_plan_id = $_GET['plan_id'] ?? '';
$filter_actual_quantity = $_GET['actual_quantity'] ?? '';
$filter_efficiency = $_GET['efficiency'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah hasil per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT pr.id, pr.plan_id, pp.plan_date, pr.actual_quantity, pr.efficiency, pr.created_at 
          FROM production_results pr 
          LEFT JOIN production_plans pp ON pr.plan_id = pp.id 
          WHERE 1=1";
$params = [];

if ($filter_plan_id) {
    $query .= " AND pr.plan_id = ?";
    $params[] = $filter_plan_id;
}
if ($filter_actual_quantity) {
    $query .= " AND pr.actual_quantity = ?";
    $params[] = $filter_actual_quantity;
}
if ($filter_efficiency) {
    $query .= " AND pr.efficiency = ?";
    $params[] = $filter_efficiency;
}

$query .= " ORDER BY pr.created_at DESC LIMIT ? OFFSET ?";

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
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total hasil untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM production_results pr 
                LEFT JOIN production_plans pp ON pr.plan_id = pp.id WHERE 1=1";
$count_params = [];
if ($filter_plan_id) {
    $count_query .= " AND pr.plan_id = ?";
    $count_params[] = $filter_plan_id;
}
if ($filter_actual_quantity) {
    $count_query .= " AND pr.actual_quantity = ?";
    $count_params[] = $filter_actual_quantity;
}
if ($filter_efficiency) {
    $count_query .= " AND pr.efficiency = ?";
    $count_params[] = $filter_efficiency;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_results = $stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);
?>

<div class="container mt-4">
    <h1>Hasil Produksi</h1>
    <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/add.php" class="btn btn-success mb-3">Tambah Hasil Produksi</a>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="plan_id" class="form-label">ID Rencana</label>
                <input type="number" class="form-control" id="plan_id" name="plan_id" value="<?php echo htmlspecialchars($filter_plan_id); ?>">
            </div>
            <div class="col-md-4">
                <label for="actual_quantity" class="form-label">Jumlah Aktual</label>
                <input type="number" class="form-control" id="actual_quantity" name="actual_quantity" value="<?php echo htmlspecialchars($filter_actual_quantity); ?>">
            </div>
            <div class="col-md-4">
                <label for="efficiency" class="form-label">Efisiensi (%)</label>
                <input type="number" step="0.01" class="form-control" id="efficiency" name="efficiency" value="<?php echo htmlspecialchars($filter_efficiency); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Hasil Produksi -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>ID Rencana</th>
                <th>Tanggal Rencana</th>
                <th>Jumlah Aktual</th>
                <th>Efisiensi (%)</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="7" class="text-center">Tidak ada data hasil produksi.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['id']); ?></td>
                        <td><?php echo htmlspecialchars($result['plan_id'] ?? 'Tidak terkait'); ?></td>
                        <td><?php echo htmlspecialchars($result['plan_date'] ?? 'Tidak terkait'); ?></td>
                        <td><?php echo htmlspecialchars($result['actual_quantity']); ?></td>
                        <td><?php echo $result['efficiency'] !== null ? htmlspecialchars(number_format($result['efficiency'], 2)) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($result['created_at']); ?></td>
                        <td>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/edit.php?id=<?php echo $result['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/delete.php?id=<?php echo $result['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus hasil ini?')">Hapus</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&plan_id=<?php echo urlencode($filter_plan_id); ?>&actual_quantity=<?php echo urlencode($filter_actual_quantity); ?>&efficiency=<?php echo urlencode($filter_efficiency); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
