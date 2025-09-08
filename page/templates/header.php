<?php
// session_start();
require_once __DIR__ . '/../../config/db.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Cigarette Factory</title>
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: row;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 15px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: white;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
            border-radius: 5px;
        }
        .content {
            flex: 1;
            margin-left: 250px; /* Sesuaikan dengan lebar sidebar */
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        /* Responsif untuk mobile */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
        }
        /* Pastikan navbar tetap rapi di desktop */
        @media (min-width: 769px) {
            .navbar {
                display: none; /* Sembunyikan navbar di desktop */
            }
        }
    </style>
</head>
<body>
    <!-- Navbar untuk toggle sidebar di mobile -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $_ENV['BASE_URL']; ?>/index.php">Cigarette Factory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="sidebarMenu">
                <div class="sidebar">
                    <h4 class="text-center d-none d-lg-block">Cigarette Factory</h4>
                    <hr class="bg-light d-none d-lg-block">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/index.php">Dashboard</a>
                        </li>
                        <?php if ($role == 'admin') { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/users/list.php">Manajemen Pengguna</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/logs/list.php">Log Aktivitas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/employees/list.php">Manajemen Karyawan</a>
                            </li>
                        <?php } ?>
                        <?php if (in_array($role, ['admin', 'supervisor'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/list.php">Perencanaan Produksi</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/list.php">Hasil Produksi</a>
                            </li>
                        <?php } ?>
                        <?php if (in_array($role, ['admin', 'gudang'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/materials/list.php">Manajemen Bahan Baku</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/list.php">Manajemen Barang Jadi</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Pergerakan Stok</a>
                            </li>
                        <?php } ?>
                        <?php if (in_array($role, ['admin', 'keuangan'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Faktur</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Pengeluaran</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Pendapatan</a>
                            </li>
                        <?php } ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/auth/logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="content">
        <div class="header">
            <span>Selamat datang, <?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</span>
        </div>
    </div>

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk menangani toggle sidebar di mobile
        document.addEventListener('DOMContentLoaded', function () {
            const toggler = document.querySelector('.navbar-toggler');
            const sidebar = document.querySelector('.sidebar');
            
            toggler.addEventListener('click', function () {
                sidebar.classList.toggle('show');
            });

            // Tutup sidebar saat link di dalamnya diklik (mobile only)
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('show');
                        document.querySelector('.navbar-toggler').setAttribute('aria-expanded', 'false');
                        document.querySelector('#sidebarMenu').classList.remove('show');
                    }
                });
            });
        });
    </script>
</body>
</html>
