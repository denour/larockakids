<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envía los avisos a tutores por el bridge de Evolution.
 *
 * Antes esto salía por Pusher: el panel emitía un evento y una pestaña abierta
 * en el navegador lo escuchaba y mandaba el mensaje por WhatsApp Web. Si esa
 * pestaña se cerraba, los papás dejaban de recibir avisos en silencio. Ahora el
 * envío ocurre en el servidor.
 */
class WhatsAppBridgeService
{
    public function isConfigured(): bool
    {
        return filled(config('whatsapp.bridge.url'))
            && filled(config('whatsapp.bridge.instance'));
    }

    /**
     * Manda un texto. Devuelve true solo si el bridge lo aceptó.
     *
     * Nunca lanza: un fallo del bridge no debe tumbar el registro de asistencia,
     * que es lo que el staff está haciendo cuando esto se dispara.
     */
    public function sendText(string $phoneNumber, string $message): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('[WhatsAppBridge] Sin configurar; no se envió el aviso.', [
                'phone' => $phoneNumber,
            ]);

            return false;
        }

        $url = rtrim((string) config('whatsapp.bridge.url'), '/');
        $instance = config('whatsapp.bridge.instance');

        try {
            $response = Http::withHeaders(['apikey' => (string) config('whatsapp.bridge.api_key')])
                ->timeout((int) config('whatsapp.bridge.timeout', 10))
                ->post("{$url}/message/sendText/{$instance}", [
                    'number' => $phoneNumber,
                    'text' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::error('[WhatsAppBridge] Falló el envío del aviso.', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if ($response->failed()) {
            Log::error('[WhatsAppBridge] El bridge rechazó el aviso.', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return false;
        }

        return true;
    }
}
