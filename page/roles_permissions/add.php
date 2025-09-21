<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($_SESSION['role'], ['create_all', 'create_roles'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil semua permissions
$stmt = $pdo->query("SELECT id, name, description FROM permissions ORDER BY name");
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $selected_permissions = $_POST['permissions'] ?? [];

    try {
        // Mulai transaksi
        $pdo->beginTransaction();

        // Tambahkan role baru
        $stmt = $pdo->prepare("INSERT INTO roles (name, description, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $description]);

        // Ambil ID role yang baru dibuat
        $role_id = $pdo->lastInsertId();

        // Tambahkan permissions yang dipilih
        foreach ($selected_permissions as $perm_id) {
            $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            $stmt->execute([$role_id, $perm_id]);
        }

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Menambahkan role baru: $name dengan permissions"]);

        // Commit transaksi
        $pdo->commit();

        header('Location: ' . $_ENV['BASE_URL'] . '/page/roles_permissions/list.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Gagal menambah role: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Tambah Role</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Nama Role</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="description" name="description">
        </div>
        <div class="mb-3">
            <label class="form-label">Permissions</label>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <?php foreach ($permissions as $perm): ?>
                                <th><?php echo htmlspecialchars($perm['name']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($permissions as $perm): ?>
                                <td class="text-center">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/roles_permissions/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>