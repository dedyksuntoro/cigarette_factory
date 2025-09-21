<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($_SESSION['role'], ['update_all', 'update_roles'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID peran dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/roles/list.php');
    exit();
}
$role_id = $_GET['id'];

// Ambil data peran
$stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
$stmt->execute([$role_id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/roles/list.php');
    exit();
}

// Ambil semua permissions
$stmt = $pdo->query("SELECT id, name, description FROM permissions ORDER BY name");
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil permissions yang sudah terkait dengan role
$stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$stmt->execute([$role_id]);
$role_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_permissions = $_POST['permissions'] ?? [];

    try {
        // Mulai transaksi
        $pdo->beginTransaction();

        // Hapus semua permissions untuk role ini
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$role_id]);

        // Tambahkan permissions yang dipilih
        foreach ($selected_permissions as $perm_id) {
            $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            $stmt->execute([$role_id, $perm_id]);
        }

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Memperbarui permissions untuk peran: " . $role['name']]);

        // Commit transaksi
        $pdo->commit();

        header('Location: ' . $_ENV['BASE_URL'] . '/page/roles/list.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Gagal memperbarui permissions: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Kelola Permissions untuk Peran: <?php echo htmlspecialchars($role['name']); ?></h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Pilih Permissions</label>
            <div class="row">
                <?php foreach ($permissions as $perm): ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="permissions[]" 
                                   value="<?php echo $perm['id']; ?>" 
                                   id="perm_<?php echo $perm['id']; ?>" 
                                   <?php echo in_array($perm['id'], $role_permissions) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="perm_<?php echo $perm['id']; ?>">
                                <?php echo htmlspecialchars($perm['name']); ?> 
                                <?php if ($perm['description']) { ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($perm['description']); ?>)</small>
                                <?php } ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
