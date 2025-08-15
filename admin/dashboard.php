<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$current_admin = getCurrentAdmin();

// Get statistics
$db = getDB();

// Total pengunjung hari ini
$stmt = $db->query("SELECT COUNT(*) as total FROM visitors WHERE DATE(created_at) = CURDATE()");
$total_today = $stmt->fetch()['total'];

// Total pengunjung yang masih di dalam (belum checkout)
$stmt = $db->query("SELECT COUNT(*) as total FROM visitors WHERE status_kunjungan = 'masuk'");
$total_inside = $stmt->fetch()['total'];

// Total pengunjung bulan ini
$stmt = $db->query("SELECT COUNT(*) as total FROM visitors WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$total_this_month = $stmt->fetch()['total'];

// Average kunjungan per hari bulan ini
$stmt = $db->query("SELECT AVG(daily_count) as avg_daily FROM (SELECT DATE(created_at) as visit_date, COUNT(*) as daily_count FROM visitors WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) GROUP BY DATE(created_at)) as daily_stats");
$avg_daily = round($stmt->fetch()['avg_daily'] ?? 0, 1);

// Get recent visitors (last 10)
$stmt = $db->query("SELECT * FROM visitors ORDER BY created_at DESC LIMIT 10");
$recent_visitors = $stmt->fetchAll();

// Get monthly chart data
$stmt = $db->query("
    SELECT DATE(created_at) as tanggal, COUNT(*) as jumlah 
    FROM visitors 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY tanggal ASC
");
$chart_data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Buku Tamu RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            border-radius: 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <i class="fas fa-hospital fa-2x mb-2"></i>
                        <h6>RS Pelita Insani</h6>
                        <small>Admin Panel</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="visitors.php">
                                <i class="fas fa-users"></i> Data Pengunjung
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Laporan
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-user-cog"></i> Kelola User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="setup.php">
                                <i class="fas fa-cog"></i> Pengaturan
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="../scanner.php" target="_blank">
                                <i class="fas fa-qrcode"></i> QR Scanner
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Form Pengunjung
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-auto pt-3">
                        <hr style="border-color: rgba(255,255,255,0.2)">
                        <div class="text-white px-3 py-2">
                            <small>
                                <i class="fas fa-user"></i> <?= htmlspecialchars($current_admin['nama']) ?><br>
                                <span class="badge bg-light text-dark"><?= ucfirst($current_admin['role']) ?></span>
                            </small>
                        </div>
                        <a href="logout.php" class="nav-link text-white-50">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" onclick="refreshData()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <a href="reports.php" class="btn btn-sm btn-success">
                                <i class="fas fa-download"></i> Export
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Pengunjung Hari Ini</div>
                                        <div class="h3 mb-0"><?= $total_today ?></div>
                                    </div>
                                    <div class="card-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Masih di Dalam</div>
                                        <div class="h3 mb-0"><?= $total_inside ?></div>
                                    </div>
                                    <div class="card-icon" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                                        <i class="fas fa-user-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Total Bulan Ini</div>
                                        <div class="h3 mb-0"><?= $total_this_month ?></div>
                                    </div>
                                    <div class="card-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Rata-rata Harian</div>
                                        <div class="h3 mb-0"><?= $avg_daily ?></div>
                                    </div>
                                    <div class="card-icon" style="background: linear-gradient(135deg, #dc3545, #fd7e14);">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart and Recent Visitors -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-chart-area"></i> Grafik Kunjungan 30 Hari Terakhir</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="visitChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-clock"></i> Pengunjung Terbaru</h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                                <?php if (empty($recent_visitors)): ?>
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-users"></i><br>
                                        Belum ada pengunjung
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_visitors as $visitor): ?>
                                        <div class="d-flex align-items-center p-3 border-bottom">
                                            <div class="me-3">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?= htmlspecialchars($visitor['nama_lengkap']) ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($visitor['asal_instansi']) ?><br>
                                                    <?= date('H:i', strtotime($visitor['jam_masuk'])) ?>
                                                    <?php if ($visitor['status_kunjungan'] == 'keluar'): ?>
                                                        <span class="badge bg-success">Keluar</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Di dalam</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare chart data
        const chartData = <?= json_encode($chart_data) ?>;
        const labels = chartData.map(item => {
            const date = new Date(item.tanggal);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        });
        const data = chartData.map(item => item.jumlah);

        // Create chart
        const ctx = document.getElementById('visitChart').getContext('2d');
        const visitChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pengunjung',
                    data: data,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        function refreshData() {
            location.reload();
        }

        // Auto refresh every 5 minutes
        setInterval(refreshData, 300000);
    </script>
</body>
</html>
