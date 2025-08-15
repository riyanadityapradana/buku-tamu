<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'buku_tamu_rs');

// Konfigurasi Aplikasi
define('BASE_URL', 'http://localhost/buku-tamu/');
define('UPLOAD_PATH', 'uploads/');
define('QR_CODE_PATH', 'qrcodes/');

// Timezone
date_default_timezone_set('Asia/Jakarta');


// Session settings (hanya jika session belum dimulai)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    session_set_cookie_params(3600);
}

// Error reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Fungsi helper untuk koneksi database
function getDB() {
    return Database::getInstance()->getConnection();
}

// Fungsi untuk generate kode kunjungan unik
function generateVisitorCode() {
    $date = date('Ymd');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return 'RSP' . $date . $random;
}

// Fungsi untuk format tanggal Indonesia
function formatTanggalIndonesia($date) {
    $bulan = array(
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    );
    
    $timestamp = strtotime($date);
    $tanggal = date('j', $timestamp);
    $bulan_idx = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $tanggal . ' ' . $bulan[$bulan_idx] . ' ' . $tahun;
}

// Fungsi untuk log aktivitas
function logActivity($visitor_id, $admin_id, $activity_type, $description) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $db->prepare("INSERT INTO activity_logs (visitor_id, admin_id, activity_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$visitor_id, $admin_id, $activity_type, $description, $ip, $user_agent]);
}
?>
