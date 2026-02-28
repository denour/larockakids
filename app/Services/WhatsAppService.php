<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $token;

    protected string $phoneNumberId;

    protected string $businessAccountId;

    protected string $apiVersion;

    protected string $baseUrl;

    public function __construct()
    {
        $this->token = config('whatsapp.token') ?? '';
        $this->phoneNumberId = config('whatsapp.phone_number_id') ?? '';
        $this->businessAccountId = config('whatsapp.business_account_id') ?? '';
        $this->apiVersion = config('whatsapp.api_version') ?? 'v17.0';
        $this->baseUrl = config('whatsapp.base_url') ?? 'https://graph.facebook.com';
    }

    /**
     * Envía un mensaje de texto a un número de WhatsApp.
     */
    public function sendTextMessage(string $to, string $message): array
    {
        return $this->sendRequest("/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ]);
    }

    /**
     * Envía un mensaje de imagen a un número de WhatsApp.
     */
    public function sendImageMessage(string $to, string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
            ],
        ];

        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        return $this->sendRequest("/{$this->apiVersion}/{$this->phoneNumberId}/messages", $payload);
    }

    /**
     * Envía un mensaje de documento a un número de WhatsApp.
     */
    public function sendDocumentMessage(string $to, string $documentUrl, ?string $filename = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => [
                'link' => $documentUrl,
            ],
        ];

        if ($filename) {
            $payload['document']['filename'] = $filename;
        }

        return $this->sendRequest("/{$this->apiVersion}/{$this->phoneNumberId}/messages", $payload);
    }

    /**
     * Envía un mensaje de plantilla a un número de WhatsApp.
     */
    public function sendTemplateMessage(string $to, string $templateName, array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'es',
                ],
            ],
        ];

        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->sendRequest("/{$this->apiVersion}/{$this->phoneNumberId}/messages", $payload);
    }

    /**
     * Verifica el estado de un mensaje enviado.
     */
    public function getMessageStatus(string $messageId): array
    {
        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/{$this->apiVersion}/{$messageId}");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error al verificar estado del mensaje de WhatsApp: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía una petición POST a la API de WhatsApp.
     */
    protected function sendRequest(string $endpoint, array $payload): array
    {
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}{$endpoint}", $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje de WhatsApp: '.$e->getMessage());
            throw $e;
        }
    }
}
