<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kiosk Access Token
    |--------------------------------------------------------------------------
    |
    | Las pantallas del escáner (check-in, check-out, asistencia) disparan
    | mensajes reales de WhatsApp a los tutores, así que no pueden quedar
    | abiertas a internet. La tablet se autoriza UNA vez abriendo cualquier
    | pantalla del escáner con ?kiosk_token=<token>; a partir de ahí guarda una
    | cookie firmada y funciona sin volver a pedirlo.
    |
    | Sin token configurado el escáner queda cerrado (solo entran usuarios
    | autenticados del panel).
    |
    */

    'token' => env('KIOSK_TOKEN'),

    'cookie' => 'lrk_kiosk',
];
