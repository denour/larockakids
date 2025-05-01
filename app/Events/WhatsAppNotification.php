<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WhatsAppNotification implements ShouldBroadcast
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