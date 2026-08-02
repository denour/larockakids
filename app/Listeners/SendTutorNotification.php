<?php

namespace App\Listeners;

use App\Events\WhatsAppNotification;
use App\Services\WhatsAppBridgeService;

/**
 * Entrega los avisos a tutores por el bridge de Evolution.
 *
 * Antes la entrega la hacía la vista `whatsapp.blade.php`: escuchaba el evento
 * por Pusher, abría `wa.me/...` con el texto precargado y ALGUIEN tenía que
 * darle enviar a mano. Si esa pestaña estaba cerrada, el aviso no salía y nadie
 * se enteraba. Ahora sale del servidor, sin intervención.
 */
class SendTutorNotification
{
    public function __construct(private WhatsAppBridgeService $bridge) {}

    public function handle(WhatsAppNotification $event): void
    {
        $this->bridge->sendText($event->phoneNumber, $event->message);
    }
}
