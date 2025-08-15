-- Database untuk Sistem Buku Tamu RS Pelita Insani
-- Buat database terlebih dahulu: CREATE DATABASE buku_tamu_rs;

USE buku_tamu_rs;

-- Tabel untuk admin/user login
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'security') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel untuk daftar petugas security
CREATE TABLE security_staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_petugas VARCHAR(100) NOT NULL,
    shift ENUM('pagi', 'siang', 'malam') DEFAULT 'pagi',
    aktif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel utama untuk buku tamu
CREATE TABLE visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_kunjungan VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    nik VARCHAR(20),
    alamat TEXT,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    umur INT,
    no_telepon VARCHAR(20) NOT NULL,
    asal_instansi VARCHAR(100) NOT NULL,
    tujuan_kunjungan TEXT NOT NULL,
    nama_pasien VARCHAR(100),
    no_ruangan VARCHAR(20),
    security_penerima VARCHAR(100) NOT NULL,
    jam_masuk TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    jam_keluar TIMESTAMP NULL,
    status_kunjungan ENUM('masuk', 'keluar') DEFAULT 'masuk',
    qr_code_path VARCHAR(255),
    foto_pengunjung VARCHAR(255),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel untuk log aktivitas
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_id INT,
    admin_id INT,
    activity_type ENUM('checkin', 'checkout', 'edit', 'delete', 'view') NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Insert data default admin
INSERT INTO admin_users (username, password, nama_lengkap, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@rspelitainsani.com', 'admin'),
('security', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Security', 'security@rspelitainsani.com', 'security');

-- Insert data security staff berdasarkan form
INSERT INTO security_staff (nama_petugas) VALUES 
('Agus'),
('Herman'),
('Bunga'),
('Vandi'),
('Hendra'),
('Rizali'),
('Yuda'),
('Julian'),
('Wildan'),
('Eko');

-- Index untuk optimasi performa
CREATE INDEX idx_visitors_tanggal ON visitors(created_at);
CREATE INDEX idx_visitors_status ON visitors(status_kunjungan);
CREATE INDEX idx_visitors_kode ON visitors(kode_kunjungan);
CREATE INDEX idx_activity_logs_date ON activity_logs(created_at);
