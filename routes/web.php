<?php

use App\Http\Controllers\KidExportController;
use App\Http\Controllers\QrCodePrintController;
use App\Http\Controllers\QrScannerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->group(function () {
    Route::get('/whatsapp', [App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp');
    Route::post('/test-notification', [App\Http\Controllers\WhatsAppController::class, 'testNotification'])
        ->name('test.notification');

    Route::get('/export/kids', KidExportController::class)->name('export.kids');

    Route::get('/qr-codes/{qrCode}/print', [QrCodePrintController::class, 'print'])->name('qr-codes.print');
    Route::get('/qr-codes/print-batch', [QrCodePrintController::class, 'printBatch'])->name('qr-codes.print-batch');
});

Route::prefix('scanner')->name('scanner.')->middleware('kiosk')->group(function () {
    Route::get('/check-in', [QrScannerController::class, 'checkInPage'])->name('check-in');
    Route::post('/check-in', [QrScannerController::class, 'processCheckIn'])->name('check-in.process');
    Route::get('/check-out', [QrScannerController::class, 'checkOutPage'])->name('check-out');
    Route::post('/check-out', [QrScannerController::class, 'processCheckOut'])->name('check-out.process');
    Route::get('/assistance', [QrScannerController::class, 'assistancePage'])->name('assistance');
    Route::post('/assistance', [QrScannerController::class, 'processAssistance'])->name('assistance.process');
});
