<?php
session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error_message = '';

if ($_POST) {
    try {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            throw new Exception("Username dan password harus diisi!");
        }
        
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Username atau password salah!");
        }
        
        // Verify password (in production, use password_verify with hashed passwords)
        if ($password !== 'admin123') { // Simple password for demo
            throw new Exception("Username atau password salah!");
        }
        
        // Set session
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_nama'] = $user['nama_lengkap'];
        $_SESSION['admin_role'] = $user['role'];
        
        header("Location: dashboard.php");
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
    <title>Login Admin - Buku Tamu RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 25px;
            font-weight: 600;
        }
        .demo-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .demo-info h6 {
            color: #1976d2;
            margin-bottom: 0.5rem;
        }
        .demo-info small {
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-hospital fa-3x text-primary mb-3"></i>
                <h3>Admin Panel</h3>
                <p class="text-muted mb-0">RS Pelita Insani</p>
                <small class="text-muted">Sistem Buku Tamu Digital</small>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" 
                               placeholder="Masukkan username" required autofocus
                               value="<?= $_POST['username'] ?? '' ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" 
                               placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-login text-white">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <!-- Demo Information -->
            <div class="demo-info">
                <h6><i class="fas fa-info-circle"></i> Informasi Demo</h6>
                <small>
                    <strong>Username:</strong> admin<br>
                    <strong>Password:</strong> admin123<br>
                    <em>Atau gunakan username "security" dengan password yang sama</em>
                </small>
            </div>

            <div class="text-center mt-3">
                <a href="../index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
