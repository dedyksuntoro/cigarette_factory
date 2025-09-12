<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
?>

<div class="container mt-4">
    <h1>Manajemen Pendapatan</h1>
    <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/add.php" class="btn btn-success mb-3">Tambah Pendapatan</a>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="description" class="form-label">Deskripsi</label>
                <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($filter_description); ?>">
            </div>
            <div class="col-md-4">
                <label for="revenue_date" class="form-label">Tanggal Pendapatan</label>
                <input type="date" class="form-control" id="revenue_date" name="revenue_date" value="<?php echo htmlspecialchars($filter_revenue_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Pendapatan -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Deskripsi</th>
                <th>Jumlah</th>
                <th>Tanggal Pendapatan</th>
                <th>Dibuat Pada</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($revenues)): ?>
                <tr><td colspan="6" class="text-center">Tidak ada data pendapatan.</td></tr>
            <?php else: ?>
                <?php foreach ($revenues as $revenue): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($revenue['id']); ?></td>
                        <td><?php echo htmlspecialchars($revenue['description']); ?></td>
                        <td><?php echo number_format($revenue['amount'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($revenue['revenue_date']); ?></td>
                        <td><?php echo htmlspecialchars($revenue['created_at']); ?></td>
                        <td>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/edit.php?id=<?php echo $revenue['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/delete.php?id=<?php echo $revenue['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pendapatan ini?')">Hapus</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&description=<?php echo urlencode($filter_description); ?>&revenue_date=<?php echo urlencode($filter_revenue_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>