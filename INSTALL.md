# Petunjuk Instalasi Sistem Buku Tamu RS Pelita Insani

## 📋 Persyaratan Server

### Minimum Requirements:
- **PHP:** 7.4 atau lebih tinggi
- **MySQL:** 5.7 atau MariaDB 10.3+
- **Apache/Nginx:** dengan mod_rewrite enabled
- **Disk Space:** Minimal 50MB
- **Memory:** 256MB

### PHP Extensions yang Diperlukan:
```
php-mysql (PDO)
php-gd (untuk QR Code)
php-mbstring
php-json
php-session
```

### Browser Support:
- Chrome 60+
- Firefox 60+
- Safari 12+
- Edge 80+

## 🚀 Instalasi Cepat (Otomatis)

### Method 1: Setup Wizard

1. **Upload File**
   ```bash
   # Upload semua file ke folder web server
   # Contoh: /var/www/html/buku-tamu/ atau C:\xampp\htdocs\buku-tamu\
   ```

2. **Akses Setup Wizard**
   ```
   http://your-domain.com/buku-tamu/setup.php
   ```

3. **Ikuti 3 Langkah Setup:**
   - **Step 1:** Konfigurasi Database
   - **Step 2:** Buat Tabel Database  
   - **Step 3:** Finalisasi Setup

4. **Hapus File Setup**
   ```bash
   rm setup.php  # Untuk keamanan
   ```

## 🔧 Instalasi Manual

### Step 1: Persiapan Database

```sql
-- Login ke MySQL
mysql -u root -p

-- Buat database
CREATE DATABASE buku_tamu_rs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Buat user (opsional, untuk keamanan)
CREATE USER 'bukutamu'@'localhost' IDENTIFIED BY 'password_kuat';
GRANT ALL PRIVILEGES ON buku_tamu_rs.* TO 'bukutamu'@'localhost';
FLUSH PRIVILEGES;

-- Import struktur database
USE buku_tamu_rs;
SOURCE database.sql;
```

### Step 2: Konfigurasi File

1. **Copy dan Edit Config**
   ```bash
   cp config/database.php.example config/database.php
   ```

2. **Edit Database Config**
   ```php
   // config/database.php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'bukutamu');        // atau 'root'
   define('DB_PASS', 'password_kuat');   // password database
   define('DB_NAME', 'buku_tamu_rs');
   define('BASE_URL', 'https://yourdomain.com/buku-tamu/');
   ```

### Step 3: Set Permissions

```bash
# Buat folder yang diperlukan
mkdir -p uploads qrcodes config

# Set permissions (Linux/Mac)
chmod 755 uploads/
chmod 755 qrcodes/
chmod 644 config/database.php

# Untuk Windows XAMPP
# Biasanya permissions sudah OK secara default
```

### Step 4: Apache Configuration

1. **Enable mod_rewrite**
   ```bash
   # Ubuntu/Debian
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   
   # CentOS/RHEL  
   # Biasanya sudah enabled
   ```

2. **Virtual Host (Opsional)**
   ```apache
   <VirtualHost *:80>
       DocumentRoot /var/www/html/buku-tamu
       ServerName bukutamu.yourdomain.com
       
       <Directory /var/www/html/buku-tamu>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

## 🔐 Konfigurasi Keamanan

### 1. Database Security

```sql
-- Ganti password default admin
UPDATE admin_users SET password = '$2y$10$NewHashedPassword' WHERE username = 'admin';

-- Hapus user yang tidak perlu
DELETE FROM admin_users WHERE username = 'demo';
```

### 2. File Permissions

```bash
# Set permissions yang aman
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 config/database.php
```

### 3. SSL/HTTPS Setup

1. **Install SSL Certificate**
2. **Update .htaccess** (uncomment HTTPS redirect)
3. **Update config** dengan BASE_URL https://

### 4. Hide Sensitive Info

```apache
# Tambah ke .htaccess
<Files "*.log">
    Order Allow,Deny
    Deny from all
</Files>

