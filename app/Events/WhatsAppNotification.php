<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Aviso a un tutor. Lo entrega App\Listeners\SendTutorNotification por el bridge.
 *
 * YA NO es ShouldBroadcast: mientras lo fue, cada aviso viajaba también a Pusher, y
 * una falla de Pusher lanzaba BroadcastException DENTRO del pase de asistencia — es
 * decir, un problema de un servicio externo podía tumbar el registro de entradas y
 * salidas. Desde que el envío ocurre en el servidor, Pusher no cumplía ninguna función:
 * solo alimentaba un console.log de monitoreo en la vista /whatsapp.
 *
 * `broadcastOn()` y `broadcastAs()` se conservan porque describen el canal histórico y
 * hay pruebas que los verifican; no se usan para entregar nada.
 */
class WhatsAppNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public string $phoneNumber;

    public function __construct(string $message, string $phoneNumber)
    {
        $this->message = $message;
        $this->phoneNumber = $phoneNumber;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('whatsapp-notifications');
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }
} 