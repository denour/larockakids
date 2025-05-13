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
]; 