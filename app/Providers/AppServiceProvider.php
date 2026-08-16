<?php

namespace App\Providers;

use App\Services\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });
    }

    // El listener App\Listeners\SendTutorNotification se registra SOLO por
    // autodescubrimiento: Laravel escanea app/Listeners y encuentra su
    // handle(WhatsAppNotification). NO lo registres aquí con Event::listen():
    // duplica el registro y cada aviso al tutor sale DOS veces.
}
