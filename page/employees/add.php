<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['create_all', 'create_employees'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Proses form sebelum output
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'] ?: null; // Bisa null
    $name = $_POST['name'];
    $position = $_POST['position'];
    $shift = $_POST['shift'];
    $salary = $_POST['salary'];

    try {
        $stmt = $pdo->prepare("INSERT INTO employees (user_id, name, position, shift, salary, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $name, $position, $shift, $salary]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Menambahkan karyawan baru: $name"]);

        header('Location: '.$_ENV['BASE_URL'].'/page/employees/list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menambah karyawan: " . $e->getMessage();
    }
}

// Ambil daftar pengguna untuk dropdown user_id
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header setelah logika redirect
require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Tambah Karyawan</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="user_id" class="form-label">Pengguna (Opsional)</label>
            <select class="form-select" id="user_id" name="user_id">
                <option value="">Tidak terkait pengguna</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Nama</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="position" class="form-label">Posisi</label>
            <input type="text" class="form-control" id="position" name="position" required>
        </div>
        <div class="mb-3">
            <label for="shift" class="form-label">Shift</label>
            <select class="form-select" id="shift" name="shift" required>
                <option value="pagi">Pagi</option>
                <option value="siang">Siang</option>
                <option value="malam">Malam</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="salary" class="form-label">Gaji</label>
            <input type="number" step="0.01" class="form-control" id="salary" name="salary" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/employees/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>