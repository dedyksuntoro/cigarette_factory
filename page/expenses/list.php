<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_expenses', 'read_expenses', 'update_expenses', 'delete_expenses'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar pengeluaran"]);

// Proses filter dan paginasi
$filter_description = $_GET['description'] ?? '';
$filter_expense_date = $_GET['expense_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah pengeluaran per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT id, description, amount, expense_date, created_at 
          FROM expenses 
          WHERE 1=1";
$params = [];

if ($filter_description) {
    $query .= " AND description LIKE ?";
    $params[] = "%$filter_description%";
}
if ($filter_expense_date) {
    $query .= " AND expense_date = ?";
    $params[] = $filter_expense_date;
}

$query .= " ORDER BY expense_date DESC LIMIT ? OFFSET ?";

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
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pengeluaran untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM expenses WHERE 1=1";
$count_params = [];
if ($filter_description) {
    $count_query .= " AND description LIKE ?";
    $count_params[] = "%$filter_description%";
}
if ($filter_expense_date) {
    $count_query .= " AND expense_date = ?";
    $count_params[] = $filter_expense_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_expenses = $stmt->fetchColumn();
$total_pages = ceil($total_expenses / $limit);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Manajemen Pengeluaran</h1>
    <?php if (hasPermission($role, ['create_all', 'create_expanses'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/expenses/add.php" class="btn btn-success mb-3">Tambah Pengeluaran</a>
    <?php endif; ?>

    <!-- Form Filter -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="description" class="form-label">Deskripsi</label>
                <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($filter_description); ?>">
            </div>
            <div class="col-md-4">
                <label for="expense_date" class="form-label">Tanggal Pengeluaran</label>
                <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo htmlspecialchars($filter_expense_date); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filter</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/expenses/list.php" class="btn btn-secondary mt-3">Reset</a>
    </form>

    <!-- Tabel Pengeluaran -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Deskripsi</th>
                <th>Jumlah</th>
                <th>Tanggal Pengeluaran</th>
                <th>Dibuat Pada</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($expenses)): ?>
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data pengeluaran.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($expenses as $index => $expense): ?>
                    <tr>
                        <td><?php echo ($offset + $index + 1); ?></td>
                        <td><?php echo htmlspecialchars($expense['description']); ?></td>
                        <td><?php echo number_format($expense['amount'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($expense['expense_date']); ?></td>
                        <td><?php echo htmlspecialchars($expense['created_at']); ?></td>
                        <td>
                            <?php if (hasPermission($role, ['update_all', 'update_expanses'])): ?>
                                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/expenses/edit.php?id=<?php echo $expense['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <?php endif; ?>
                            <?php if (hasPermission($role, ['delete_all', 'delete_expanses'])): ?>
                                <a href="<?php echo $_ENV['BASE_URL']; ?>/page/expenses/delete.php?id=<?php echo $expense['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pengeluaran ini?')">Hapus</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&description=<?php echo urlencode($filter_description); ?>&expense_date=<?php echo urlencode($filter_expense_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>