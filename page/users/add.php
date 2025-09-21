<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($_SESSION['role'], ['create_all', 'create_users'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil semua roles
$stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY name");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $selected_roles = $_POST['roles'] ?? [];

    try {
        // Mulai transaksi
        $pdo->beginTransaction();

        // Tambahkan pengguna baru
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$username, $password, $email]);

        // Ambil ID pengguna yang baru dibuat
        $user_id = $pdo->lastInsertId();

        // Tambahkan roles yang dipilih
        foreach ($selected_roles as $role_id) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $role_id]);
        }

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Menambahkan pengguna baru: $username dengan roles"]);

        // Commit transaksi
        $pdo->commit();

        header('Location: ' . $_ENV['BASE_URL'] . '/page/users/list.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Gagal menambah pengguna: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Tambah Pengguna</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Roles</label>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <?php foreach ($roles as $role): ?>
                                <th><?php echo htmlspecialchars($role['name']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($roles as $role): ?>
                                <td class="text-center">
                                    <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo $_ENV['BASE_URL']; ?>/page/users/list.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>