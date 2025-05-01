<?php

use Illuminate\Support\Facades\Route;
use App\Events\WhatsAppNotification;
Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/whatsapp', [App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp');
Route::post('/test-notification', [App\Http\Controllers\WhatsAppController::class, 'testNotification'])
    ->name('test.notification')
    ->middleware('web');