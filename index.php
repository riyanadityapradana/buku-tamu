<?php
require_once 'config/database.php';

// Ambil daftar security staff
$db = getDB();
$stmt = $db->query("SELECT nama_petugas FROM security_staff WHERE aktif = 1 ORDER BY nama_petugas");
$security_staff = $stmt->fetchAll(PDO::FETCH_COLUMN);

$success_message = '';
$error_message = '';

if ($_POST) {
    try {
        // Validasi input
        $required_fields = ['nama_lengkap', 'jenis_kelamin', 'no_telepon', 'asal_instansi', 'tujuan_kunjungan', 'security_penerima'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field " . ucfirst(str_replace('_', ' ', $field)) . " harus diisi!");
            }
        }
        
        // Generate kode kunjungan unik
        $kode_kunjungan = generateVisitorCode();
        
        // Insert data pengunjung
        $stmt = $db->prepare("INSERT INTO visitors (kode_kunjungan, nama_lengkap, nik, alamat, jenis_kelamin, umur, no_telepon, asal_instansi, tujuan_kunjungan, nama_pasien, no_ruangan, security_penerima) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $kode_kunjungan,
            $_POST['nama_lengkap'],
            $_POST['nik'] ?? null,
            $_POST['alamat'] ?? null,
            $_POST['jenis_kelamin'],
            $_POST['umur'] ?? null,
            $_POST['no_telepon'],
            $_POST['asal_instansi'],
            $_POST['tujuan_kunjungan'],
            $_POST['nama_pasien'] ?? null,
            $_POST['no_ruangan'] ?? null,
            $_POST['security_penerima']
        ]);
        
        $visitor_id = $db->lastInsertId();
        
        // Generate QR Code
        try {
            require_once 'lib/qr_generator.php';
            $qr_path = QR_CODE_PATH . $kode_kunjungan . '.png';
            $qr_data = BASE_URL . 'checkout.php?code=' . $kode_kunjungan;
            
            // Buat direktori jika belum ada
            if (!file_exists(QR_CODE_PATH)) {
                mkdir(QR_CODE_PATH, 0755, true);
            }
            
            // Generate QR Code using new library
            $qr_generator = new SimpleQRGenerator();
            $qr_result = $qr_generator->generateQR($qr_data, $qr_path, 200);
            
            if (!$qr_result) {
                // QR generation failed, but continue without it
                $qr_path = null;
            }
        } catch (Exception $qr_error) {
            // Log QR error but don't stop registration
            error_log("QR Code generation error: " . $qr_error->getMessage());
            $qr_path = null;
        }
        
        // Update path QR code di database
        $stmt = $db->prepare("UPDATE visitors SET qr_code_path = ? WHERE id = ?");
        $stmt->execute([$qr_path, $visitor_id]);
        
        // Log aktivitas
        logActivity($visitor_id, null, 'checkin', 'Pengunjung melakukan check-in');
        
        $success_message = "Pendaftaran berhasil! Kode kunjungan Anda: " . $kode_kunjungan;
        
        // Redirect ke halaman QR code
        header("Location: qr_display.php?code=" . $kode_kunjungan);
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .header-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .form-container {
            max-width: 800px;
            margin: -50px auto 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <!-- Header -->
    <div class="header-bg text-center">
        <div class="container">
            <h1><i class="fas fa-hospital"></i> Buku Tamu RS Pelita Insani</h1>
            <p class="lead">Untuk para tamu yang akan berkunjung ke Rs. Pelita Insani di harapkan mengisi data berikut:</p>
            <small>riyanadityapradanaa@gmail.com | <a href="admin/login.php" class="text-white text-decoration-none">Login Admin</a></small>
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

            <div class="text-center mb-4">
                <h4><i class="fas fa-user-edit"></i> Form Pendaftaran Pengunjung</h4>
                <p class="text-muted">Silakan isi data dengan lengkap dan benar</p>
            </div>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="nama_lengkap" required 
                                   placeholder="Masukkan nama lengkap" value="<?= $_POST['nama_lengkap'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIK/No. Identitas</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" name="nik" 
                                   placeholder="Nomor KTP/SIM/Passport" value="<?= $_POST['nik'] ?? '' ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis Kelamin <span class="required">*</span></label>
                        <select class="form-select" name="jenis_kelamin" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki" <?= ($_POST['jenis_kelamin'] ?? '') == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= ($_POST['jenis_kelamin'] ?? '') == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Umur</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="number" class="form-control" name="umur" min="1" max="120"
                                   placeholder="Umur dalam tahun" value="<?= $_POST['umur'] ?? '' ?>">
                            <span class="input-group-text">tahun</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alamat Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea class="form-control" name="alamat" rows="3" 
                                  placeholder="Alamat lengkap tempat tinggal"><?= $_POST['alamat'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. Telepon/Handphone <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" name="no_telepon" required 
                                   placeholder="08xxxxxxxxxx" value="<?= $_POST['no_telepon'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Asal Instansi <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" name="asal_instansi" required 
                                   placeholder="Nama instansi/perusahaan" value="<?= $_POST['asal_instansi'] ?? '' ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Pasien yang Dikunjungi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-injured"></i></span>
                            <input type="text" class="form-control" name="nama_pasien" 
                                   placeholder="Nama pasien (jika ada)" value="<?= $_POST['nama_pasien'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. Ruangan/Kamar</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-door-open"></i></span>
                            <input type="text" class="form-control" name="no_ruangan" 
                                   placeholder="Contoh: VIP-01, Kelas 1-A" value="<?= $_POST['no_ruangan'] ?? '' ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tujuan Kunjungan/Keperluan <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-clipboard-list"></i></span>
                        <textarea class="form-control" name="tujuan_kunjungan" rows="3" required 
                                  placeholder="Jelaskan tujuan kunjungan Anda"><?= $_POST['tujuan_kunjungan'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Security Penerima <span class="required">*</span></label>
                    <select class="form-select" name="security_penerima" required>
                        <option value="">Pilih nama petugas security</option>
                        <?php foreach ($security_staff as $petugas): ?>
                            <option value="<?= htmlspecialchars($petugas) ?>" 
                                    <?= ($_POST['security_penerima'] ?? '') == $petugas ? 'selected' : '' ?>>
                                <?= htmlspecialchars($petugas) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-qrcode"></i> Daftar & Generate QR Code
                    </button>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <span class="required">*</span> Menunjukkan pertanyaan yang wajib diisi
                    </small>
                </div>
            </form>
        </div>
    </div>

    <!-- QR Scanner Button for Security -->
    <div class="position-fixed bottom-0 end-0 m-4">
        <a href="scanner.php" class="btn btn-warning btn-lg rounded-circle shadow" title="Scan QR Code untuk Checkout">
            <i class="fas fa-camera fa-2x"></i>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
