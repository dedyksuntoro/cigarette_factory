<?php
$envFile = __DIR__ . '/.env'; // Sesuaikan path jika perlu
if (file_exists($envFile)) {
    $envVariables = parse_ini_string(file_get_contents($envFile));

    // Set variabel lingkungan menggunakan $_ENV atau getenv()
    foreach ($envVariables as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value"); // Opsional, jika ingin menggunakan getenv()
    }
} else {
    // Tangani error jika file .env tidak ditemukan
    die("File .env tidak ditemukan!");
}

$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER']; // Ganti dengan username database Anda
$password = $_ENV['DB_PASSWORD']; // Ganti dengan password database Anda

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk memeriksa apakah pengguna memiliki salah satu dari permissions yang diperlukan
function hasPermission($userPermissions, $requiredPermissions) {
    return count(array_intersect($userPermissions, $requiredPermissions)) > 0;
}

// Ambil permissions dari session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : [];
