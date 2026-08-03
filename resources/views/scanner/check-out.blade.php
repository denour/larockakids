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

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px;
            padding-top: calc(16px + env(safe-area-inset-top));
            pointer-events: none;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 8px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
            pointer-events: auto;
        }

        .toast.hiding {
            animation: slideOut 0.3s ease-in forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(-100%);
                opacity: 0;
            }
        }

        .toast.success {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        }

        .toast.error {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        .toast-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
        }

        .toast-icon svg {
            width: 100%;
            height: 100%;
            fill: white;
        }

        .toast-content {
            flex: 1;
            min-width: 0;
        }

        .toast-message {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .toast-name {
            font-size: 18px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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

        .btn {
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

        .mode-switch {
            padding: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .mode-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #374151;
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            transition: background 0.2s;
        }

        .mode-switch-btn:hover {
            background: #4b5563;
        }

        .mode-switch-btn svg {
            width: 20px;
            height: 20px;
        }

        .toast-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .toast-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .toast-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .toast-btn svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }

        .toast.warning {
            background: linear-gradient(135deg, #ca8a04 0%, #a16207 100%);
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
            <a href="{{ route('scanner.check-in') }}" class="mode-switch-btn">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
                Entrada
            </a>
            <a href="{{ route('scanner.assistance') }}" class="mode-switch-btn" style="background: #ca8a04;">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
                Asistencia
            </a>
        </div>
    </div>

    <div class="toast-container" id="toast-container"></div>

    <script>
        let html5QrCode = null;
        let isProcessing = false;
        let scanCooldown = false;

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
            if (isProcessing || scanCooldown) return;
            isProcessing = true;

            document.getElementById('loading').classList.add('show');

            fetch('{{ route("scanner.check-out.process") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: decodedText })
            })
            .then(response => response.json())
            .then(data => {
                showToast(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast({
                    success: false,
                    message: 'Error de conexion',
                    action: null,
                    kid_name: null
                });
            })
            .finally(() => {
                document.getElementById('loading').classList.remove('show');
                isProcessing = false;

                // Cooldown to prevent scanning the same code multiple times
                scanCooldown = true;
                setTimeout(() => {
                    scanCooldown = false;
                }, 2000);
            });
        }

        function onScanFailure(error) {
            // Ignore scan failures (no QR detected in frame)
        }

        function showToast(data) {
            const container = document.getElementById('toast-container');

            const toast = document.createElement('div');
            toast.className = 'toast';

            // Set toast type
            if (data.success) {
                toast.classList.add('success');
            } else {
                if (data.no_active_attendance) {
                    toast.classList.add('warning');
                } else {
                    toast.classList.add('error');
                }
            }

            // Get icon SVG
            let iconSvg = '';
            if (data.success) {
                iconSvg = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
            } else {
                if (data.no_active_attendance) {
                    iconSvg = '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
                } else {
                    iconSvg = '<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
                }
            }

            // Build actions HTML
            let actionsHtml = '';
            if (data.no_active_attendance) {
                actionsHtml += `<a href="{{ route('scanner.check-in') }}" class="toast-btn">
                    <svg viewBox="0 0 24 24"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
                    Ir a Entrada
                </a>`;
            }

            toast.innerHTML = `
                <div class="toast-icon">${iconSvg}</div>
                <div class="toast-content">
                    <div class="toast-message">${data.message}</div>
                    ${data.kid_name ? `<div class="toast-name">${data.kid_name}</div>` : ''}
                    ${actionsHtml ? `<div class="toast-actions">${actionsHtml}</div>` : ''}
                </div>
            `;

            container.appendChild(toast);

            // Play sound feedback
            if (data.success) {
                playSuccessSound();
            } else {
                if (data.no_active_attendance) {
                    playSuccessSound(); // Use different sound for "no entry" warning
                } else {
                    playErrorSound();
                }
            }

            // Auto-remove after 6 seconds (longer to allow clicking buttons)
            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 6000);
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
