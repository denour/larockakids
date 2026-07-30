<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Onboarding / kiosk configuration
    |--------------------------------------------------------------------------
    */

    // Number shown on the kiosk screen for parents to message the code to.
    'whatsapp_display_number' => env('ONBOARDING_WHATSAPP_NUMBER', '+52 55 1234 5678'),

    // Token that Meta echoes back when verifying the webhook subscription.
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),

    // Locales offered by the kiosk language switcher. Also the whitelist enforced
    // by the onboarding.locale route and by the SetLocale middleware.
    'locales' => ['es', 'en'],
];
