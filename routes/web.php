<?php

use App\Http\Controllers\KidExportController;
use App\Http\Controllers\QrCodePrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/whatsapp', [App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp');
Route::post('/test-notification', [App\Http\Controllers\WhatsAppController::class, 'testNotification'])
    ->name('test.notification')
    ->middleware('web');
Route::get('/export/kids', KidExportController::class)->name('export.kids');

Route::get('/qr-codes/{qrCode}/print', [QrCodePrintController::class, 'print'])->name('qr-codes.print');
Route::get('/qr-codes/print-batch', [QrCodePrintController::class, 'printBatch'])->name('qr-codes.print-batch');
