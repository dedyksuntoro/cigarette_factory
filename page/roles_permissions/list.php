<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($_SESSION['role'], ['create_all', 'read_all', 'update_all', 'delete_all', 'create_roles_permissions', 'read_roles_permissions', 'update_roles_permissions', 'delete_roles_permissions'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar peran izin"]);

// Proses filter dan paginasi
$filter_name = $_GET['name'] ?? '';
$filter_created_date = $_GET['created_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah permissions per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, name, description, created_at 
          FROM permissions 
          WHERE 1=1";
$params = [];

if ($filter_name) {
    $query .= " AND name LIKE ?";
    $params[] = '%' . $filter_name . '%';
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
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total permissions untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM permissions WHERE 1=1";
$count_params = [];
if ($filter_name) {
    $count_query .= " AND name LIKE ?";
    $count_params[] = '%' . $filter_name . '%';
}
if ($filter_created_date) {
    $count_query .= " AND DATE(created_at) = ?";
    $count_params[] = $filter_created_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_permissions = $stmt->fetchColumn();
$total_pages = ceil($total_permissions / $limit);

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Manajemen Permissions</h1>
    <a href="add.php" class="btn btn-success mb-3">Tambah Permission</a>
    
    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="name" class="form-label">Nama Permission</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            <div class="col-md-3">
                <label for="created_date" class="form-label">Tanggal Dibuat</label>
                <input type="date" class="form-control" id="created_date" name="created_date" value="<?php echo htmlspecialchars($filter_created_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles_permissions/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Permissions -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Permission</th>
                <th>Deskripsi</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($permissions)): ?>
                <tr><td colspan="5" class="text-center">Tidak ada data permissions.</td></tr>
            <?php else: ?>
                <?php foreach ($permissions as $perm): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($perm['id']); ?></td>
                        <td><?php echo htmlspecialchars($perm['name']); ?></td>
                        <td><?php echo htmlspecialchars($perm['description']); ?></td>
                        <td><?php echo htmlspecialchars($perm['created_at']); ?></td>
                        <td>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles_permissions/edit.php?id=<?php echo $perm['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles_permissions/delete.php?id=<?php echo $perm['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus permission ini?')">Hapus</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&name=<?php echo urlencode($filter_name); ?>&created_date=<?php echo urlencode($filter_created_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>