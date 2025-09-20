<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_materials', 'read_materials', 'update_materials', 'delete_materials'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar manajemen bahan baku"]);

// Proses filter dan paginasi
$filter_name = $_GET['name'] ?? '';
$filter_unit = $_GET['unit'] ?? '';
$filter_created_date = $_GET['created_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah bahan per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, name, unit, stock, created_at 
          FROM materials 
          WHERE 1=1";
$params = [];

if ($filter_name) {
    $query .= " AND name LIKE ?";
    $params[] = '%' . $filter_name . '%';
}
if ($filter_unit) {
    $query .= " AND unit LIKE ?";
    $params[] = '%' . $filter_unit . '%';
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
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total bahan untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM materials WHERE 1=1";
$count_params = [];
if ($filter_name) {
    $count_query .= " AND name LIKE ?";
    $count_params[] = '%' . $filter_name . '%';
}
if ($filter_unit) {
    $count_query .= " AND unit LIKE ?";
    $count_params[] = '%' . $filter_unit . '%';
}
if ($filter_created_date) {
    $count_query .= " AND DATE(created_at) = ?";
    $count_params[] = $filter_created_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_materials = $stmt->fetchColumn();
$total_pages = ceil($total_materials / $limit);
require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Manajemen Bahan Baku</h1>
    <a href="<?php echo $_ENV['BASE_URL']; ?>/page/materials/add.php" class="btn btn-success mb-3">Tambah Bahan Baku</a>
    
    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="name" class="form-label">Nama Bahan</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            <div class="col-md-4">
                <label for="unit" class="form-label">Unit</label>
                <input type="text" class="form-control" id="unit" name="unit" value="<?php echo htmlspecialchars($filter_unit); ?>">
            </div>
            <div class="col-md-4">
                <label for="created_date" class="form-label">Tanggal Dibuat</label>
                <input type="date" class="form-control" id="created_date" name="created_date" value="<?php echo htmlspecialchars($filter_created_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/materials/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Bahan Baku -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Bahan</th>
                <th>Unit</th>
                <th>Stok</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($materials)): ?>
                <tr><td colspan="6" class="text-center">Tidak ada data bahan baku.</td></tr>
            <?php else: ?>
                <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($material['id']); ?></td>
                        <td><?php echo htmlspecialchars($material['name']); ?></td>
                        <td><?php echo htmlspecialchars($material['unit']); ?></td>
                        <td><?php echo number_format($material['stock'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($material['created_at']); ?></td>
                        <td>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/materials/edit.php?id=<?php echo $material['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/materials/delete.php?id=<?php echo $material['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus bahan baku ini?')">Hapus</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&name=<?php echo urlencode($filter_name); ?>&unit=<?php echo urlencode($filter_unit); ?>&created_date=<?php echo urlencode($filter_created_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
