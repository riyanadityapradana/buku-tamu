<?php
require_once 'config/database.php';

if (!isset($_GET['code'])) {
    header("Location: index.php");
    exit;
}

$kode_kunjungan = $_GET['code'];

// Ambil data pengunjung
$db = getDB();
$stmt = $db->prepare("SELECT * FROM visitors WHERE kode_kunjungan = ? AND status_kunjungan = 'masuk'");
$stmt->execute([$kode_kunjungan]);
$visitor = $stmt->fetch();

if (!$visitor) {
    header("Location: index.php");
    exit;
}

$qr_file = $visitor['qr_code_path'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Kunjungan - RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .qr-container {
            max-width: 500px;
            margin: 50px auto;
            text-align: center;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .qr-code {
            border: 3px solid #667eea;
            border-radius: 10px;
            padding: 20px;
            background: white;
            margin: 20px 0;
        }
        .visitor-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 20px 0;
            text-align: left;
        }
        .print-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        @media print {
            .no-print { display: none !important; }
            .qr-container { 
                box-shadow: none; 
                margin: 0;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <div class="container">
        <div class="qr-container">
            <div class="text-center mb-4">
                <h3><i class="fas fa-hospital text-primary"></i> RS Pelita Insani</h3>
                <h5 class="text-success"><i class="fas fa-check-circle"></i> Pendaftaran Berhasil!</h5>
                <p class="text-muted">Simpan QR Code ini untuk checkout</p>
            </div>

            <!-- Informasi Pengunjung -->
            <div class="visitor-info">
                <h6><i class="fas fa-user"></i> Informasi Pengunjung</h6>
                <div class="row">
                    <div class="col-6"><strong>Kode:</strong></div>
                    <div class="col-6"><?= htmlspecialchars($visitor['kode_kunjungan']) ?></div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Nama:</strong></div>
                    <div class="col-6"><?= htmlspecialchars($visitor['nama_lengkap']) ?></div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Telepon:</strong></div>
                    <div class="col-6"><?= htmlspecialchars($visitor['no_telepon']) ?></div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Instansi:</strong></div>
                    <div class="col-6"><?= htmlspecialchars($visitor['asal_instansi']) ?></div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Jam Masuk:</strong></div>
                    <div class="col-6"><?= date('d/m/Y H:i:s', strtotime($visitor['jam_masuk'])) ?></div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Security:</strong></div>
                    <div class="col-6"><?= htmlspecialchars($visitor['security_penerima']) ?></div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="qr-code">
                <?php if ($visitor['qr_code_path'] && file_exists($qr_file)): ?>
                    <img src="<?= $qr_file ?>" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                <?php else: ?>
                    <!-- Generate QR code on-the-fly if not exists -->
                    <img src="generate_qr.php?code=<?= urlencode($visitor['kode_kunjungan']) ?>" 
                         alt="QR Code" class="img-fluid" style="max-width: 200px;">
                <?php endif; ?>
                <p class="mt-2 mb-0"><strong>Scan untuk Checkout</strong></p>
                <small class="text-muted">Tunjukkan ke security saat keluar</small>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 justify-content-center no-print">
                <button onclick="window.print()" class="btn print-btn text-white">
                    <i class="fas fa-print"></i> Cetak QR Code
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Kembali
                </a>
            </div>

            <div class="mt-4 no-print">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Petunjuk:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Simpan atau screenshot QR Code ini</li>
                        <li>Tunjukkan ke security saat hendak keluar</li>
                        <li>Security akan scan QR Code untuk mencatat jam keluar</li>
                        <li>Jangan kehilangan QR Code ini</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
