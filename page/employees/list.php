<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_employees', 'read_employees', 'update_employees', 'delete_employees'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar manajemen karyawan"]);

// Proses filter dan paginasi
$filter_name = $_GET['name'] ?? '';
$filter_position = $_GET['position'] ?? '';
$filter_shift = $_GET['shift'] ?? '';
$filter_created_date = $_GET['created_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah karyawan per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT e.id, e.user_id, u.username, e.name, e.position, e.shift, e.salary, e.created_at 
          FROM employees e 
          LEFT JOIN users u ON e.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($filter_name) {
    $query .= " AND e.name LIKE ?";
    $params[] = '%' . $filter_name . '%';
}
if ($filter_position) {
    $query .= " AND e.position LIKE ?";
    $params[] = '%' . $filter_position . '%';
}
if ($filter_shift) {
    $query .= " AND e.shift = ?";
    $params[] = $filter_shift;
}
if ($filter_created_date) {
    $query .= " AND DATE(e.created_at) = ?";
    $params[] = $filter_created_date;
}

$query .= " ORDER BY e.created_at DESC LIMIT ? OFFSET ?";

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
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total karyawan untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM employees WHERE 1=1";
$count_params = [];
if ($filter_name) {
    $count_query .= " AND name LIKE ?";
    $count_params[] = '%' . $filter_name . '%';
}
if ($filter_position) {
    $count_query .= " AND position LIKE ?";
    $count_params[] = '%' . $filter_position . '%';
}
if ($filter_shift) {
    $count_query .= " AND shift = ?";
    $count_params[] = $filter_shift;
}
if ($filter_created_date) {
    $count_query .= " AND DATE(created_at) = ?";
    $count_params[] = $filter_created_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_employees = $stmt->fetchColumn();
$total_pages = ceil($total_employees / $limit);
require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Manajemen Karyawan</h1>
    <a href="<?php echo $_ENV['BASE_URL']; ?>/page/employees/add.php" class="btn btn-success mb-3">Tambah Karyawan</a>
    
    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="name" class="form-label">Nama</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($filter_name); ?>">
            </div>
            <div class="col-md-3">
                <label for="position" class="form-label">Posisi</label>
                <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($filter_position); ?>">
            </div>
            <div class="col-md-3">
                <label for="shift" class="form-label">Shift</label>
                <select class="form-select" id="shift" name="shift">
                    <option value="">Semua Shift</option>
                    <option value="pagi" <?php echo $filter_shift == 'pagi' ? 'selected' : ''; ?>>Pagi</option>
                    <option value="siang" <?php echo $filter_shift == 'siang' ? 'selected' : ''; ?>>Siang</option>
                    <option value="malam" <?php echo $filter_shift == 'malam' ? 'selected' : ''; ?>>Malam</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="created_date" class="form-label">Tanggal Dibuat</label>
                <input type="date" class="form-control" id="created_date" name="created_date" value="<?php echo htmlspecialchars($filter_created_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/employees/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Karyawan -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Nama</th>
                <th>Posisi</th>
                <th>Shift</th>
                <th>Gaji</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($employees)): ?>
                <tr><td colspan="8" class="text-center">Tidak ada data karyawan.</td></tr>
            <?php else: ?>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['id']); ?></td>
                        <td><?php echo htmlspecialchars($employee['username'] ?? 'Tidak terkait'); ?></td>
                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                        <td><?php echo htmlspecialchars($employee['shift']); ?></td>
                        <td><?php echo number_format($employee['salary'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($employee['created_at']); ?></td>
                        <td>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/employees/edit.php?id=<?php echo $employee['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/employees/delete.php?id=<?php echo $employee['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus karyawan ini?')">Hapus</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&name=<?php echo urlencode($filter_name); ?>&position=<?php echo urlencode($filter_position); ?>&shift=<?php echo urlencode($filter_shift); ?>&created_date=<?php echo urlencode($filter_created_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>