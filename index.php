<?php
session_start();
require_once 'page/templates/header.php';
require_once 'config/db.php';

// Debugging: Cek apakah sesi sudah diatur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: ' . $_ENV['BASE_URL'] . '/page/auth/login.php');
    exit();
}

// Ambil peran pengguna dari sesi
$user_roles = isset($_SESSION['role']) ? $_SESSION['role'] : [];

// Fungsi untuk mengambil data ringkasan menggunakan PDO
function getSummaryData($pdo, $table, $condition = '')
{
    try {
        $query = "SELECT COUNT(*) as total FROM $table $condition";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        error_log("Error in getSummaryData: " . $e->getMessage());
        return 0;
    }
}

// Fungsi untuk mengambil data bulanan
function getMonthlyData($pdo, $table, $column, $condition = '')
{
    try {
        $query = "SELECT MONTH($column) as month, COUNT(*) as total 
                  FROM $table 
                  WHERE YEAR($column) = YEAR(CURDATE()) $condition 
                  GROUP BY MONTH($column)";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthly_data = array_fill(1, 12, 0);
        foreach ($results as $row) {
            $monthly_data[$row['month']] = $row['total'];
        }
        return array_values($monthly_data);
    } catch (PDOException $e) {
        error_log("Error in getMonthlyData: " . $e->getMessage());
        return array_fill(1, 12, 0);
    }
}

// Fungsi untuk mengambil efisiensi produksi bulanan
function getMonthlyEfficiency($pdo)
{
    try {
        $query = "SELECT MONTH(created_at) as month, AVG(efficiency) as avg_efficiency 
                  FROM production_results 
                  WHERE YEAR(created_at) = YEAR(CURDATE()) AND efficiency IS NOT NULL 
                  GROUP BY MONTH(created_at)";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthly_data = array_fill(1, 12, 0);
        foreach ($results as $row) {
            $monthly_data[$row['month']] = round($row['avg_efficiency'], 2);
        }
        return array_values($monthly_data);
    } catch (PDOException $e) {
        error_log("Error in getMonthlyEfficiency: " . $e->getMessage());
        return array_fill(1, 12, 0);
    }
}

// Inisialisasi data untuk dashboard
$total_production_plans = 0;
$total_production_results = 0;
$total_materials = 0;
$total_finished_goods = 0;
$total_distributions = 0;
$total_invoices_unpaid = 0;
$total_employees = 0;
$total_expenses = 0;
$total_revenues = 0;
$total_quality_controls = 0;
$total_payrolls = 0;

// Inisialisasi data bulanan
$monthly_revenues = array_fill(1, 12, 0);
$monthly_expenses = array_fill(1, 12, 0);
$monthly_production_results = array_fill(1, 12, 0);
$monthly_distributions = array_fill(1, 12, 0);
$monthly_quality_controls_failed = array_fill(1, 12, 0);
$monthly_efficiency = array_fill(1, 12, 0);

// Fungsi untuk memeriksa akses ke modul tertentu
$has_production_access = hasPermission($user_roles, ['create_all', 'read_all', 'create_production_plans', 'read_production_plans', 'update_production_plans', 'delete_production_plans', 'create_production_results', 'read_production_results', 'update_production_results', 'delete_production_results']);
$has_warehouse_access = hasPermission($user_roles, ['create_all', 'read_all', 'create_materials', 'read_materials', 'update_materials', 'delete_materials', 'create_finished_goods', 'read_finished_goods', 'update_finished_goods', 'delete_finished_goods', 'create_stock_movements', 'read_stock_movements', 'update_stock_movements', 'delete_stock_movements']);
$has_distribution_access = hasPermission($user_roles, ['create_all', 'read_all', 'create_distributions', 'read_distributions', 'update_distributions', 'delete_distributions', 'create_invoices', 'read_invoices', 'update_invoices', 'delete_invoices']);
$has_finance_access = hasPermission($user_roles, ['create_all', 'read_all', 'create_expenses', 'read_expenses', 'update_expenses', 'delete_expenses', 'create_revenues', 'read_revenues', 'update_revenues', 'delete_revenues', 'create_invoices', 'read_invoices', 'update_invoices', 'delete_invoices']);
$has_qc_access = hasPermission($user_roles, ['create_all', 'read_all', 'create_quality_controls', 'read_quality_controls', 'update_quality_controls', 'delete_quality_controls']);
$has_hrd_access = hasPermission($user_roles, ['create_all', 'read_all', 'create_employees', 'read_employees', 'update_employees', 'delete_employees', 'create_payrolls', 'read_payrolls', 'update_payrolls', 'delete_payrolls']);

