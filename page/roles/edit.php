<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['update_all', 'update_roles'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID peran dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/roles/list.php');
    exit();
}
$role_id = $_GET['id'];

// Ambil data peran pengguna
$stmt = $pdo->prepare("SELECT id, name, description FROM roles WHERE id = ?");
$stmt->execute([$role_id]);
$peran = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$peran) {
    header('Location: '.$_ENV['BASE_URL'].'/page/roles/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    try {
        $stmt = $pdo->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $role_id]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Mengedit peran pengguna: $name"]);

        header('Location: '.$_ENV['BASE_URL'].'/page/roles/list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal mengedit peran pengguna: " . $e->getMessage();
    }
}

require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit peran Pengguna</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Peran Pengguna</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($peran['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($peran['description']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
