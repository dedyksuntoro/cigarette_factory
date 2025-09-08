<?php
session_start();
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../templates/header.php';

// Cek apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar manajemen pengguna"]);

// Proses filter dan paginasi
$filter_username = $_GET['username'] ?? '';
$filter_email = $_GET['email'] ?? '';
$filter_role = $_GET['role'] ?? '';
$filter_created_date = $_GET['created_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah pengguna per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, username, email, role, created_at 
          FROM users 
          WHERE 1=1";
$params = [];

if ($filter_username) {
    $query .= " AND username LIKE ?";
    $params[] = '%' . $filter_username . '%';
}
if ($filter_email) {
    $query .= " AND email LIKE ?";
    $params[] = '%' . $filter_email . '%';
}
if ($filter_role) {
    $query .= " AND role = ?";
    $params[] = $filter_role;
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
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pengguna untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$count_params = [];
if ($filter_username) {
    $count_query .= " AND username LIKE ?";
    $count_params[] = '%' . $filter_username . '%';
}
if ($filter_email) {
    $count_query .= " AND email LIKE ?";
    $count_params[] = '%' . $filter_email . '%';
}
if ($filter_role) {
    $count_query .= " AND role = ?";
    $count_params[] = $filter_role;
}
if ($filter_created_date) {
    $count_query .= " AND DATE(created_at) = ?";
    $count_params[] = $filter_created_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);
?>

<div class="container mt-4">
    <h1>Manajemen Pengguna</h1>
    <a href="add.php" class="btn btn-success mb-3">Tambah Pengguna</a>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($filter_username); ?>">
            </div>
            <div class="col-md-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($filter_email); ?>">
            </div>
            <div class="col-md-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="supervisor" <?php echo $filter_role == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                    <option value="operator" <?php echo $filter_role == 'operator' ? 'selected' : ''; ?>>Operator</option>
                    <option value="gudang" <?php echo $filter_role == 'gudang' ? 'selected' : ''; ?>>Gudang</option>
                    <option value="keuangan" <?php echo $filter_role == 'keuangan' ? 'selected' : ''; ?>>Keuangan</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="created_date" class="form-label">Tanggal Dibuat</label>
                <input type="date" class="form-control" id="created_date" name="created_date" value="<?php echo htmlspecialchars($filter_created_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/users/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Pengguna -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center">Tidak ada data pengguna.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="<?php echo $_ENV['BASE_URL']; ?>/page/users/delete.php?id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pengguna ini?')">Hapus</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&username=<?php echo urlencode($filter_username); ?>&email=<?php echo urlencode($filter_email); ?>&role=<?php echo urlencode($filter_role); ?>&created_date=<?php echo urlencode($filter_created_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
