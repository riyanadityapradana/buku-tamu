<?php
// Generate QR Code on-the-fly
require_once 'config/database.php';
require_once 'lib/qr_generator.php';

// Get visitor code from parameter
$kode_kunjungan = $_GET['code'] ?? '';

if (empty($kode_kunjungan)) {
    // Show error image
    header("Content-Type: text/plain");
    http_response_code(400);
    echo "Error: Kode kunjungan tidak valid";
    exit;
}

// Verify code exists in database
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM visitors WHERE kode_kunjungan = ?");
    $stmt->execute([$kode_kunjungan]);
    $visitor = $stmt->fetch();
    
    if (!$visitor) {
        header("Content-Type: text/plain");
        http_response_code(404);
        echo "Error: Kode kunjungan tidak ditemukan";
        exit;
    }
    
} catch (Exception $e) {
    header("Content-Type: text/plain");
    http_response_code(500);
    echo "Error: Database error";
    exit;
}

// Generate checkout URL
$checkout_url = BASE_URL . 'checkout.php?code=' . $kode_kunjungan;

// Generate and display QR code
try {
    $qr_generator = new SimpleQRGenerator();
    $qr_generator->displayQR($checkout_url, 200);
    
} catch (Exception $e) {
    // Fallback to text display
    header("Content-Type: text/plain");
    echo "QR Code untuk: " . $checkout_url;
}
?>
