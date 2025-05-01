<?php

return [
    'default_filesystem_disk' => 'public',
    'auth' => [
        'guard' => 'web',
        'pages' => [
            'login' => \App\Filament\Pages\Auth\Login::class,
        ],
    ],
    'pages' => [
        'namespace' => 'App\\Filament\\Pages',
        'path' => app_path('Filament/Pages'),
        'register' => [],
    ],
    'resources' => [
        'namespace' => 'App\\Filament\\Resources',
        'path' => app_path('Filament/Resources'),
        'register' => [],
    ],
    'widgets' => [
        'namespace' => 'App\\Filament\\Widgets',
        'path' => app_path('Filament/Widgets'),
        'register' => [],
    ],
    'styles' => [
        'css/app.css',
    ],
    'scripts' => [
        'https://js.pusher.com/8.2.0/pusher.min.js',
        'js/app.js',
        'js/filament/notifications/whatsapp.js',
    ],
    'assets' => [
        'https://js.pusher.com/8.2.0/pusher.min.js',
        'js/filament/notifications/whatsapp.js',
    ],
]; 