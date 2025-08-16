<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

$id = $_GET['id'] ?? null;
if (!$id) {
    die('ID pengunjung tidak ditemukan.');
}

// Ambil data pengunjung
$stmt = $db->prepare('SELECT * FROM visitors WHERE id = ?');
$stmt->execute([$id]);
$visitor = $stmt->fetch();
if (!$visitor) {
    die('Data pengunjung tidak ditemukan.');
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $asal_instansi = $_POST['asal_instansi'] ?? '';
    $no_telepon = $_POST['no_telepon'] ?? '';
    $tujuan_kunjungan = $_POST['tujuan_kunjungan'] ?? '';
    $nama_pasien = $_POST['nama_pasien'] ?? '';
    $no_ruangan = $_POST['no_ruangan'] ?? '';

    $stmt = $db->prepare('UPDATE visitors SET nik = ?, nama_lengkap = ?, jenis_kelamin = ?, asal_instansi = ?, no_telepon = ?, tujuan_kunjungan = ?, nama_pasien = ?, no_ruangan = ? WHERE id = ?');
    $stmt->execute([$nik, $nama_lengkap, $jenis_kelamin, $asal_instansi, $no_telepon, $tujuan_kunjungan, $nama_pasien, $no_ruangan, $id]);
    header('Location: visitors.php?success=edit');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengunjung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Data Pengunjung</h2>
    <form method="POST">
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-3 border-primary">
            <div class="card-header bg-primary text-white fw-bold">Data Bisa Diedit</div>
            <div class="card-body">
              <div class="mb-3">
                  <label class="form-label">NIK</label>
                  <input type="text" name="nik" class="form-control" value="<?= htmlspecialchars($visitor['nik']) ?>">
              </div>
              <div class="mb-3">
                  <label class="form-label">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($visitor['nama_lengkap']) ?>" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Jenis Kelamin</label>
                  <select name="jenis_kelamin" class="form-select" required>
                      <option value="Laki-laki" <?= $visitor['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                      <option value="Perempuan" <?= $visitor['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                  </select>
              </div>
              <div class="mb-3">
                  <label class="form-label">No. Telepon</label>
                  <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($visitor['no_telepon']) ?>">
              </div>
              <div class="mb-3">
                  <label class="form-label">Asal Instansi</label>
                  <input type="text" name="asal_instansi" class="form-control" value="<?= htmlspecialchars($visitor['asal_instansi']) ?>">
              </div>
              <div class="mb-3">
                  <label class="form-label">Tujuan Kunjungan</label>
                  <input type="text" name="tujuan_kunjungan" class="form-control" value="<?= htmlspecialchars($visitor['tujuan_kunjungan']) ?>">
              </div>
              <div class="mb-3">
                  <label class="form-label">Nama Pasien</label>
                  <input type="text" name="nama_pasien" class="form-control" value="<?= htmlspecialchars($visitor['nama_pasien']) ?>">
              </div>
              <div class="mb-3">
                  <label class="form-label">No. Ruangan</label>
                  <input type="text" name="no_ruangan" class="form-control" value="<?= htmlspecialchars($visitor['no_ruangan']) ?>">
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card mb-3 border-warning">
            <div class="card-header bg-warning text-dark fw-bold">Data Hanya Dibaca</div>
            <div class="card-body">
              <div class="mb-3">
                  <label class="form-label">Umur</label>
                  <input type="text" class="form-control" value="<?= htmlspecialchars($visitor['umur']) ?>" readonly>
              </div>
              <div class="mb-3">
                  <label class="form-label">Alamat</label>
                  <textarea class="form-control" readonly><?= htmlspecialchars($visitor['alamat']) ?></textarea>
              </div>
              <div class="mb-3">
                  <label class="form-label">Security Penerima</label>
                  <input type="text" class="form-control" value="<?= htmlspecialchars($visitor['security_penerima']) ?>" readonly>
              </div>
              <div class="mb-3">
                  <label class="form-label">Jam Masuk</label>
                  <input type="text" class="form-control text-success fw-bold" value="<?= htmlspecialchars($visitor['jam_masuk']) ?>" readonly>
              </div>
              <div class="mb-3">
                  <label class="form-label">Jam Keluar</label>
                  <input type="text" class="form-control text-danger fw-bold" value="<?= htmlspecialchars($visitor['jam_keluar']) ?>" readonly>
              </div>
            </div>
          </div>
        </div>
      </div>
      <button type="submit" class="btn btn-success mt-3">Simpan Perubahan</button>
      <a href="visitors.php" class="btn btn-secondary mt-3">Batal</a>
    </form>
</div>
</body>
</html>
