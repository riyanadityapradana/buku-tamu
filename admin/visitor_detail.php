<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$current_admin = getCurrentAdmin();

if (!isset($_GET['id'])) {
    header("Location: visitors.php");
    exit;
}

$visitor_id = $_GET['id'];
$db = getDB();

// Get visitor data
$stmt = $db->prepare("SELECT * FROM visitors WHERE id = ?");
$stmt->execute([$visitor_id]);
$visitor = $stmt->fetch();

if (!$visitor) {
    header("Location: visitors.php");
    exit;
}

// Get activity logs for this visitor
$stmt = $db->prepare("
    SELECT al.*, au.nama_lengkap as admin_nama 
    FROM activity_logs al 
    LEFT JOIN admin_users au ON al.admin_id = au.id 
    WHERE al.visitor_id = ? 
    ORDER BY al.created_at DESC
");
$stmt->execute([$visitor_id]);
$activity_logs = $stmt->fetchAll();

// Calculate duration if checked out
$duration = null;
if ($visitor['jam_keluar']) {
    $checkin = new DateTime($visitor['jam_masuk']);
    $checkout = new DateTime($visitor['jam_keluar']);
    $duration = $checkout->diff($checkin);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengunjung - Admin RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .detail-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .qr-code-container {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .status-badge {
            font-size: 1rem;
            padding: 8px 16px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -20px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #007bff;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-user-circle text-primary"></i> Detail Pengunjung</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="visitors.php">Data Pengunjung</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group">
                <a href="visitor_edit.php?id=<?= $visitor['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="visitors.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Main Info -->
            <div class="col-md-8">
                <div class="card detail-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user"></i> Informasi Pengunjung
                            <?php if ($visitor['status_kunjungan'] == 'masuk'): ?>
                                <span class="badge bg-warning status-badge float-end">Di Dalam</span>
                            <?php else: ?>
                                <span class="badge bg-success status-badge float-end">Keluar</span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="info-label">Kode Kunjungan:</label>
                                    <div class="fw-bold text-monospace"><?= htmlspecialchars($visitor['kode_kunjungan']) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Nama Lengkap:</label>
                                    <div class="fw-bold"><?= htmlspecialchars($visitor['nama_lengkap']) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">NIK:</label>
                                    <div><?= htmlspecialchars($visitor['nik'] ?: '-') ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Jenis Kelamin:</label>
                                    <div><?= htmlspecialchars($visitor['jenis_kelamin']) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Umur:</label>
                                    <div><?= $visitor['umur'] ? $visitor['umur'] . ' tahun' : '-' ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">No. Telepon:</label>
                                    <div><?= htmlspecialchars($visitor['no_telepon']) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="info-label">Asal Instansi:</label>
                                    <div><?= htmlspecialchars($visitor['asal_instansi']) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Nama Pasien:</label>
                                    <div><?= htmlspecialchars($visitor['nama_pasien'] ?: '-') ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">No. Ruangan:</label>
                                    <div><?= htmlspecialchars($visitor['no_ruangan'] ?: '-') ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Security Penerima:</label>
                                    <div><?= htmlspecialchars($visitor['security_penerima']) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Jam Masuk:</label>
                                    <div class="text-success fw-bold"><?= date('d/m/Y H:i:s', strtotime($visitor['jam_masuk'])) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Jam Keluar:</label>
                                    <div class="<?= $visitor['jam_keluar'] ? 'text-primary fw-bold' : 'text-muted' ?>">
                                        <?= $visitor['jam_keluar'] ? date('d/m/Y H:i:s', strtotime($visitor['jam_keluar'])) : 'Belum checkout' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="info-label">Alamat:</label>
                                    <div><?= htmlspecialchars($visitor['alamat'] ?: '-') ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="info-label">Tujuan Kunjungan:</label>
                                    <div><?= nl2br(htmlspecialchars($visitor['tujuan_kunjungan'])) ?></div>
                                </div>
                                <?php if ($visitor['catatan']): ?>
                                <div class="mb-3">
                                    <label class="info-label">Catatan:</label>
                                    <div><?= nl2br(htmlspecialchars($visitor['catatan'])) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($duration): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-clock"></i> 
                            <strong>Durasi Kunjungan:</strong> 
                            <?php if ($duration->h > 0): ?>
                                <?= $duration->h ?> jam 
                            <?php endif; ?>
                            <?= $duration->i ?> menit
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="card detail-card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Riwayat Aktivitas</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php if (empty($activity_logs)): ?>
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-history fa-2x mb-2"></i><br>
                                    Belum ada riwayat aktivitas
                                </div>
                            <?php else: ?>
                                <?php foreach ($activity_logs as $log): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>
                                                    <?php
                                                    $activity_names = [
                                                        'checkin' => 'Check-in',
                                                        'checkout' => 'Check-out', 
                                                        'edit' => 'Edit Data',
                                                        'delete' => 'Hapus Data',
                                                        'view' => 'Lihat Data'
                                                    ];
                                                    echo $activity_names[$log['activity_type']] ?? $log['activity_type'];
                                                    ?>
                                                </strong>
                                                <div class="text-muted small">
                                                    <?= htmlspecialchars($log['description']) ?>
                                                    <?php if ($log['admin_nama']): ?>
                                                        <br>oleh: <?= htmlspecialchars($log['admin_nama']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- QR Code -->
                <div class="card detail-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-qrcode"></i> QR Code</h6>
                    </div>
                    <div class="card-body">
                        <div class="qr-code-container">
                            <?php if ($visitor['qr_code_path'] && file_exists('../' . $visitor['qr_code_path'])): ?>
                                <img src="../<?= $visitor['qr_code_path'] ?>" alt="QR Code" class="img-fluid mb-3" style="max-width: 200px;">
                                <div class="d-grid gap-2">
                                    <a href="../qr_display.php?code=<?= $visitor['kode_kunjungan'] ?>" 
                                       target="_blank" class="btn btn-primary btn-sm">
                                        <i class="fas fa-external-link-alt"></i> Lihat QR
                                    </a>
                                    <?php if ($visitor['status_kunjungan'] == 'masuk'): ?>
                                    <a href="../checkout.php?code=<?= $visitor['kode_kunjungan'] ?>" 
                                       target="_blank" class="btn btn-warning btn-sm">
                                        <i class="fas fa-sign-out-alt"></i> Checkout
                                    </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                                    QR Code tidak tersedia
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card detail-card">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="fas fa-tools"></i> Aksi Cepat</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="visitor_edit.php?id=<?= $visitor['id'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Data
                            </a>
                            <?php if ($visitor['status_kunjungan'] == 'masuk'): ?>
                            <a href="../checkout.php?code=<?= $visitor['kode_kunjungan'] ?>" 
                               target="_blank" class="btn btn-success">
                                <i class="fas fa-sign-out-alt"></i> Manual Checkout
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-danger" 
                                    onclick="deleteVisitor(<?= $visitor['id'] ?>, '<?= htmlspecialchars($visitor['nama_lengkap'], ENT_QUOTES) ?>')">
                                <i class="fas fa-trash"></i> Hapus Data
                            </button>
                            <hr>
                            <a href="visitors.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form method="POST" action="visitors.php" id="deleteForm" style="display: none;">
        <input type="hidden" name="delete_id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteVisitor(id, name) {
            if (confirm(`Hapus data pengunjung "${name}"?\nAksi ini tidak dapat dibatalkan.`)) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
