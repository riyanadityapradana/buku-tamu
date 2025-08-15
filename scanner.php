<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - RS Pelita Insani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .scanner-container {
            max-width: 500px;
            margin: 20px auto;
            text-align: center;
        }
        .video-container {
            position: relative;
            margin: 20px 0;
            border-radius: 15px;
            overflow: hidden;
            background: #000;
        }
        #video {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 3px solid #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255,255,255,0.3);
        }
        .result-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 20px 0;
        }
        .btn-scanner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <div class="container">
        <div class="scanner-container">
            <div class="result-container">
                <h3><i class="fas fa-qrcode text-primary"></i> QR Code Scanner</h3>
                <p class="text-muted">Arahkan kamera ke QR code untuk checkout</p>
                
                <div class="video-container">
                    <div id="video"></div>
                    <div class="scanner-overlay"></div>
                </div>
                
                <div id="result" class="mt-3"></div>
                
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button onclick="startScanner()" class="btn btn-scanner text-white" id="startBtn">
                        <i class="fas fa-play"></i> Mulai Scan
                    </button>
                    <button onclick="stopScanner()" class="btn btn-danger" id="stopBtn" style="display: none;">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                    <a href="checkout.php" class="btn btn-outline-secondary">
                        <i class="fas fa-keyboard"></i> Input Manual
                    </a>
                </div>
                
                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Pastikan QR code terlihat jelas dalam kotak scanner
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>

        let html5Qrcode;
        let isScanning = false;

        function startScanner() {
            if (isScanning) return;
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display = 'inline-block';
            document.getElementById('result').innerHTML = '<div class="alert alert-info"><i class="fas fa-camera"></i> Scanner aktif, arahkan kamera ke QR code...</div>';
            html5Qrcode = new Html5Qrcode("video");
            html5Qrcode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 200, height: 200 }
                },
                function(decodedText, decodedResult) {
                    // QR code detected
                    stopScanner();
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> QR Code berhasil dibaca!<br>
                            <strong>Data:</strong> ${decodedText}
                        </div>
                        <div class="text-center">
                            <button onclick="processQRCode('${decodedText}')" class="btn btn-primary">
                                <i class="fas fa-arrow-right"></i> Proses Checkout
                            </button>
                        </div>
                    `;
                },
                function(errorMessage) {
                    // QR code scan error or not found
                    // console.warn(errorMessage);
                }
            ).catch(function(err) {
                document.getElementById('result').innerHTML = `<div class='alert alert-danger'>Gagal mengakses kamera: ${err}</div>`;
            });
            isScanning = true;
        }

        function stopScanner() {
            if (!isScanning) return;
            if (html5Qrcode) {
                html5Qrcode.stop().then(function() {
                    html5Qrcode.clear();
                }).catch(function(err) {
                    // console.error('Stop error:', err);
                });
            }
            document.getElementById('startBtn').style.display = 'inline-block';
            document.getElementById('stopBtn').style.display = 'none';
            document.getElementById('result').innerHTML = '<div class="alert alert-secondary"><i class="fas fa-pause"></i> Scanner berhenti</div>';
            isScanning = false;
        }

        function processQRCode(qrData) {
            // Extract kode kunjungan from QR data
            let kodeKunjungan = '';
            
            if (qrData.includes('checkout.php?code=')) {
                // Extract code from URL
                const urlParams = new URLSearchParams(qrData.split('?')[1]);
                kodeKunjungan = urlParams.get('code');
            } else {
                // Assume the QR data is the code itself
                kodeKunjungan = qrData;
            }
            
            if (kodeKunjungan) {
                // Redirect to checkout page with the code
                window.location.href = `checkout.php?code=${encodeURIComponent(kodeKunjungan)}`;
            } else {
                document.getElementById('result').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> QR Code tidak valid!<br>
                        Data yang dibaca: ${qrData}
                    </div>
                `;
            }
        }

    // Tidak perlu auto-check kamera, html5-qrcode sudah handle permission dan preview

        // Handle page visibility change to stop scanner when tab is hidden
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && isScanning) {
                stopScanner();
            }
        });
    </script>
</body>
</html>
