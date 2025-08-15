<?php
// Setup dan instalasi otomatis untuk Sistem Buku Tamu RS Pelita Insani
session_start();

$step = $_GET['step'] ?? 1;
$error_message = '';
$success_message = '';

// Check if already installed
if (file_exists('config/.installed') && $step == 1) {
    header("Location: index.php");
    exit;
}

if ($_POST) {
    if ($step == 1) {
        // Step 1: Database Configuration
        try {
            $db_host = $_POST['db_host'];
            $db_user = $_POST['db_user'];
            $db_pass = $_POST['db_pass'];
            $db_name = $_POST['db_name'];
            $base_url = $_POST['base_url'];
            
            // Test database connection
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // Save configuration
            $_SESSION['config'] = [
                'db_host' => $db_host,
                'db_user' => $db_user,
                'db_pass' => $db_pass,
                'db_name' => $db_name,
                'base_url' => $base_url
            ];
            
            $success_message = "Koneksi database berhasil! Database '$db_name' telah dibuat.";
            
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
        
    } elseif ($step == 2) {
        // Step 2: Create Tables and Insert Data
        try {
            $config = $_SESSION['config'];
            $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']}", 
                          $config['db_user'], $config['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Read and execute SQL file
            $sql = file_get_contents('database.sql');
            
            // Remove database creation commands as we already created it
            $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
            $sql = preg_replace('/USE.*?;/i', '', $sql);
            
            // Split and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            $success_message = "Database tables berhasil dibuat dan data default telah dimasukkan.";
            
        } catch (Exception $e) {
            $error_message = "Error creating tables: " . $e->getMessage();
        }
        
    } elseif ($step == 3) {
        // Step 3: Create Config File and Folders
        try {
            $config = $_SESSION['config'];
            
            // Create config file
            $config_content = '<?php
// Konfigurasi Database
define(\'DB_HOST\', \'' . $config['db_host'] . '\');
define(\'DB_USER\', \'' . $config['db_user'] . '\');
define(\'DB_PASS\', \'' . $config['db_pass'] . '\');
define(\'DB_NAME\', \'' . $config['db_name'] . '\');

// Konfigurasi Aplikasi
define(\'BASE_URL\', \'' . $config['base_url'] . '\');
define(\'UPLOAD_PATH\', \'uploads/\');
define(\'QR_CODE_PATH\', \'qrcodes/\');

// Timezone
date_default_timezone_set(\'Asia/Jakarta\');

// Session settings
ini_set(\'session.gc_maxlifetime\', 3600);
session_set_cookie_params(3600);

// Error reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set(\'display_errors\', 1);

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

function getDB() {
    return Database::getInstance()->getConnection();
}

function generateVisitorCode() {
    $date = date(\'Ymd\');
    $random = str_pad(mt_rand(1, 9999), 4, \'0\', STR_PAD_LEFT);
    return \'RSP\' . $date . $random;
}

function formatTanggalIndonesia($date) {
    $bulan = array(
        1 => \'Januari\', 2 => \'Februari\', 3 => \'Maret\', 4 => \'April\',
        5 => \'Mei\', 6 => \'Juni\', 7 => \'Juli\', 8 => \'Agustus\',
        9 => \'September\', 10 => \'Oktober\', 11 => \'November\', 12 => \'Desember\'
    );
    
    $timestamp = strtotime($date);
    $tanggal = date(\'j\', $timestamp);
    $bulan_idx = date(\'n\', $timestamp);
    $tahun = date(\'Y\', $timestamp);
    
    return $tanggal . \' \' . $bulan[$bulan_idx] . \' \' . $tahun;
}

function logActivity($visitor_id, $admin_id, $activity_type, $description) {
    $db = getDB();
    $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
    $user_agent = $_SERVER[\'HTTP_USER_AGENT\'] ?? \'unknown\';
    
    $stmt = $db->prepare("INSERT INTO activity_logs (visitor_id, admin_id, activity_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$visitor_id, $admin_id, $activity_type, $description, $ip, $user_agent]);
}
?>';

            file_put_contents('config/database.php', $config_content);
            
            // Create necessary folders
            $folders = ['uploads', 'qrcodes', 'config'];
            foreach ($folders as $folder) {
                if (!file_exists($folder)) {
                    mkdir($folder, 0755, true);
                }
            }
            
            // Create installation marker
            file_put_contents('config/.installed', date('Y-m-d H:i:s'));
            
            // Clear session
            unset($_SESSION['config']);
            
            $success_message = "Setup berhasil! Sistem buku tamu telah siap digunakan.";
            
        } catch (Exception $e) {
            $error_message = "Error creating config: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Sistem Buku Tamu RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .setup-container {
            max-width: 600px;
            margin: auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .setup-body {
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step.pending {
            background: #e9ecef;
            color: #6c757d;
        }
        .step-line {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            margin-top: 19px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="setup-header">
                <i class="fas fa-hospital fa-3x mb-3"></i>
                <h2>Setup Sistem Buku Tamu</h2>
                <p class="mb-0">RS Pelita Insani</p>
            </div>
            
            <div class="setup-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending' ?>">1</div>
                    <div class="step-line"></div>
                    <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending' ?>">2</div>
                    <div class="step-line"></div>
                    <div class="step <?= $step >= 3 ? 'active' : 'pending' ?>">3</div>
                </div>

                <!-- Alerts -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success_message ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <!-- Step 1: Database Configuration -->
                    <h4><i class="fas fa-database"></i> Konfigurasi Database</h4>
                    <p class="text-muted">Masukkan informasi database MySQL Anda</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Host Database</label>
                            <input type="text" class="form-control" name="db_host" 
                                   value="<?= $_POST['db_host'] ?? 'localhost' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Username Database</label>
                            <input type="text" class="form-control" name="db_user" 
                                   value="<?= $_POST['db_user'] ?? 'root' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Database</label>
                            <input type="password" class="form-control" name="db_pass" 
                                   value="<?= $_POST['db_pass'] ?? '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Database</label>
                            <input type="text" class="form-control" name="db_name" 
                                   value="<?= $_POST['db_name'] ?? 'buku_tamu_rs' ?>" required>
                            <div class="form-text">Database akan dibuat otomatis jika belum ada</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Base URL</label>
                            <input type="url" class="form-control" name="base_url" 
                                   value="<?= $_POST['base_url'] ?? 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' ?>" required>
                            <div class="form-text">URL lengkap ke folder buku-tamu (dengan slash di akhir)</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right"></i> Test Koneksi & Lanjut
                            </button>
                        </div>
                    </form>

                <?php elseif ($step == 2): ?>
                    <!-- Step 2: Create Database Tables -->
                    <h4><i class="fas fa-table"></i> Buat Tabel Database</h4>
                    <p class="text-muted">Membuat tabel dan memasukkan data default</p>
                    
                    <?php if ($success_message): ?>
                        <div class="d-grid">
                            <a href="?step=3" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right"></i> Lanjut ke Step 3
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Yang akan dibuat:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Tabel admin_users (untuk login admin)</li>
                                    <li>Tabel security_staff (daftar petugas security)</li>
                                    <li>Tabel visitors (data pengunjung)</li>
                                    <li>Tabel activity_logs (log aktivitas)</li>
                                    <li>Data default admin dan security staff</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus"></i> Buat Tabel Database
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                <?php elseif ($step == 3): ?>
                    <!-- Step 3: Final Setup -->
                    <h4><i class="fas fa-cog"></i> Finalisasi Setup</h4>
                    <p class="text-muted">Membuat file konfigurasi dan folder yang diperlukan</p>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Setup Berhasil!</h5>
                            <p class="mb-3">Sistem buku tamu telah siap digunakan. Berikut informasi login:</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Admin:</h6>
                                    <ul>
                                        <li><strong>Username:</strong> admin</li>
                                        <li><strong>Password:</strong> admin123</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Security:</h6>
                                    <ul>
                                        <li><strong>Username:</strong> security</li>
                                        <li><strong>Password:</strong> admin123</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-success btn-lg">
                                <i class="fas fa-home"></i> Buka Form Pengunjung
                            </a>
                            <a href="admin/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login Admin
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Yang akan dibuat:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>File config/database.php</li>
                                    <li>Folder uploads/ untuk foto</li>
                                    <li>Folder qrcodes/ untuk QR code</li>
                                    <li>File penanda instalasi</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check"></i> Selesaikan Setup
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                <?php endif; ?>
                
                <hr class="my-4">
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i> 
                        Hapus file setup.php setelah instalasi selesai untuk keamanan
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
