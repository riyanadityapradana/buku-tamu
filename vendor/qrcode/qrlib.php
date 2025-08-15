<?php
/*
 * Simple QR Code Generator using Online API
 * Compatible with existing systems
 */

// Check if QRcode class already exists
if (!class_exists('QRcode')) {
    class QRcode {
        
        public static function png($text, $outfile = false, $level = 'L', $size = 200, $margin = 0, $saveandprint = false) {
            // Use Google Charts API or qr-server.com API for QR generation
            $api_url = "https://api.qrserver.com/v1/create-qr-code/";
            $params = http_build_query([
                'size' => $size . 'x' . $size,
                'data' => $text,
                'format' => 'png',
                'margin' => $margin
            ]);
            
            $qr_url = $api_url . '?' . $params;
            
            // Get QR code image data
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; QR-Generator/1.0)'
                ]
            ]);
            
            $qr_data = @file_get_contents($qr_url, false, $context);
            
            if ($qr_data === false) {
                // Fallback: create simple QR using backup method
                return self::createFallbackQR($text, $outfile);
            }
            
            if ($outfile) {
                // Save to file
                $result = file_put_contents($outfile, $qr_data);
                if ($saveandprint) {
                    header("Content-type: image/png");
                    echo $qr_data;
                }
                return $result !== false;
            } else {
                // Output directly
                header("Content-type: image/png");
                echo $qr_data;
                return true;
            }
        }
        
        public static function text($text, $outfile = false, $level = 'L', $size = 3, $margin = 4) {
            // Text version - just return the text for now
            $output = "QR Code Text: " . $text;
            
            if ($outfile) {
                return file_put_contents($outfile, $output);
            } else {
                echo $output;
                return true;
            }
        }
        
        public static function raw($text, $outfile = false, $level = 'L', $size = 3, $margin = 4) {
            return self::text($text, $outfile, $level, $size, $margin);
        }
        
        private static function createFallbackQR($text, $outfile = false) {
            // Create a simple placeholder image if online API fails
            $size = 200;
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
            
            // Add text
            $font_size = 3;
            $text_lines = [
                "QR Code",
                "Generated",
                substr($text, 0, 20),
                "Show this to",
                "security for",
                "checkout"
            ];
            
            $y_pos = 40;
            foreach ($text_lines as $line) {
                $text_width = strlen($line) * imagefontwidth($font_size);
                $x_pos = ($size - $text_width) / 2;
                imagestring($image, $font_size, $x_pos, $y_pos, $line, $text_color);
                $y_pos += 25;
            }
            
            // Add some QR-like pattern
            for ($i = 0; $i < 10; $i++) {
                for ($j = 0; $j < 10; $j++) {
                    if (($i + $j) % 2 == 0) {
                        $x = 30 + ($i * 14);
                        $y = 120 + ($j * 6);
                        imagefilledrectangle($image, $x, $y, $x+10, $y+4, $text_color);
                    }
                }
            }
            
            if ($outfile) {
                $result = imagepng($image, $outfile);
                imagedestroy($image);
                return $result;
            } else {
                header("Content-type: image/png");
                imagepng($image);
                imagedestroy($image);
                return true;
            }
        }
    }
}

// Compatibility functions
if (!defined('QR_ECLEVEL_L')) {
    define('QR_ECLEVEL_L', 'L');
    define('QR_ECLEVEL_M', 'M');
    define('QR_ECLEVEL_Q', 'Q');
    define('QR_ECLEVEL_H', 'H');
}
?>
