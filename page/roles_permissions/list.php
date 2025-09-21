<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array('create_users', $_SESSION['role'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/login.php');
    exit();
}

// Ambil daftar permissions
$stmt = $pdo->query("SELECT * FROM permissions");
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil permissions untuk role tertentu (misal role_id = 1)
$role_id = 1; // Ganti dengan role_id yang ingin diedit
$stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$stmt->execute([$role_id]);
$role_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_permissions = $_POST['permissions'] ?? [];
    // Hapus semua permissions untuk role ini
    $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    // Tambahkan permissions yang dipilih
    foreach ($selected_permissions as $perm_id) {
        $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmt->execute([$role_id, $perm_id]);
    }
    echo "<div class='alert alert-success'>Permissions diperbarui!</div>";
}
?>
<form method="POST">
    <?php foreach ($permissions as $perm): ?>
        <div>
            <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>" 
                   <?php echo in_array($perm['id'], $role_permissions) ? 'checked' : ''; ?>>
            <?php echo htmlspecialchars($perm['name']); ?>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary">Simpan</button>
</form>