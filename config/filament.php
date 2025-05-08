<?php

return [
    'auth' => [
        'guard' => 'web',
        'pages' => [
            'login' => \Filament\Pages\Auth\Login::class,
        ],
        'registration' => false,
    ],
    'layout' => [
        'sidebar' => [
            'is_collapsible_on_desktop' => true,
            'groups' => [
                'are_collapsible' => true,
            ],
        ],
    ],
    'pages' => [
        'dashboard' => \Filament\Pages\Dashboard::class,
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