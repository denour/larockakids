<?php

namespace App\Services;

use App\Events\WhatsAppNotification;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\TutorMessage;
use Illuminate\Support\Carbon;

class TutorMessageService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Genera URL de WhatsApp Web para un mensaje
     */
    public function generateWhatsAppUrl(string $label, Contact $contact, Kid $kid, array $additionalData = []): string
    {
        // Obtener el mensaje base
        $message = TutorMessage::findByLabel($label);

        if (! $message) {
            throw new \Exception("No se encontró un mensaje para el label: {$label}");
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

        // Limpiar el número de teléfono (sin + ni espacios)
        $phoneNumber = str_replace('+', '', $contact->full_phone);

        // Crear URL de WhatsApp Web
        return "https://wa.me/{$phoneNumber}?text=" . urlencode($formattedMessage);
    }

    /**
     * Envía un mensaje a WhatsApp basado en el tipo de mensaje (usando wa.me)
     */
    public function sendMessage(string $label, Contact $contact, Kid $kid, array $additionalData = []): void
    {
        // Obtener el mensaje base
        $message = TutorMessage::findByLabel($label);

        if (! $message) {
            throw new \Exception("No se encontró un mensaje para el label: {$label}");
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

        // Limpiar el número de teléfono (sin + ni espacios)
        $phoneNumber = str_replace('+', '', $contact->full_phone);

        // Crear URL de WhatsApp Web (forma antigua)
        $whatsappUrl = "https://wa.me/{$phoneNumber}?text=" . urlencode($formattedMessage);

        // Log del enlace generado (en lugar de enviar por API)
        \Illuminate\Support\Facades\Log::info('WhatsApp URL generada', [
            'phone' => $phoneNumber,
            'kid' => $kid->full_name,
            'label' => $label,
            'url' => $whatsappUrl,
            'message' => $formattedMessage
        ]);

        // En lugar de enviar por API, retornamos la URL o la usamos de otra forma
        // Por ahora solo logueamos para debugging
    }

    /**
     * Genera URL de WhatsApp para mensaje de bienvenida
     */
    public function getWelcomeMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('welcome', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de entrada
     */
    public function getEntryMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('entry', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de baño
     */
    public function getBathroomMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('bathroom', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de pañal
     */
    public function getDiaperMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('diaper', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de niño inconsolable
     */
    public function getUnconsolableMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('unconsolable', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de niño enfermo
     */
    public function getSickMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('sick', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de niño recuperado
     */
    public function getRecoveredMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('recovered', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de salida
     */
    public function getExitMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('exit', $contact, $kid);
    }

    /**
     * Genera URL de WhatsApp para mensaje de asistencia
     */
    public function getAssistanceMessageUrl(Contact $contact, Kid $kid): string
    {
        return $this->generateWhatsAppUrl('assistance', $contact, $kid);
    }

    // Métodos legacy para compatibilidad (ahora solo generan URLs y loguean)
    public function sendWelcomeMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('welcome', $contact, $kid);
    }

    public function sendEntryMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('entry', $contact, $kid);
    }

    public function sendBathroomMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('bathroom', $contact, $kid);
    }

    public function sendDiaperMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('diaper', $contact, $kid);
    }

    public function sendUnconsolableMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('unconsolable', $contact, $kid);
    }

    public function sendSickMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('sick', $contact, $kid);
    }

    public function sendRecoveredMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('recovered', $contact, $kid);
    }

    public function sendExitMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('exit', $contact, $kid);
    }

    public function sendAssistanceMessage(Contact $contact, Kid $kid): void
    {
        $this->sendMessage('assistance', $contact, $kid);
    }
}
