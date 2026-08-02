<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar las credenciales y opciones para WhatsApp Business API.
    |
    */

    'token' => env('WHATSAPP_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v17.0'),
    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

    /*
    |--------------------------------------------------------------------------
    | Bridge (Evolution API)
    |--------------------------------------------------------------------------
    |
    | Salida de los avisos a tutores. Sustituye al esquema anterior, que dependía
    | de una pestaña del navegador escuchando Pusher para enviar por WhatsApp Web:
    | si la pestaña se cerraba, los avisos dejaban de salir sin que nadie se diera
    | cuenta. Sin `url` configurada el envío queda deshabilitado y solo se registra
    | en el log, para que un entorno sin bridge no rompa el pase de asistencia.
    |
    */
    'bridge' => [
        'url' => env('WHATSAPP_BRIDGE_URL'),
        'api_key' => env('WHATSAPP_BRIDGE_API_KEY'),
        'instance' => env('WHATSAPP_BRIDGE_INSTANCE'),
        'timeout' => (int) env('WHATSAPP_BRIDGE_TIMEOUT', 10),
    ],
];