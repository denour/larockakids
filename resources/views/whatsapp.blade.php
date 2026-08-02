<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp Notifications</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        .pattern {
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23e2e8f0' fill-opacity='0.4' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
        .status-indicator {
            transition: all 0.3s ease;
        }
        .status-connected {
            background-color: #10B981;
        }
        .status-disconnected {
            background-color: #EF4444;
        }
        .status-connecting {
            background-color: #F59E0B;
        }
    </style>
</head>
<body class="pattern min-h-screen flex items-center justify-center">
    <div class="bg-white/90 backdrop-blur-sm p-8 rounded-lg shadow-lg max-w-md w-full">
        <div class="text-center">
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-green-500 pulse" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Sistema de Notificaciones WhatsApp</h1>
            <div class="flex items-center justify-center space-x-2 mb-4">
                <div id="status" class="status-indicator w-3 h-3 rounded-full status-disconnected"></div>
                <span id="statusText" class="text-sm text-gray-600">Desconectado</span>
            </div>
            <div class="flex items-center justify-center space-x-2">
                <div class="w-2 h-2 bg-green-500 rounded-full pulse"></div>
                <div class="w-2 h-2 bg-green-500 rounded-full pulse" style="animation-delay: 0.2s"></div>
                <div class="w-2 h-2 bg-green-500 rounded-full pulse" style="animation-delay: 0.4s"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let pusher = null;
            let channel = null;
            let connectionStatus = 'disconnected';

            function updateStatus(status) {
                const statusElement = document.getElementById('status');
                const statusText = document.getElementById('statusText');
                
                if (statusElement && statusText) {
                    connectionStatus = status;
                    statusElement.className = `status-indicator w-3 h-3 rounded-full ${status === 'connected' ? 'status-connected' : status === 'disconnected' ? 'status-disconnected' : 'status-connecting'}`;
                    statusText.textContent = status === 'connected' ? 'Conectado' : status === 'disconnected' ? 'Desconectado' : 'Conectando...';
                }
            }

            function initializePusher() {
                try {
                    pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
                        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                        encrypted: true
                    });

                    pusher.connection.bind('state_change', function(states) {
                        updateStatus(states.current);
                    });

                    channel = pusher.subscribe('whatsapp-notifications');
                    updateStatus('connected');

                    // Debug: log all events
                    channel.bind_global(function(event, data) {
                        console.log('Pusher event received:', event, data);
                    });

                    channel.bind('notification', async function(data) {
                        // Los avisos ya NO se envían desde aquí: los manda el servidor por el
                        // bridge (App\Listeners\SendTutorNotification). Antes esto abría wa.me
                        // con el texto precargado y alguien le daba enviar a mano. Además el
                        // evento dejó de ser ShouldBroadcast, así que este canal ya no recibe
                        // nada. Si volviera el popup, el papá recibiría el mensaje dos veces.
                        console.log('Aviso enviado por el servidor (solo monitoreo):', data);
                    });
                } catch (error) {
                    updateStatus('disconnected');
                    console.error('Error initializing Pusher:', error);
                }
            }

            // Initialize Pusher
            initializePusher();

            // Reconnect every 5 seconds if disconnected
            setInterval(() => {
                if (connectionStatus === 'disconnected') {
                    updateStatus('connecting');
                    initializePusher();
                }
            }, 5000);
        });
    </script>
</body>
</html>