// Khusus untuk Owner: Pastikan akses ke semua modul
$is_owner = in_array('create_all', $user_roles) || in_array('read_all', $user_roles);
if ($is_owner) {
    $has_production_access = true;
    $has_warehouse_access = true;
    $has_distribution_access = true;
    $has_finance_access = true;
    $has_qc_access = true;
    $has_hrd_access = true;
}

// Ambil data ringkasan hanya untuk modul yang diizinkan
if ($has_production_access) {
    $total_production_plans = getSummaryData($pdo, 'production_plans');
    $total_production_results = getSummaryData($pdo, 'production_results');
    $monthly_production_results = getMonthlyData($pdo, 'production_results', 'created_at');
    $monthly_efficiency = getMonthlyEfficiency($pdo);
}
if ($has_warehouse_access) {
    $total_materials = getSummaryData($pdo, 'materials', 'WHERE stock > 0');
    $total_finished_goods = getSummaryData($pdo, 'finished_goods', 'WHERE stock > 0');
}
if ($has_distribution_access) {
    $total_distributions = getSummaryData($pdo, 'distributions', "WHERE status = 'pending'");
    $total_invoices_unpaid = getSummaryData($pdo, 'invoices', "WHERE status = 'unpaid'");
    $monthly_distributions = getMonthlyData($pdo, 'distributions', 'created_at', "WHERE status = 'delivered'");
}
if ($has_finance_access) {
    $total_expenses = getSummaryData($pdo, 'expenses', "WHERE YEAR(expense_date) = YEAR(CURDATE())");
    $total_revenues = getSummaryData($pdo, 'revenues', "WHERE YEAR(revenue_date) = YEAR(CURDATE())");
    $monthly_revenues = getMonthlyData($pdo, 'revenues', 'revenue_date');
    $monthly_expenses = getMonthlyData($pdo, 'expenses', 'expense_date');
}
if ($has_qc_access) {
    $total_quality_controls = getSummaryData($pdo, 'quality_controls', "WHERE result = 'failed'");
    $monthly_quality_controls_failed = getMonthlyData($pdo, 'quality_controls', 'qc_date', "WHERE result = 'failed'");
}
if ($has_hrd_access) {
    $total_employees = getSummaryData($pdo, 'employees');
    $total_payrolls = getSummaryData($pdo, 'payrolls', "WHERE YEAR(month) = YEAR(CURDATE())");
}
?>

