<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#dc2626">
    <title>Check-out - Salida</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #111827;
            color: white;
            overflow: hidden;
        }

        .container {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            padding: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .scanner-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 16px;
            position: relative;
        }

        #reader {
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            overflow: hidden;
        }

        #reader video {
            border-radius: 16px;
        }

        .result-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 24px;
            z-index: 100;
        }

        .result-overlay.show {
            display: flex;
        }

        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 24px;
        }

        .result-icon.success {
            background: #16a34a;
        }

        .result-icon.error {
            background: #dc2626;
        }

        .result-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }

        .result-message {
            font-size: 20px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 12px;
        }

        .result-details {
            font-size: 16px;
            text-align: center;
            opacity: 0.8;
            margin-bottom: 32px;
        }

        .btn {
            width: 100%;
            max-width: 300px;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            touch-action: manipulation;
            min-height: 56px;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
        }

        .btn-primary:active {
            background: #b91c1c;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: #dc2626;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .permission-error {
            text-align: center;
            padding: 24px;
        }

        .permission-error h2 {
            font-size: 20px;
            margin-bottom: 12px;
        }

        .permission-error p {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 20px;
        }

        .mode-switch {
            padding: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .mode-switch a {
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
        }

        .mode-switch a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SALIDA</h1>
            <p>Escanea el codigo QR del nino</p>
        </div>

        <div class="scanner-container">
            <div id="reader"></div>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Procesando...</p>
            </div>
            <div class="permission-error" id="permission-error" style="display: none;">
                <h2>Permiso de camara requerido</h2>
                <p>Por favor permite el acceso a la camara para escanear codigos QR.</p>
                <button class="btn btn-primary" onclick="startScanner()">Reintentar</button>
            </div>
        </div>

        <div class="mode-switch">
            <a href="{{ route('scanner.check-in') }}">Cambiar a modo ENTRADA</a>
        </div>
    </div>

    <div class="result-overlay" id="result-overlay">
        <div class="result-icon" id="result-icon">
            <svg id="icon-success" viewBox="0 0 24 24" style="display: none;">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <svg id="icon-error" viewBox="0 0 24 24" style="display: none;">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </div>
        <div class="result-message" id="result-message"></div>
        <div class="result-details" id="result-details"></div>
        <button class="btn btn-primary" onclick="scanAgain()">Escanear Otro</button>
    </div>

    <script>
        let html5QrCode = null;
        let isProcessing = false;

        function startScanner() {
            document.getElementById('permission-error').style.display = 'none';

            html5QrCode = new Html5Qrcode("reader");

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanFailure
            ).catch(err => {
                console.error("Camera error:", err);
                document.getElementById('permission-error').style.display = 'block';
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return;
            isProcessing = true;

            html5QrCode.pause();
            document.getElementById('loading').classList.add('show');

            fetch('{{ route("scanner.check-out.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: decodedText })
            })
            .then(response => response.json())
            .then(data => {
                showResult(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showResult({
                    success: false,
                    message: 'Error de conexion',
                    action: null,
                    kid_name: null
                });
            })
            .finally(() => {
                document.getElementById('loading').classList.remove('show');
            });
        }

        function onScanFailure(error) {
            // Ignore scan failures (no QR detected in frame)
        }

        function showResult(data) {
            const overlay = document.getElementById('result-overlay');
            const icon = document.getElementById('result-icon');
            const message = document.getElementById('result-message');
            const details = document.getElementById('result-details');

            // Reset icons
            document.getElementById('icon-success').style.display = 'none';
            document.getElementById('icon-error').style.display = 'none';

            // Remove all classes
            icon.classList.remove('success', 'error');

            if (data.success) {
                icon.classList.add('success');
                document.getElementById('icon-success').style.display = 'block';
            } else {
                icon.classList.add('error');
                document.getElementById('icon-error').style.display = 'block';
            }

            message.textContent = data.message;
            details.textContent = data.kid_name ? data.kid_name : '';

            overlay.classList.add('show');

            // Play sound feedback
            if (data.success) {
                playSuccessSound();
            } else {
                playErrorSound();
            }
        }

        function scanAgain() {
            document.getElementById('result-overlay').classList.remove('show');
            isProcessing = false;
            html5QrCode.resume();
        }

        function playSuccessSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();
                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gain.gain.value = 0.3;
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.15);
            } catch (e) {}
        }

        function playErrorSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();
                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.frequency.value = 300;
                oscillator.type = 'sine';
                gain.gain.value = 0.3;
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', startScanner);
    </script>
</body>
</html>