<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>
```

## 🗂️ Struktur Folder

```
buku-tamu/
├── index.php              # Form pendaftaran utama
├── checkout.php           # Sistem checkout
├── scanner.php            # QR Scanner
├── qr_display.php         # Display QR Code
├── setup.php              # Setup wizard (hapus setelah install)
├── database.sql           # Struktur database
├── .htaccess              # Apache configuration
├── README.md              # Dokumentasi
├── INSTALL.md             # Petunjuk instalasi
├── admin/                 # Panel admin
│   ├── login.php          # Login admin
│   ├── dashboard.php      # Dashboard utama
│   ├── visitors.php       # Kelola pengunjung
│   ├── reports.php        # Laporan
│   ├── auth_check.php     # Cek autentikasi
│   └── logout.php         # Logout
├── config/                # File konfigurasi
│   ├── database.php       # Config database
│   └── .installed         # Marker instalasi
├── vendor/                # Library eksternal
│   └── qrcode/           # QR Code library
├── uploads/               # Upload files (foto, etc)
├── qrcodes/               # Generated QR codes
├── 404.html              # Error page 404
└── 403.html              # Error page 403
```

## 🧪 Testing Installation

### 1. Test Database Connection
```php
// test_db.php (hapus setelah test)
<?php
require_once 'config/database.php';
try {
    $db = getDB();
    echo "✅ Database connection successful!";
} catch(Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
```

### 2. Test QR Code Generation
1. Daftar sebagai pengunjung
2. Cek apakah QR code muncul
3. Test scan QR code untuk checkout

### 3. Test Admin Panel
1. Login dengan `admin / admin123`
2. Cek dashboard statistik
3. Test CRUD pengunjung
4. Test generate laporan

## 🔄 Update System

### Backup Data
```bash
# Backup database
mysqldump -u root -p buku_tamu_rs > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz buku-tamu/
```

### Update Process
1. Backup data dan files
2. Download versi terbaru
3. Replace files (kecuali config/)
4. Run database migrations jika ada
5. Test functionality

## 🐛 Troubleshooting

### Problem: "500 Internal Server Error"
**Solution:**
```bash
# Cek error log
tail -f /var/log/apache2/error.log

# Cek PHP error log
tail -f /var/log/php/error.log

# Cek permissions
ls -la buku-tamu/
```

### Problem: "Database connection failed"
**Solution:**
1. Cek kredensial di `config/database.php`
2. Pastikan MySQL service running
3. Test koneksi manual:
   ```bash
   mysql -h localhost -u username -p database_name
   ```

### Problem: "QR Code not generated"
**Solution:**
1. Cek PHP GD extension: `php -m | grep -i gd`
2. Cek folder permissions: `chmod 755 qrcodes/`
3. Cek disk space: `df -h`

### Problem: "Camera not working in scanner"
**Solution:**
1. Pastikan menggunakan HTTPS
2. Allow camera permission di browser
3. Test di browser yang support WebRTC

### Problem: "Session timeout too fast"
**Solution:**
```php
// Tambah di config/database.php
ini_set('session.gc_maxlifetime', 7200); // 2 hours
session_set_cookie_params(7200);
```

## 📱 Mobile Optimization

### 1. Test Responsive Design
- Test di berbagai ukuran layar
- Pastikan QR scanner berfungsi di mobile
- Test form input di smartphone

### 2. PWA Setup (Optional)
1. Tambah manifest.json
2. Implement service worker
3. Add to homescreen functionality

## 🔧 Customization

### Menambah Field Baru

1. **Update Database:**
```sql
ALTER TABLE visitors ADD COLUMN field_baru VARCHAR(255);
```

2. **Update Form:** Edit `index.php`
```html
<input type="text" name="field_baru" class="form-control">
```

3. **Update Insert Query:** Edit `index.php`
```php
$stmt->execute([..., $_POST['field_baru']]);
```

### Custom Styling

1. **Override CSS:**
```html
<style>
/* Custom styles */
.custom-theme {
    background: your-custom-gradient;
}
</style>
```

2. **Custom Logo:**
- Replace logo di header
- Update favicon.ico

## 📞 Support & Contact

- **Email:** riyanadityapradanaa@gmail.com
- **Documentation:** README.md
- **Issues:** Create GitHub issue

---

**© 2024 RS Pelita Insani - Sistem Buku Tamu Digital**
