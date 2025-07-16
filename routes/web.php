<?php

use Illuminate\Support\Facades\Route;
use App\Events\WhatsAppNotification;
use App\Http\Controllers\KidExportController;
Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/whatsapp', [App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp');
Route::post('/test-notification', [App\Http\Controllers\WhatsAppController::class, 'testNotification'])
    ->name('test.notification')
    ->middleware('web');
Route::get('/export/kids', KidExportController::class)->name('export.kids');