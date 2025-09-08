<?php
// session_start();
require_once __DIR__.'/../../config/db.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: '.$_ENV['BASE_URL'].'/page/auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cigarette Factory</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background-color: #343a40;
            color: white;
            height: 100vh;
            padding: 15px;
        }
        .sidebar .nav-link {
            color: white;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
            border-radius: 5px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- Navbar (mobile & desktop toggle) -->
<nav class="navbar navbar-dark bg-dark d-md-none">
    <div class="container-fluid">
        <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
            ☰ Menu
        </button>
        <span class="navbar-text text-white">Cigarette Factory</span>
    </div>
</nav>

<!-- Sidebar Offcanvas for Mobile -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <?php include 'sidebar.php'; ?>
    </div>
</div>

<!-- Main Layout -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar for Desktop -->
        <nav class="col-md-3 col-lg-2 d-none d-md-block sidebar">
            <?php include 'sidebar.php'; ?>
        </nav>

        <!-- Content Area -->
        <main class="col-md-9 col-lg-10 px-4 py-3">
            <div class="header">
                <span>Selamat datang, <?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</span>
            </div>
