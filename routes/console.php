<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vivía en app/Console/Kernel.php, que Laravel 12 ya no carga: el cron nunca corría.
Schedule::command('attendances:clean')
    ->dailyAt('20:00')
    ->timezone('America/Tijuana');
