<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !hasPermission($role, ['update_all', 'update_users'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil ID pengguna dari URL
if (!isset($_GET['id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/users/list.php');
    exit();
}
$user_id = $_GET['id'];

// Ambil data pengguna
$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/users/list.php');
    exit();
}

// Ambil semua roles
$stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY name");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil roles yang sudah terkait dengan pengguna
$stmt = $pdo->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_roles = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];
    $selected_roles = $_POST['roles'] ?? [];

    try {
        // Mulai transaksi
        $pdo->beginTransaction();

        // Update data pengguna
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $email, $password, $user_id]);

        // Hapus semua roles untuk pengguna ini
        $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Tambahkan roles yang dipilih
        foreach ($selected_roles as $role_id) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $role_id]);
        }

        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, log_time) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Mengedit pengguna: $username dengan roles"]);

        // Commit transaksi
        $pdo->commit();

        header('Location: ' . $_ENV['BASE_URL'] . '/page/users/list.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Gagal mengedit pengguna: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <h1>Edit Pengguna: <?php echo htmlspecialchars($user['username']); ?></h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password (kosongkan jika tidak ingin mengubah)</label>
            <input type="password" class="form-control" id="password" name="password">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
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
                                    <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" 
                                           <?php echo in_array($role['id'], $user_roles) ? 'checked' : ''; ?>>
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