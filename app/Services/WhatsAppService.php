<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $client;
    protected $token;
    protected $phoneNumberId;
    protected $businessAccountId;
    protected $apiVersion;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = config('whatsapp.token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->businessAccountId = config('whatsapp.business_account_id');
        $this->apiVersion = config('whatsapp.api_version');
        $this->baseUrl = config('whatsapp.base_url');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Envía un mensaje de texto a un número de WhatsApp.
     *
     * @param string $to Número de teléfono del destinatario
     * @param string $message Mensaje a enviar
     * @return array
     */
    public function sendTextMessage(string $to, string $message): array
    {
        try {
            $response = $this->client->post("/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje de WhatsApp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía un mensaje de imagen a un número de WhatsApp.
     *
     * @param string $to Número de teléfono del destinatario
     * @param string $imageUrl URL de la imagen
     * @param string|null $caption Pie de foto (opcional)
     * @return array
     */
    public function sendImageMessage(string $to, string $imageUrl, ?string $caption = null): array
    {
        try {
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

            $response = $this->client->post("/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error al enviar imagen por WhatsApp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía un mensaje de documento a un número de WhatsApp.
     *
     * @param string $to Número de teléfono del destinatario
     * @param string $documentUrl URL del documento
     * @param string|null $filename Nombre del archivo (opcional)
     * @return array
     */
    public function sendDocumentMessage(string $to, string $documentUrl, ?string $filename = null): array
    {
        try {
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

            $response = $this->client->post("/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error al enviar documento por WhatsApp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía un mensaje de plantilla a un número de WhatsApp.
     *
     * @param string $to Número de teléfono del destinatario
     * @param string $templateName Nombre de la plantilla
     * @param array $components Componentes de la plantilla
     * @return array
     */
    public function sendTemplateMessage(string $to, string $templateName, array $components = []): array
    {
        try {
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

            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            $response = $this->client->post("/{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error al enviar plantilla por WhatsApp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica el estado de un mensaje enviado.
     *
     * @param string $messageId ID del mensaje
     * @return array
     */
    public function getMessageStatus(string $messageId): array
    {
        try {
            $response = $this->client->get("/{$this->apiVersion}/{$messageId}");

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error al verificar estado del mensaje de WhatsApp: ' . $e->getMessage());
            throw $e;
        }
    }
} 