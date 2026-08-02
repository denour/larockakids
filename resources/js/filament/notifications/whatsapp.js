import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Los avisos ya NO se envían desde el navegador: los manda el servidor por el
// bridge (App\Listeners\SendTutorNotification). Este listener queda solo para
// monitoreo; si volviera a abrir wa.me, el papá recibiría el mensaje dos veces.
window.Echo.channel('whatsapp-notifications')
    .listen('whatsapp.notification', (data) => {
        console.log('Aviso enviado por el servidor (solo monitoreo):', data);
    });
