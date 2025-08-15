<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$current_admin = getCurrentAdmin();
$db = getDB();

// Handle export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $month = $_GET['month'] ?? date('Y-m');
    
    // Get data for export
    $stmt = $db->prepare("
        SELECT 
            kode_kunjungan,
            nama_lengkap,
            nik,
            jenis_kelamin,
            umur,
            alamat,
            no_telepon,
            asal_instansi,
            tujuan_kunjungan,
            nama_pasien,
            no_ruangan,
            security_penerima,
            jam_masuk,
            jam_keluar,
            status_kunjungan,
            TIMESTAMPDIFF(MINUTE, jam_masuk, COALESCE(jam_keluar, NOW())) as durasi_menit
        FROM visitors 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ? 
        ORDER BY jam_masuk DESC
    ");
    $stmt->execute([$month]);
    $visitors = $stmt->fetchAll();
    
    if ($export_type == 'excel') {
        // Export to CSV (Excel-compatible)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan_kunjungan_' . $month . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, [
            'Kode Kunjungan',
            'Nama Lengkap',
            'NIK',
            'Jenis Kelamin',
            'Umur',
            'Alamat',
            'No. Telepon',
            'Asal Instansi',
            'Tujuan Kunjungan',
            'Nama Pasien',
            'No. Ruangan',
            'Security Penerima',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Durasi (Menit)'
        ]);
        
        // Data
        foreach ($visitors as $visitor) {
            fputcsv($output, [
                $visitor['kode_kunjungan'],
                $visitor['nama_lengkap'],
                $visitor['nik'],
                $visitor['jenis_kelamin'],
                $visitor['umur'],
                $visitor['alamat'],
                $visitor['no_telepon'],
                $visitor['asal_instansi'],
                $visitor['tujuan_kunjungan'],
                $visitor['nama_pasien'],
                $visitor['no_ruangan'],
                $visitor['security_penerima'],
                $visitor['jam_masuk'],
                $visitor['jam_keluar'],
                $visitor['status_kunjungan'],
                $visitor['durasi_menit']
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Get current month data
$current_month = $_GET['month'] ?? date('Y-m');

// Monthly statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_kunjungan,
        COUNT(CASE WHEN status_kunjungan = 'keluar' THEN 1 END) as total_selesai,
        COUNT(CASE WHEN status_kunjungan = 'masuk' THEN 1 END) as total_masih_dalam,
        AVG(TIMESTAMPDIFF(MINUTE, jam_masuk, jam_keluar)) as avg_durasi
    FROM visitors 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
");
$stmt->execute([$current_month]);
$monthly_stats = $stmt->fetch();

// Daily breakdown
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as tanggal,
        COUNT(*) as total_kunjungan,
        COUNT(CASE WHEN status_kunjungan = 'keluar' THEN 1 END) as selesai,
        COUNT(CASE WHEN status_kunjungan = 'masuk' THEN 1 END) as masih_dalam
    FROM visitors 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    GROUP BY DATE(created_at)
    ORDER BY tanggal DESC
");
$stmt->execute([$current_month]);
$daily_breakdown = $stmt->fetchAll();

// Top institutions
$stmt = $db->prepare("
    SELECT 
        asal_instansi,
        COUNT(*) as jumlah_kunjungan
    FROM visitors 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    GROUP BY asal_instansi
    ORDER BY jumlah_kunjungan DESC
    LIMIT 10
");
$stmt->execute([$current_month]);
$top_institutions = $stmt->fetchAll();

// Peak hours
$stmt = $db->prepare("
    SELECT 
        HOUR(jam_masuk) as jam,
        COUNT(*) as jumlah
    FROM visitors 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    GROUP BY HOUR(jam_masuk)
    ORDER BY jumlah DESC
");
$stmt->execute([$current_month]);
$peak_hours = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kunjungan - Admin RS Pelita Insani</title>
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
            transform: translateY(-2px);
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
        @media print {
            .no-print { display: none !important; }
            .sidebar { display: none !important; }
            .main-content { margin: 0 !important; padding: 20px !important; }
            .stats-card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse no-print">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <i class="fas fa-hospital fa-2x mb-2"></i>
                        <h6>RS Pelita Insani</h6>
                        <small>Admin Panel</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="visitors.php">
                                <i class="fas fa-users"></i> Data Pengunjung
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reports.php">
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
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> Pengaturan
                            </a>
                        </li>
                        <?php endif; ?>
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
                    <h1 class="h2">Laporan Kunjungan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0 no-print">
                        <div class="btn-group me-2">
                            <input type="month" id="monthSelector" class="form-control" 
                                   value="<?= $current_month ?>" onchange="changeMonth()">
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success" onclick="exportExcel()">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Header Info -->
                <div class="text-center mb-4">
                    <h3>Laporan Kunjungan RS Pelita Insani</h3>
                    <p class="lead">Periode: <?= formatTanggalIndonesia($current_month . '-01') ?></p>
                    <small class="text-muted">Dicetak pada: <?= formatTanggalIndonesia(date('Y-m-d')) ?> oleh <?= htmlspecialchars($current_admin['nama']) ?></small>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 class="text-primary"><?= number_format($monthly_stats['total_kunjungan']) ?></h4>
                                <p class="text-muted mb-0">Total Kunjungan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h4 class="text-success"><?= number_format($monthly_stats['total_selesai']) ?></h4>
                                <p class="text-muted mb-0">Selesai Berkunjung</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h4 class="text-warning"><?= number_format($monthly_stats['total_masih_dalam']) ?></h4>
                                <p class="text-muted mb-0">Masih di Dalam</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-stopwatch fa-2x text-info mb-2"></i>
                                <h4 class="text-info"><?= round($monthly_stats['avg_durasi'] ?? 0) ?></h4>
                                <p class="text-muted mb-0">Rata-rata Durasi (Menit)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8 mb-4">
                        <div class="card stats-card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-chart-line"></i> Trend Kunjungan Harian</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="dailyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card stats-card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-clock"></i> Jam Sibuk</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="hourlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card stats-card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-calendar-day"></i> Rekap Harian</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Total</th>
                                                <th>Selesai</th>
                                                <th>Dalam</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($daily_breakdown)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">
                                                        Tidak ada data untuk bulan ini
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($daily_breakdown as $day): ?>
                                                    <tr>
                                                        <td><?= date('d/m', strtotime($day['tanggal'])) ?></td>
                                                        <td><strong><?= $day['total_kunjungan'] ?></strong></td>
                                                        <td><span class="text-success"><?= $day['selesai'] ?></span></td>
                                                        <td><span class="text-warning"><?= $day['masih_dalam'] ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card stats-card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-building"></i> Top 10 Instansi</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th>Ranking</th>
                                                <th>Instansi</th>
                                                <th>Kunjungan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($top_institutions)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">
                                                        Tidak ada data instansi
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($top_institutions as $idx => $institution): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($idx < 3): ?>
                                                                <span class="badge bg-warning"><?= $idx + 1 ?></span>
                                                            <?php else: ?>
                                                                <?= $idx + 1 ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($institution['asal_instansi']) ?></td>
                                                        <td><strong><?= $institution['jumlah_kunjungan'] ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-5 pt-4 border-top">
                    <small class="text-muted">
                        Sistem Buku Tamu Digital RS Pelita Insani<br>
                        Laporan digenerate otomatis pada <?= date('d/m/Y H:i:s') ?>
                    </small>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Chart
        const dailyData = <?= json_encode($daily_breakdown) ?>;
        const dailyLabels = dailyData.map(item => {
            const date = new Date(item.tanggal);
            return date.getDate() + '/' + (date.getMonth() + 1);
        }).reverse();
        const dailyValues = dailyData.map(item => item.total_kunjungan).reverse();

        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'Kunjungan',
                    data: dailyValues,
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
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Hourly Chart
        const hourlyData = <?= json_encode($peak_hours) ?>;
        const hourlyLabels = hourlyData.map(item => item.jam + ':00');
        const hourlyValues = hourlyData.map(item => item.jumlah);

        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'doughnut',
            data: {
                labels: hourlyLabels,
                datasets: [{
                    data: hourlyValues,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 10 } }
                    }
                }
            }
        });

        function changeMonth() {
            const month = document.getElementById('monthSelector').value;
            window.location.href = '?month=' + month;
        }

        function exportExcel() {
            const month = document.getElementById('monthSelector').value;
            window.location.href = '?export=excel&month=' + month;
        }
    </script>
</body>
</html>
