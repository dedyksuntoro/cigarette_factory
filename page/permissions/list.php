<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_permissions', 'read_permissions', 'update_permissions', 'delete_permissions'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar permissions"]);

// Proses filter dan paginasi
$filter_izin = $_GET['izin'] ?? '';
$filter_created_date = $_GET['created_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah permissions per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, name, description, created_at 
          FROM permissions 
          WHERE 1=1";
$params = [];

if ($filter_izin) {
    $query .= " AND name LIKE ?";
    $params[] = '%' . $filter_izin . '%';
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
if ($filter_izin) {
    $count_query .= " AND name LIKE ?";
    $count_params[] = '%' . $filter_izin . '%';
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
    <?php if (hasPermission($role, ['create_all', 'create_permissions'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/permissions/add.php" class="btn btn-success mb-3">Tambah Permission</a>
    <?php endif; ?>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="izin" class="form-label">Permission</label>
                <input type="text" class="form-control" id="izin" name="izin" value="<?php echo htmlspecialchars($filter_izin); ?>">
            </div>
            <div class="col-md-3">
                <label for="created_date" class="form-label">Tanggal Dibuat</label>
                <input type="date" class="form-control" id="created_date" name="created_date" value="<?php echo htmlspecialchars($filter_created_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/permissions/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Permissions -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Permission</th>
                <th>Deskripsi</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($permissions)): ?>
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data permissions.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($permissions as $permission): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($permission['id']); ?></td>
                        <td><?php echo htmlspecialchars($permission['name']); ?></td>
                        <td><?php echo htmlspecialchars($permission['description']); ?></td>
                        <td><?php echo htmlspecialchars($permission['created_at']); ?></td>
                        <td>
                            <?php if (hasPermission($role, ['update_all', 'update_permissions'])): ?>
                                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/permissions/edit.php?id=<?php echo $permission['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <?php endif; ?>
                            <?php if (hasPermission($role, ['delete_all', 'delete_permissions'])): ?>
                                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/permissions/delete.php?id=<?php echo $permission['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus permissions ini?')">Hapus</a>
                            <?php endif; ?>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&izin=<?php echo urlencode($filter_izin); ?>&created_date=<?php echo urlencode($filter_created_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>