<div class="container mt-4">
    <h1>Dashboard</h1>
    <p>Selamat datang di sistem manajemen Cigarette Factory. Berikut adalah ringkasan data operasional untuk peran Anda:</p>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <?php if ($has_production_access) { ?>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Rencana Produksi</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_production_plans); ?> Rencana</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Hasil Produksi</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_production_results); ?> Catatan</p>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_warehouse_access) { ?>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Bahan Baku</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_materials); ?> Item</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Barang Jadi</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_finished_goods); ?> Item</p>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_distribution_access) { ?>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Distribusi Pending</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_distributions); ?> Pengiriman</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-secondary">
                    <div class="card-body">
                        <h5 class="card-title">Faktur Belum Dibayar</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_invoices_unpaid); ?> Faktur</p>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_finance_access) { ?>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Pengeluaran Tahun Ini</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_expenses); ?> Transaksi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Pendapatan Tahun Ini</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_revenues); ?> Transaksi</p>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_qc_access) { ?>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-dark">
                    <div class="card-body">
                        <h5 class="card-title">Kontrol Kualitas Gagal</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_quality_controls); ?> Catatan</p>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_hrd_access) { ?>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Jumlah Karyawan</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_employees); ?> Orang</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Penggajian Tahun Ini</h5>
                        <p class="card-text"><?php echo htmlspecialchars($total_payrolls); ?> Catatan</p>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Grafik Statis: Status Distribusi dan Hasil Kontrol Kualitas -->
    <div class="row mb-12">
        <?php if ($has_distribution_access) { ?>
            <div class="col-lg-6 p-2">
                <div class="card">
                    <div class="card-header">Status Distribusi</div>
                    <div class="card-body">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_qc_access) { ?>
            <div class="col-lg-6 p-2">
                <div class="card">
                    <div class="card-header">Hasil Kontrol Kualitas</div>
                    <div class="card-body">
                        <canvas id="qcChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Grafik Bulanan: Pendapatan & Pengeluaran, Efisiensi Produksi -->
    <div class="row mb-4">
        <?php if ($has_finance_access) { ?>
            <div class="col-lg-6 p-2">
                <div class="card">
                    <div class="card-header">Pendapatan & Pengeluaran Bulanan</div>
                    <div class="card-body">
                        <canvas id="monthlyFinancialChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_production_access) { ?>
            <div class="col-lg-6 p-2">
                <div class="card">
                    <div class="card-header">Efisiensi Produksi Bulanan</div>
                    <div class="card-body">
                        <canvas id="monthlyEfficiencyChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Grafik Bulanan Lainnya -->
    <div class="row">
        <?php if ($has_production_access) { ?>
            <div class="col-lg-6 p-2">
                <div class="card">
                    <div class="card-header">Hasil Produksi Bulanan</div>
                    <div class="card-body">
                        <canvas id="monthlyProductionChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_distribution_access) { ?>
            <div class="col-lg-6 p-2">
                <div class="card">
                    <div class="card-header">Distribusi Selesai Bulanan</div>
                    <div class="card-body">
                        <canvas id="monthlyDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if ($has_qc_access) { ?>
            <div class="col-lg-6 p-2">
                <div class="card">
                    <div class="card-header">Kontrol Kualitas Gagal Bulanan</div>
                    <div class="card-body">
                        <canvas id="monthlyQcChart"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Bootstrap JS and Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    <?php if ($has_finance_access) { ?>
        // Grafik bulanan pendapatan & pengeluaran
        const monthlyFinancialChart = new Chart(document.getElementById('monthlyFinancialChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                        label: 'Pendapatan',
                        data: <?php echo json_encode($monthly_revenues); ?>,
                        borderColor: '#28a745',
                        fill: false
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?php echo json_encode($monthly_expenses); ?>,
                        borderColor: '#dc3545',
                        fill: false
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    <?php } ?>

    <?php if ($has_distribution_access) { ?>
        // Grafik status distribusi
        <?php
        $stmt_pending = $pdo->prepare("SELECT COUNT(*) as total FROM distributions WHERE status = 'pending'");
        $stmt_pending->execute();
        $pending = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt_shipped = $pdo->prepare("SELECT COUNT(*) as total FROM distributions WHERE status = 'shipped'");
        $stmt_shipped->execute();
        $shipped = $stmt_shipped->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt_delivered = $pdo->prepare("SELECT COUNT(*) as total FROM distributions WHERE status = 'delivered'");
        $stmt_delivered->execute();
        $delivered = $stmt_delivered->fetch(PDO::FETCH_ASSOC)['total'];
        ?>
        const distributionChart = new Chart(document.getElementById('distributionChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Shipped', 'Delivered'],
                datasets: [{
                    label: 'Status Distribusi',
                    data: [<?php echo $pending; ?>, <?php echo $shipped; ?>, <?php echo $delivered; ?>],
                    backgroundColor: ['#ffc107', '#007bff', '#28a745'],
                    borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });

        // Grafik bulanan distribusi selesai
        const monthlyDistributionChart = new Chart(document.getElementById('monthlyDistributionChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Distribusi Selesai',
                    data: <?php echo json_encode($monthly_distributions); ?>,
                    borderColor: '#007bff',
                    fill: false
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    <?php } ?>

    <?php if ($has_production_access) { ?>
        // Grafik bulanan efisiensi produksi
        const monthlyEfficiencyChart = new Chart(document.getElementById('monthlyEfficiencyChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Efisiensi (%)',
                    data: <?php echo json_encode($monthly_efficiency); ?>,
                    borderColor: '#17a2b8',
                    fill: false
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Grafik bulanan hasil produksi
        const monthlyProductionChart = new Chart(document.getElementById('monthlyProductionChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Hasil Produksi',
                    data: <?php echo json_encode($monthly_production_results); ?>,
                    borderColor: '#17a2b8',
                    fill: false
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    <?php } ?>

    <?php if ($has_qc_access) { ?>
        // Grafik hasil kontrol kualitas
        <?php
        $stmt_passed = $pdo->prepare("SELECT COUNT(*) as total FROM quality_controls WHERE result = 'passed'");
        $stmt_passed->execute();
        $passed = $stmt_passed->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt_failed = $pdo->prepare("SELECT COUNT(*) as total FROM quality_controls WHERE result = 'failed'");
        $stmt_failed->execute();
        $failed = $stmt_failed->fetch(PDO::FETCH_ASSOC)['total'];
        ?>
        const qcChart = new Chart(document.getElementById('qcChart'), {
            type: 'pie',
            data: {
                labels: ['Lulus', 'Gagal'],
                datasets: [{
                    label: 'Hasil Kontrol Kualitas',
                    data: [<?php echo $passed; ?>, <?php echo $failed; ?>],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderColor: ['#ffffff', '#ffffff'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });

        // Grafik bulanan kontrol kualitas gagal
        const monthlyQcChart = new Chart(document.getElementById('monthlyQcChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Kontrol Kualitas Gagal',
                    data: <?php echo json_encode($monthly_quality_controls_failed); ?>,
                    borderColor: '#dc3545',
                    fill: false
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    <?php } ?>
</script>
</body>

</html>