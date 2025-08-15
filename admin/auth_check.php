<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Check session timeout (1 hour)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check admin role
function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'admin';
}

// Function to check security role
function isSecurity() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'security';
}

// Function to get current admin info
function getCurrentAdmin() {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? '',
        'nama' => $_SESSION['admin_nama'] ?? '',
        'role' => $_SESSION['admin_role'] ?? ''
    ];
}
?>
