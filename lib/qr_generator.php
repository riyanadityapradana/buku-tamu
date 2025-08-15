<?php
/**
 * Simple QR Code Generator
 * Menggunakan Google Charts API sebagai fallback
 */

class SimpleQRGenerator {
    
    public static function generateQR($text, $filepath = null, $size = 200) {
        // Method 1: Try using Google Charts API (reliable)
        $google_url = "https://chart.googleapis.com/chart?";
        $params = http_build_query([
            'chs' => $size . 'x' . $size,
            'cht' => 'qr',
            'chl' => $text,
            'choe' => 'UTF-8'
        ]);
        
        $qr_url = $google_url . $params;
        
        // Get QR code data
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (compatible; QR-Generator/1.0)',
                'method' => 'GET'
            ]
        ]);
        
        $qr_data = @file_get_contents($qr_url, false, $context);
        
        if ($qr_data === false) {
            // Method 2: Try alternative API
            $qr_data = self::tryAlternativeAPI($text, $size);
        }
        
        if ($qr_data === false) {
            // Method 3: Create fallback image
            $qr_data = self::createFallbackImage($text, $size);
        }
        
        if ($filepath) {
            // Save to file
            $result = file_put_contents($filepath, $qr_data);
            return $result !== false;
        } else {
            // Return data for direct output
            return $qr_data;
        }
    }
    
    private static function tryAlternativeAPI($text, $size) {
        // Try qr-server.com API
        $api_url = "https://api.qrserver.com/v1/create-qr-code/";
        $params = http_build_query([
            'size' => $size . 'x' . $size,
            'data' => $text,
            'format' => 'png'
        ]);
        
        $qr_url = $api_url . '?' . $params;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; QR-Generator/1.0)'
            ]
        ]);
        
        return @file_get_contents($qr_url, false, $context);
    }
    
    private static function createFallbackImage($text, $size) {
        // Create fallback image if all APIs fail
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $image = imagecreate($size, $size);
        
        // Colors
        $bg_color = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $border_color = imagecolorallocate($image, 100, 100, 100);
        
        // Fill background
        imagefill($image, 0, 0, $bg_color);
        
        // Draw border
        imagerectangle($image, 0, 0, $size-1, $size-1, $border_color);
        imagerectangle($image, 10, 10, $size-11, $size-11, $border_color);
        
        // Add text information
        $font_size = 4;
        $lines = [
            "QR CODE",
            "",
            "Kode: " . substr($text, -10),
            "",
            "Tunjukkan ke",
            "petugas security",
            "untuk checkout",
            "",
            "RS Pelita Insani"
        ];
        
        $y_pos = 20;
        foreach ($lines as $line) {
            if (empty($line)) {
                $y_pos += 10;
                continue;
            }
            
            $text_width = strlen($line) * imagefontwidth($font_size);
            $x_pos = ($size - $text_width) / 2;
            imagestring($image, $font_size, $x_pos, $y_pos, $line, $text_color);
            $y_pos += 20;
        }
        
        // Add QR-like pattern
        $pattern_size = 8;
        $start_x = ($size - ($pattern_size * 15)) / 2;
        $start_y = $y_pos + 10;
        
        for ($i = 0; $i < $pattern_size; $i++) {
            for ($j = 0; $j < $pattern_size; $j++) {
                // Create pseudo-random pattern based on text
                $hash = md5($text . $i . $j);
                if (hexdec(substr($hash, 0, 1)) % 2 == 0) {
                    $x = $start_x + ($i * 15);
                    $y = $start_y + ($j * 8);
                    imagefilledrectangle($image, $x, $y, $x+12, $y+6, $text_color);
                }
            }
        }
        
        // Capture image data
        ob_start();
        imagepng($image);
        $image_data = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return $image_data;
    }
    
    public static function displayQR($text, $size = 200) {
        $qr_data = self::generateQR($text, null, $size);
        
        if ($qr_data) {
            header("Content-Type: image/png");
            header("Cache-Control: public, max-age=3600");
            echo $qr_data;
        } else {
            // Show error image
            header("Content-Type: text/plain");
            echo "Error generating QR code";
        }
    }
}

// Compatibility wrapper for existing code
if (!class_exists('QRcode')) {
    class QRcode {
        public static function png($text, $outfile = false, $level = 'L', $size = 200, $margin = 0, $saveandprint = false) {
            $generator = new SimpleQRGenerator();
            
            if ($outfile) {
                $result = $generator->generateQR($text, $outfile, $size);
                if ($saveandprint && $result) {
                    $data = file_get_contents($outfile);
                    header("Content-Type: image/png");
                    echo $data;
                }
                return $result;
            } else {
                $generator->displayQR($text, $size);
                return true;
            }
        }
    }
}
?>
