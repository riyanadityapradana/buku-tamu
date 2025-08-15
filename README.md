# Sistem Buku Tamu Digital RS Pelita Insani

Sistem buku tamu digital dengan fitur QR Code untuk rumah sakit, menggantikan Google Form dengan sistem yang lebih canggih dan terintegrasi.

## 🏥 Fitur Utama

### Untuk Pengunjung:
- ✅ Form pendaftaran digital yang user-friendly
- ✅ Generate QR Code otomatis setelah registrasi
- ✅ Checkout dengan scan QR Code
- ✅ Validasi data dan security question

### Untuk Admin:
- ✅ Dashboard dengan statistik real-time
- ✅ Kelola data pengunjung (CRUD)
- ✅ Laporan bulanan dengan grafik
- ✅ Export data ke Excel/CSV
- ✅ Cetak laporan
- ✅ Sistem login multi-role (Admin & Security)

### Untuk Security:
- ✅ QR Code Scanner untuk checkout
- ✅ Form checkout manual
- ✅ Akses terbatas ke data pengunjung

## 📋 Persyaratan Sistem

- **Server:** Apache/Nginx dengan PHP 7.4+
- **Database:** MySQL 5.7+ atau MariaDB 10.3+
- **Browser:** Chrome, Firefox, Safari (support kamera untuk QR scanner)
- **Extensions:** PHP PDO, GD Library untuk QR Code

## 🚀 Instalasi

### 1. Persiapan Database

```sql
-- Buat database baru
CREATE DATABASE buku_tamu_rs;

-- Import struktur database
mysql -u root -p buku_tamu_rs < database.sql
```

### 2. Konfigurasi

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'buku_tamu_rs');
define('BASE_URL', 'http://your-domain.com/buku-tamu/');
```

### 3. Permissions

Pastikan folder berikut dapat ditulis:
```bash
chmod 755 uploads/
chmod 755 qrcodes/
```

### 4. Access URLs

- **Form Pengunjung:** `http://your-domain.com/buku-tamu/`
- **Admin Login:** `http://your-domain.com/buku-tamu/admin/login.php`
- **QR Scanner:** `http://your-domain.com/buku-tamu/scanner.php`

## 👥 Default Login

### Admin
- **Username:** admin
- **Password:** admin123
- **Akses:** Full dashboard, laporan, pengaturan

### Security
- **Username:** security  
- **Password:** admin123
- **Akses:** Terbatas untuk checkout dan monitoring

## 📱 Cara Penggunaan

### Alur Pengunjung:

1. **Registrasi:**
   - Buka link form pendaftaran
   - Isi data lengkap sesuai form
   - Klik "Daftar & Generate QR Code"

2. **Mendapat QR Code:**
   - Sistem generate QR Code unik
   - Screenshot atau cetak QR Code
   - Simpan untuk checkout nanti

3. **Checkout:**
   - Tunjukkan QR Code ke security
   - Security scan dengan smartphone/tablet
   - Jam keluar tercatat otomatis

### Alur Admin:

1. **Login:** Akses admin panel dengan kredensial
2. **Dashboard:** Monitor statistik real-time
3. **Data Pengunjung:** Kelola data, edit, hapus
4. **Laporan:** Generate laporan bulanan, export data
5. **Pengaturan:** Kelola user, security staff

## 🔧 Struktur File

```
buku-tamu/
├── index.php              # Form pendaftaran pengunjung
├── qr_display.php         # Tampilan QR Code
├── checkout.php           # Form checkout
├── scanner.php            # QR Code scanner
├── database.sql           # Struktur database
├── config/
│   └── database.php       # Konfigurasi database
├── admin/
│   ├── login.php          # Login admin
│   ├── dashboard.php      # Dashboard utama
│   ├── visitors.php       # Data pengunjung
│   ├── reports.php        # Laporan
│   └── auth_check.php     # Autentikasi
├── vendor/
│   └── qrcode/           # Library QR Code
├── uploads/              # Upload foto (jika ada)
└── qrcodes/              # Generated QR codes
```

## 🛠️ Customization

### Menambah Field Form:

1. **Update Database:**
```sql
ALTER TABLE visitors ADD COLUMN field_baru VARCHAR(100);
```

2. **Update Form (`index.php`):**
```html
<input type="text" name="field_baru" class="form-control">
```

3. **Update Insert Query:**
```php
$stmt->execute([..., $_POST['field_baru']]);
```

### Mengubah Security Staff:

```sql
INSERT INTO security_staff (nama_petugas) VALUES ('Nama Baru');
UPDATE security_staff SET aktif = 0 WHERE nama_petugas = 'Nama Lama';
```

## 📊 Fitur Laporan

- **Statistik Real-time:** Total kunjungan, yang masih dalam, rata-rata durasi
- **Grafik Trend:** Visualisasi kunjungan harian
- **Top Instansi:** Ranking institusi dengan kunjungan terbanyak
- **Export Excel:** Unduh data dalam format CSV
- **Cetak PDF:** Generate laporan untuk arsip

## 🔒 Keamanan

- Session-based authentication
- SQL injection protection dengan prepared statements
- XSS protection dengan htmlspecialchars
- Role-based access control
- Activity logging untuk audit trail

## 🐛 Troubleshooting

### QR Code tidak muncul:
1. Pastikan folder `qrcodes/` dapat ditulis
2. Cek PHP GD extension aktif
3. Periksa konfigurasi BASE_URL

### Scanner tidak berfungsi:
1. Pastikan HTTPS untuk akses kamera
2. Cek browser support WebRTC
3. Izinkan akses kamera di browser

### Database error:
1. Periksa kredensial database
2. Pastikan database sudah dibuat
3. Import ulang file `database.sql`

## 📞 Support

Untuk pertanyaan atau bantuan:
- **Email:** riyanadityapradanaa@gmail.com
- **GitHub:** [Repository Link]

## 📝 Changelog

### v1.0.0 (Initial Release)
- Form pendaftaran pengunjung
- QR Code generation & scanning
- Dashboard admin dengan statistik
- Laporan bulanan dengan export
- Multi-role authentication
- Responsive mobile-friendly design

---

**© 2024 RS Pelita Insani - Sistem Buku Tamu Digital**
