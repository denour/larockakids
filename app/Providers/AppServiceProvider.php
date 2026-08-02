<?php

namespace App\Providers;

use App\Events\WhatsAppNotification;
use App\Listeners\SendTutorNotification;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Event;
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registro explícito: no dependemos del autodescubrimiento de listeners
        // porque de esto dependen los avisos a los papás.
        Event::listen(WhatsAppNotification::class, SendTutorNotification::class);
    }
}
