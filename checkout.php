<?php
require_once 'config/database.php';

$success_message = '';
$error_message = '';
$visitor = null;

// Jika ada parameter code dari QR scan
if (isset($_GET['code'])) {
    $kode_kunjungan = $_GET['code'];
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM visitors WHERE kode_kunjungan = ?");
    $stmt->execute([$kode_kunjungan]);
    $visitor = $stmt->fetch();
    
    if ($visitor) {
        if ($visitor['status_kunjungan'] == 'keluar') {
            $error_message = "Pengunjung dengan kode " . $kode_kunjungan . " sudah melakukan checkout pada " . 
                           date('d/m/Y H:i:s', strtotime($visitor['jam_keluar']));
        }
    } else {
        $error_message = "Kode kunjungan tidak ditemukan!";
    }
}

// Proses checkout manual
if ($_POST) {
    try {
        $kode_input = $_POST['kode_kunjungan'];
        
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM visitors WHERE kode_kunjungan = ?");
        $stmt->execute([$kode_input]);
        $visitor = $stmt->fetch();
        
        if (!$visitor) {
            throw new Exception("Kode kunjungan tidak ditemukan!");
        }
        
        if ($visitor['status_kunjungan'] == 'keluar') {
            throw new Exception("Pengunjung sudah melakukan checkout pada " . 
                               date('d/m/Y H:i:s', strtotime($visitor['jam_keluar'])));
        }
        
        // Update status checkout
        $stmt = $db->prepare("UPDATE visitors SET jam_keluar = NOW(), status_kunjungan = 'keluar' WHERE id = ?");
        $stmt->execute([$visitor['id']]);
        
        // Log aktivitas
        logActivity($visitor['id'], null, 'checkout', 'Pengunjung melakukan check-out');
        
        $success_message = "Checkout berhasil! Terima kasih atas kunjungan Anda.";
        
        // Refresh data visitor
        $stmt = $db->prepare("SELECT * FROM visitors WHERE id = ?");
        $stmt->execute([$visitor['id']]);
        $visitor = $stmt->fetch();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Auto checkout jika QR code valid dan belum checkout
if (isset($_GET['code']) && $visitor && $visitor['status_kunjungan'] == 'masuk' && !$_POST) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE visitors SET jam_keluar = NOW(), status_kunjungan = 'keluar' WHERE id = ?");
    $stmt->execute([$visitor['id']]);
    
    // Log aktivitas
    logActivity($visitor['id'], null, 'checkout', 'Pengunjung melakukan check-out via QR scan');
    
    $success_message = "Checkout otomatis berhasil! Terima kasih atas kunjungan Anda.";
    
    // Refresh data visitor
    $stmt = $db->prepare("SELECT * FROM visitors WHERE id = ?");
    $stmt->execute([$visitor['id']]);
    $visitor = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pengunjung - RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .header-bg {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            padding: 2rem 0;
        }
        .form-container {
            max-width: 600px;
            margin: -50px auto 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .visitor-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 20px 0;
        }
        .checkout-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 20px 0;
        }
        .btn-checkout {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <!-- Header -->
    <div class="header-bg text-center">
        <div class="container">
            <h1><i class="fas fa-sign-out-alt"></i> Checkout Pengunjung</h1>
            <p class="lead">RS Pelita Insani</p>
            <small><a href="index.php" class="text-white text-decoration-none">← Kembali ke Pendaftaran</a></small>
        </div>
    </div>

    <!-- Form Container -->
    <div class="container">
        <div class="form-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($visitor && $visitor['status_kunjungan'] == 'keluar'): ?>
                <!-- Informasi Checkout Berhasil -->
                <div class="visitor-card">
                    <div class="text-center mb-3">
                        <i class="fas fa-check-circle fa-3x"></i>
                        <h4 class="mt-2">Checkout Berhasil!</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-4"><strong>Nama:</strong></div>
                        <div class="col-sm-8"><?= htmlspecialchars($visitor['nama_lengkap']) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Kode:</strong></div>
                        <div class="col-sm-8"><?= htmlspecialchars($visitor['kode_kunjungan']) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Jam Masuk:</strong></div>
                        <div class="col-sm-8"><?= date('d/m/Y H:i:s', strtotime($visitor['jam_masuk'])) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>Jam Keluar:</strong></div>
                        <div class="col-sm-8"><?= date('d/m/Y H:i:s', strtotime($visitor['jam_keluar'])) ?></div>
                    </div>
                    
                    <?php
                    $jam_masuk = new DateTime($visitor['jam_masuk']);
                    $jam_keluar = new DateTime($visitor['jam_keluar']);
                    $durasi = $jam_keluar->diff($jam_masuk);
                    ?>
                    <div class="row">
                        <div class="col-sm-4"><strong>Durasi Kunjungan:</strong></div>
                        <div class="col-sm-8">
                            <?php if ($durasi->h > 0): ?>
                                <?= $durasi->h ?> jam 
                            <?php endif; ?>
                            <?= $durasi->i ?> menit
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Kembali ke Beranda
                    </a>
                    <a href="scanner.php" class="btn btn-warning">
                        <i class="fas fa-qrcode"></i> Scan QR Lain
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Form Checkout Manual -->
                <div class="text-center mb-4">
                    <h4><i class="fas fa-sign-out-alt"></i> Checkout Pengunjung</h4>
                    <p class="text-muted">Masukkan kode kunjungan untuk checkout</p>
                </div>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label">Kode Kunjungan</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-qrcode"></i></span>
                            <input type="text" class="form-control" name="kode_kunjungan" 
                                   placeholder="Masukkan atau scan kode kunjungan" 
                                   value="<?= $_GET['code'] ?? '' ?>" required autofocus>
                        </div>
                        <small class="text-muted">
                            Format: RSP + tanggal + nomor urut (contoh: RSP202312010001)
                        </small>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-checkout btn-lg text-white">
                            <i class="fas fa-sign-out-alt"></i> Checkout Sekarang
                        </button>
                    </div>
                </form>

                <div class="checkout-info mt-4">
                    <h6><i class="fas fa-info-circle text-primary"></i> Informasi Checkout</h6>
                    <ul class="mb-0">
                        <li>Scan QR Code pengunjung atau masukkan kode manual</li>
                        <li>Sistem akan otomatis mencatat jam keluar</li>
                        <li>Pengunjung akan mendapat konfirmasi checkout</li>
                        <li>Data kunjungan tersimpan untuk laporan</li>
                    </ul>
                </div>

                <!-- QR Scanner Link -->
                <div class="text-center mt-4">
                    <a href="scanner.php" class="btn btn-outline-warning">
                        <i class="fas fa-camera"></i> Buka QR Scanner
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
