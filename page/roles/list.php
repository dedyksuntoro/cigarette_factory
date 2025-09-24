<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_roles', 'read_roles', 'update_roles', 'delete_roles'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar roles"]);

// Proses filter dan paginasi
$filter_role = $_GET['role'] ?? '';
$filter_created_date = $_GET['created_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah roles per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, name, description, created_at 
          FROM roles 
          WHERE 1=1";
$params = [];

if ($filter_role) {
    $query .= " AND name LIKE ?";
    $params[] = '%' . $filter_role . '%';
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
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total roles untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM roles WHERE 1=1";
$count_params = [];
if ($filter_role) {
    $count_query .= " AND name LIKE ?";
    $count_params[] = '%' . $filter_role . '%';
}
if ($filter_created_date) {
    $count_query .= " AND DATE(created_at) = ?";
    $count_params[] = $filter_created_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_roles = $stmt->fetchColumn();
$total_pages = ceil($total_roles / $limit);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';

// URL parameter untuk mempertahankan filter
$base_url = "?role=" . urlencode($filter_role) . "&created_date=" . urlencode($filter_created_date) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Roles</h1>
    <?php if (hasPermission($role, ['create_all', 'create_roles'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles/add.php" class="btn btn-success mb-3">Tambah Role</a>
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
                        <label for="role" class="form-label">Nama Role</label>
                        <input type="text" class="form-control" id="role" name="role" value="<?php echo htmlspecialchars($filter_role); ?>">
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label for="created_date" class="form-label">Tanggal Dibuat</label>
                        <input type="date" class="form-control" id="created_date" name="created_date" value="<?php echo htmlspecialchars($filter_created_date); ?>">
                    </div>
                    <hr />
                    <div class="col-md-4 col-sm-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles/list.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Roles -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Role</th>
                    <th>Deskripsi</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($roles)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data roles.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($roles as $index => $role): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($role['name']); ?></td>
                            <td><?php echo htmlspecialchars($role['description']); ?></td>
                            <td><?php echo htmlspecialchars($role['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission($role, ['update_all', 'update_roles'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles/edit.php?id=<?php echo $role['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (hasPermission($role, ['delete_all', 'delete_roles'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles/delete.php?id=<?php echo $role['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus role ini?')">Hapus</a>
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
                Menampilkan <?php echo count($roles); ?> dari <?php echo $total_roles; ?> data
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