<h4 class="text-center">Cigarette Factory</h4>
<hr class="bg-light">
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
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/list.php">Pergerakan Stok</a>
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
