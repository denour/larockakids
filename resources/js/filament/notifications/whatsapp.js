import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Variable global para mantener la referencia de la pestaña
let whatsappTab = null;

window.Echo.channel('whatsapp-notifications')
    .listen('whatsapp.notification', (data) => {
        const { message, phoneNumber } = data;
        const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
        
        // Abrir WhatsApp en una nueva pestaña
        window.open(whatsappUrl, '_blank');
    }); 