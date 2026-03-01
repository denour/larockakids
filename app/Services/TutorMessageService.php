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

        // Eliminar etiquetas HTML y decodificar entidades
        $formattedMessage = strip_tags($formattedMessage);
        $formattedMessage = html_entity_decode($formattedMessage, ENT_QUOTES, 'UTF-8');
        $formattedMessage = trim($formattedMessage);

        // Limpiar el número de teléfono (incluye código de país)
        $phoneNumber = preg_replace('/[^0-9]/', '', $contact->full_phone);

        // Disparar el evento de WhatsApp
        broadcast(new WhatsAppNotification(
            message: $formattedMessage,
            phoneNumber: $phoneNumber
        ));
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

    /**
     * Genera una URL de WhatsApp para un mensaje específico
     */
    public function generateWhatsAppUrl(string $label, Contact $contact, Kid $kid, array $additionalData = []): string
    {
        $message = TutorMessage::findByLabel($label);

        if (! $message) {
            Log::warning("No se encontró un mensaje activo para el label: {$label}");

            return $this->buildWhatsAppUrl($contact->full_phone, '');
        }

        $values = [
            '[tutor]' => $contact->full_name,
            '[nino]' => $kid->full_name,
            '[fecha]' => Carbon::now()->format('d/m/Y h:i A'),
            '[comentario]' => $additionalData['comment'] ?? '',
        ];

        $formattedMessage = $message->replaceTags($values);

        return $this->buildWhatsAppUrl($contact->full_phone, $formattedMessage);
    }

    /**
     * Construye la URL de WhatsApp
     */
    protected function buildWhatsAppUrl(string $phone, string $message): string
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phone);

        // Eliminar etiquetas HTML y decodificar entidades
        $cleanMessage = strip_tags($message);
        $cleanMessage = html_entity_decode($cleanMessage, ENT_QUOTES, 'UTF-8');
        $cleanMessage = trim($cleanMessage);

        return 'https://wa.me/'.$phoneNumber.'?text='.urlencode($cleanMessage);
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
}
