import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Los avisos ya NO se envían desde el navegador: los manda el servidor por el bridge
// (App\Listeners\SendTutorNotification). Además el evento dejó de ser ShouldBroadcast,
// así que este canal YA NO RECIBE NADA y este listener nunca se dispara. Se conserva
// por si algún día se quiere volver a difundir el aviso para monitoreo en vivo; lo que
// no debe volver es abrir wa.me desde aquí, o el papá recibiría el mensaje dos veces.
window.Echo.channel('whatsapp-notifications')
    .listen('whatsapp.notification', (data) => {
        console.log('Aviso enviado por el servidor (solo monitoreo):', data);
    });
