<h4 class="text-center">Cigarette Factory</h4>
<hr class="bg-light">
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/index.php">Dashboard</a>
    </li>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_production_plans', 'read_production_plans', 'update_production_plans', 'delete_production_plans'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/production_plans/list.php">Perencanaan Produksi</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_production_results', 'read_production_results', 'update_production_results', 'delete_production_results'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/production_results/list.php">Hasil Produksi</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_materials', 'read_materials', 'update_materials', 'delete_materials'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/materials/list.php">Manajemen Bahan Baku</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_finished_goods', 'read_finished_goods', 'update_finished_goods', 'delete_finished_goods'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/finished_goods/list.php">Manajemen Barang Jadi</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_quality_controls', 'read_quality_controls', 'update_quality_controls', 'delete_quality_controls'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/quality_controls/list.php">Kontrol Kualitas</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_stock_movements', 'read_stock_movements', 'update_stock_movements', 'delete_stock_movements'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/stock_movements/list.php">Pergerakan Stok</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_distributions', 'read_distributions', 'update_distributions', 'delete_distributions'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/distributions/list.php">Distribusi</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_invoices', 'read_invoices', 'update_invoices', 'delete_invoices'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/invoices/list.php">Faktur</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_expenses', 'read_expenses', 'update_expenses', 'delete_expenses'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/expenses/list.php">Pengeluaran</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_revenues', 'read_revenues', 'update_revenues', 'delete_revenues'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/revenues/list.php">Pendapatan</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_users', 'read_users', 'update_users', 'delete_users'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/users/list.php">Manajemen Pengguna</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_employees', 'read_employees', 'update_employees', 'delete_employees'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/employees/list.php">Manajemen Karyawan</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_employees', 'read_employees', 'update_employees', 'delete_employees'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/permissions/list.php">Manajemen Izin Pengguna</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_roles', 'read_roles', 'update_roles', 'delete_roles'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/roles/list.php">Manajemen Peran Pengguna</a>
        </li>
    <?php } ?>
    <?php if (hasPermission($role, ['create_all', 'read_all', 'update_all', 'delete_all', 'create_logs', 'read_logs', 'update_logs', 'delete_logs'])) { ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/logs/list.php">Log Aktivitas</a>
        </li>
    <?php } ?>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $_ENV['BASE_URL']; ?>/page/auth/logout.php">Logout</a>
    </li>
</ul>
