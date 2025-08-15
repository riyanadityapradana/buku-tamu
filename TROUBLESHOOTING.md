# Panduan Troubleshooting - Sistem Buku Tamu RS Pelita Insani

## 🔧 Masalah Umum dan Solusi

### 1. Error "Cannot declare class QRcode"

**Masalah:** `Fatal error: Cannot declare class QRcode, because the name is already in use`

**Penyebab:** 
- Ada library QR Code lain yang sudah di-load sebelumnya
- Konflik dengan extension PHP yang sudah ada
- Class QRcode sudah didefinisikan di tempat lain

**Solusi:**

#### Solusi A: Gunakan Library Baru (Sudah Diimplementasi)
Library baru menggunakan `SimpleQRGenerator` yang menghindari konflik:

```php
// File: lib/qr_generator.php sudah dibuat
require_once 'lib/qr_generator.php';
$qr_generator = new SimpleQRGenerator();
$qr_generator->generateQR($data, $file_path, 200);
```

#### Solusi B: Cek Konflik Library
```bash
# Cek apakah ada library QR lain yang aktif
php -r "if(class_exists('QRcode')) echo 'QRcode class already exists'; else echo 'QRcode class not found';"
```

#### Solusi C: Clear PHP Cache
```bash
# Restart web server
sudo systemctl restart apache2
# atau
sudo service apache2 restart

# Clear opcache jika ada
php -r "if(function_exists('opcache_reset')) opcache_reset();"
```

### 2. QR Code Tidak Muncul

**Masalah:** QR Code tidak tampil atau error saat generate

**Solusi:**

#### Cek Extension PHP
```bash
php -m | grep -i gd
```
Jika tidak ada, install:
```bash
# Ubuntu/Debian
sudo apt-get install php-gd

# CentOS/RHEL
sudo yum install php-gd

# Windows XAMPP - enable di php.ini
extension=gd
```

#### Cek Internet Connection
QR generator menggunakan Google Charts API, pastikan server bisa akses internet:
```bash
curl -I https://chart.googleapis.com
```

#### Test QR Generator
Akses: `http://your-domain.com/buku-tamu/test_qr.php`

### 3. Database Connection Error

**Masalah:** `Database connection failed`

**Solusi:**

#### Cek Kredensial Database
```php
// File: config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'buku_tamu_rs');
```

#### Test Koneksi Manual
```bash
mysql -h localhost -u root -p buku_tamu_rs
```

#### Cek MySQL Service
```bash
# Status MySQL
sudo systemctl status mysql
# atau
sudo service mysql status

# Start MySQL jika stop
sudo systemctl start mysql
```

### 4. Permission Denied Error

**Masalah:** Error saat buat folder atau file

**Solusi:**

```bash
# Set permissions yang benar
chmod 755 buku-tamu/
chmod 755 buku-tamu/uploads/
chmod 755 buku-tamu/qrcodes/
chmod 644 buku-tamu/config/database.php

# Atau untuk development
chmod -R 777 buku-tamu/uploads/
chmod -R 777 buku-tamu/qrcodes/
```

### 5. Session Error

**Masalah:** Login tidak bisa atau session timeout cepat

**Solusi:**

#### Cek Session Configuration
```php
// Di config/database.php, tambahkan:
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
```

#### Clear Session Files
```bash
# Clear session files
sudo rm /tmp/sess_*
# atau sesuai session.save_path di php.ini
```

### 6. .htaccess Error

**Masalah:** 500 Internal Server Error

**Solusi:**

#### Enable mod_rewrite
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Backup .htaccess
```bash
# Rename sementara untuk test
mv .htaccess .htaccess.backup
```

#### Minimal .htaccess
```apache
RewriteEngine On
Options -Indexes
```

### 7. QR Scanner Tidak Berfungsi

**Masalah:** Kamera tidak jalan di QR scanner

**Solusi:**

#### Pastikan HTTPS
QR scanner butuh HTTPS untuk akses kamera:
```apache
# Di .htaccess, uncomment:
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Test Browser Support
- Chrome 60+
- Firefox 60+
- Safari 12+
- Edge 80+

#### Allow Camera Permission
Pastikan browser mengizinkan akses kamera.

### 8. Data Tidak Masuk Database

**Masalah:** Form submit tapi data tidak tersimpan

**Solusi:**

#### Cek PHP Error Log
```bash
tail -f /var/log/php/error.log
# atau
tail -f /var/log/apache2/error.log
```

#### Enable PHP Error Display
```php
// Tambah di config/database.php untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### Test Database Query
```php
// test_db.php
require_once 'config/database.php';
$db = getDB();
$stmt = $db->query("SELECT COUNT(*) as total FROM visitors");
echo "Total visitors: " . $stmt->fetch()['total'];
```

## 🔍 Debugging Tools

### 1. Test QR Generator
```
http://your-domain.com/buku-tamu/test_qr.php
```

### 2. PHP Info
```php
<?php phpinfo(); ?>
```

### 3. Database Test
```php
<?php
require_once 'config/database.php';
try {
    $db = getDB();
    echo "✅ Database OK";
} catch(Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
?>
```

### 4. File Permissions Check
```bash
ls -la buku-tamu/
ls -la buku-tamu/uploads/
ls -la buku-tamu/qrcodes/
```

## 📞 Getting Help

1. **Cek Error Logs:**
   - `/var/log/apache2/error.log`
   - `/var/log/php/error.log`
   - Browser Developer Console

2. **Test Components:**
   - Database connection
   - QR generator
   - File permissions
   - PHP extensions

3. **Contact Support:**
   - Email: riyanadityapradanaa@gmail.com
   - Include: Error message, PHP version, server info

## 🛠️ Quick Fixes

### Reset Everything
```bash
# 1. Backup data
mysqldump -u root -p buku_tamu_rs > backup.sql

# 2. Re-run setup
http://your-domain.com/buku-tamu/setup.php

# 3. Restore data if needed
mysql -u root -p buku_tamu_rs < backup.sql
```

### Alternative QR Method
Jika QR masih bermasalah, gunakan QR manual:
```
http://your-domain.com/buku-tamu/checkout.php?code=KODE_PENGUNJUNG
```

---

**© 2024 RS Pelita Insani - Sistem Buku Tamu Digital**
