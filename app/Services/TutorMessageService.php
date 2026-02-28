<?php

namespace App\Services;

use App\Events\WhatsAppNotification;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\TutorMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TutorMessageService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Envía un mensaje a WhatsApp basado en el tipo de mensaje
     */
    public function sendMessage(string $label, Contact $contact, Kid $kid, array $additionalData = []): void
    {
        // Obtener el mensaje base
        $message = TutorMessage::findByLabel($label);

        if (! $message) {
            Log::warning("No se encontró un mensaje activo para el label: {$label}");

            return;
        }

        // Preparar los valores para reemplazar
        $values = [
            '[tutor]' => $contact->full_name,
            '[nino]' => $kid->full_name,
            '[fecha]' => Carbon::now()->format('d/m/Y h:i A'),
            '[comentario]' => $additionalData['comment'] ?? '',
        ];

        // Reemplazar los tags en el mensaje
        $formattedMessage = $message->replaceTags($values);

        // Limpiar el número de teléfono (incluye código de país)
        $phoneNumber = preg_replace('/[^0-9]/', '', $contact->full_phone);

        // Disparar el evento de WhatsApp
        broadcast(new WhatsAppNotification(
            message: $formattedMessage,
            phoneNumber: $phoneNumber
        ))->toOthers();
    }

    /**
     * Envía un mensaje de bienvenida
     */
    public function sendWelcomeMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('welcome', $contact, $kid);
    }

    /**
     * Envía un mensaje de entrada
     */
    public function sendEntryMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('entry', $contact, $kid);
    }

    /**
     * Envía un mensaje de baño
     */
    public function sendBathroomMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('bathroom', $contact, $kid);
    }

    /**
     * Envía un mensaje de pañal
     */
    public function sendDiaperMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('diaper', $contact, $kid);
    }

    /**
     * Envía un mensaje de niño inconsolable
     */
    public function sendUnconsolableMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('unconsolable', $contact, $kid);
    }

    /**
     * Envía un mensaje de niño enfermo
     */
    public function sendSickMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('sick', $contact, $kid);
    }

    /**
     * Envía un mensaje de niño recuperado
     */
    public function sendRecoveredMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('recovered', $contact, $kid);
    }

    /**
     * Envía un mensaje de salida
     */
    public function sendExitMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('exit', $contact, $kid);
    }

    /**
     * Envía un mensaje de asistencia
     */
    public function sendAssistanceMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('assistance', $contact, $kid);
    }
}
