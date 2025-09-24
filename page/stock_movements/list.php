<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_stock_movements', 'read_stock_movements', 'update_stock_movements', 'delete_stock_movements'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Catat log aktivitas akses halaman
$stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], "Mengakses daftar pergerakan stok"]);

// Proses filter dan paginasi
$filter_type = $_GET['type'] ?? '';
$filter_material = $_GET['material_id'] ?? '';
$filter_finished_good = $_GET['finished_good_id'] ?? '';
$filter_date = $_GET['movement_date'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah pergerakan stok per halaman
$offset = ($page - 1) * $limit;

// Bangun query dengan filter
$query = "SELECT sm.id, sm.type, sm.quantity, sm.movement_date, m.name AS material_name, fg.product_name AS finished_good_name 
          FROM stock_movements sm 
          LEFT JOIN materials m ON sm.material_id = m.id 
          LEFT JOIN finished_goods fg ON sm.finished_good_id = fg.id 
          WHERE 1=1";
$params = [];

if ($filter_type) {
    $query .= " AND sm.type = ?";
    $params[] = $filter_type;
}
if ($filter_material) {
    $query .= " AND sm.material_id = ?";
    $params[] = $filter_material;
}
if ($filter_finished_good) {
    $query .= " AND sm.finished_good_id = ?";
    $params[] = $filter_finished_good;
}
if ($filter_date) {
    $query .= " AND DATE(sm.movement_date) = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY sm.movement_date DESC LIMIT ? OFFSET ?";

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
$stock_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pergerakan stok untuk paginasi
$count_query = "SELECT COUNT(*) as total FROM stock_movements sm 
                LEFT JOIN materials m ON sm.material_id = m.id 
                LEFT JOIN finished_goods fg ON sm.finished_good_id = fg.id 
                WHERE 1=1";
$count_params = [];
if ($filter_type) {
    $count_query .= " AND sm.type = ?";
    $count_params[] = $filter_type;
}
if ($filter_material) {
    $count_query .= " AND sm.material_id = ?";
    $count_params[] = $filter_material;
}
if ($filter_finished_good) {
    $count_query .= " AND sm.finished_good_id = ?";
    $count_params[] = $filter_finished_good;
}
if ($filter_date) {
    $count_query .= " AND DATE(sm.movement_date) = ?";
    $count_params[] = $filter_date;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_movements = $stmt->fetchColumn();
$total_pages = ceil($total_movements / $limit);

// Ambil data untuk dropdown filter
$materials = $pdo->query("SELECT id, name FROM materials")->fetchAll(PDO::FETCH_ASSOC);
$finished_goods = $pdo->query("SELECT id, product_name FROM finished_goods")->fetchAll(PDO::FETCH_ASSOC);

// Sertakan header setelah logika selesai
require_once __DIR__ . '/../templates/header.php';

// URL parameter untuk mempertahankan filter
$base_url = "?type=" . urlencode($filter_type) . "&material_id=" . urlencode($filter_material) . "&finished_good_id=" . urlencode($filter_finished_good) . "&movement_date=" . urlencode($filter_date) . "&page=";

// Hitung rentang halaman untuk ditampilkan
$max_visible_pages = 5;
$half_visible = floor($max_visible_pages / 2);
$start_page = max(1, $page - $half_visible);
$end_page = min($total_pages, $start_page + $max_visible_pages - 1);
$start_page = max(1, min($start_page, $total_pages - $max_visible_pages + 1));
?>

<div class="container mt-4">
    <h1>Pergerakan Stok</h1>
    <?php if (hasPermission($role, ['create_all', 'create_stock_movements'])): ?>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/add.php" class="btn btn-success mb-3">Tambah Pergerakan Stok</a>
    <?php endif; ?>
    <a class="btn btn-primary mb-3" data-bs-toggle="collapse" href="#collapsePencarian" role="button" aria-expanded="false" aria-controls="collapsePencarian">
        Filter Data
    </a>

    <!-- Form Filter -->
    <div class="collapse pb-3" id="collapsePencarian">
        <div class="card card-body shadow">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label for="type" class="form-label">Tipe</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Semua Tipe</option>
                            <option value="in" <?php echo $filter_type == 'in' ? 'selected' : ''; ?>>Masuk</option>
                            <option value="out" <?php echo $filter_type == 'out' ? 'selected' : ''; ?>>Keluar</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label for="material_id" class="form-label">Bahan Baku</label>
                        <select class="form-select" id="material_id" name="material_id">
                            <option value="">Semua Bahan Baku</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?php echo $material['id']; ?>" <?php echo $filter_material == $material['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($material['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label for="finished_good_id" class="form-label">Barang Jadi</label>
                        <select class="form-select" id="finished_good_id" name="finished_good_id">
                            <option value="">Semua Barang Jadi</option>
                            <?php foreach ($finished_goods as $fg): ?>
                                <option value="<?php echo $fg['id']; ?>" <?php echo $filter_finished_good == $fg['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($fg['product_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label for="movement_date" class="form-label">Tanggal Pergerakan</label>
                        <input type="date" class="form-control" id="movement_date" name="movement_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <hr />
                    <div class="col-md-4 col-sm-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/list.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Pergerakan Stok -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tipe</th>
                    <th>Bahan Baku</th>
                    <th>Barang Jadi</th>
                    <th>Jumlah</th>
                    <th>Tanggal Pergerakan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stock_movements)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data pergerakan stok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stock_movements as $index => $movement): ?>
                        <tr>
                            <td><?php echo ($offset + $index + 1); ?></td>
                            <td><?php echo htmlspecialchars($movement['type'] == 'in' ? 'Masuk' : 'Keluar'); ?></td>
                            <td><?php echo htmlspecialchars($movement['material_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($movement['finished_good_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($movement['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($movement['movement_date']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (hasPermission($role, ['update_all', 'update_stock_movements'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/edit.php?id=<?php echo $movement['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (hasPermission($role, ['delete_all', 'delete_stock_movements'])): ?>
                                        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/delete.php?id=<?php echo $movement['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pergerakan stok ini?')">Hapus</a>
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
                Menampilkan <?php echo count($stock_movements); ?> dari <?php echo $total_movements; ?> data
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