<?php
session_start();
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['update_all', 'update_permissions'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

// Ambil ID permissions dari URL
if (!isset($_GET['id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/permissions/list.php');
    exit();
}
$permission_id = $_GET['id'];

// Ambil data permissions
$stmt = $pdo->prepare("SELECT id, name, description FROM permissions WHERE id = ?");
$stmt->execute([$permission_id]);
$permission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$permission) {
    header('Location: '.$_ENV['BASE_URL'].'/page/permissions/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    try {
        $stmt = $pdo->prepare("UPDATE permissions SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $permission_id]);

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Mengedit permission: $name"]);

        header('Location: '.$_ENV['BASE_URL'].'/page/permissions/list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal mengedit permission: " . $e->getMessage();
    }
}

require_once __DIR__.'/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit izin Pengguna</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Permission</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($permission['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($permission['description']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/permissions/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>