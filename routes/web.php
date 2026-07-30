<?php

use App\Http\Controllers\KidExportController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\QrCodePrintController;
use App\Http\Controllers\QrScannerController;
use App\Http\Controllers\WhatsAppWebhookController;
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

Route::prefix('onboarding')->name('onboarding.')->middleware('auth')->group(function () {
    Route::get('/', [OnboardingController::class, 'entry'])->name('entry');
    Route::get('/inicio', [OnboardingController::class, 'splash'])->name('splash');
    Route::get('/status/{code}', [OnboardingController::class, 'status'])->name('status');
    Route::get('/locale/{locale}', LocaleController::class)
        ->whereIn('locale', config('onboarding.locales'))
        ->name('locale');
    Route::get('/search', [OnboardingController::class, 'search'])->name('search');
    Route::post('/search', [OnboardingController::class, 'find'])->name('find');
    Route::get('/register', [OnboardingController::class, 'register'])->name('register');
    Route::post('/register', [OnboardingController::class, 'store'])->name('store');
    Route::get('/{kid}/confirm', [OnboardingController::class, 'confirm'])->name('confirm');
    Route::get('/{kid}/edit', [OnboardingController::class, 'edit'])->name('edit');
    Route::put('/{kid}', [OnboardingController::class, 'update'])->name('update');
    Route::get('/{kid}/done', [OnboardingController::class, 'done'])->name('done');
});

Route::match(['get'], '/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp.handle');

Route::prefix('scanner')->name('scanner.')->group(function () {
    Route::get('/check-in', [QrScannerController::class, 'checkInPage'])->name('check-in');
    Route::post('/check-in', [QrScannerController::class, 'processCheckIn'])->name('check-in.process');
    Route::get('/check-out', [QrScannerController::class, 'checkOutPage'])->name('check-out');
    Route::post('/check-out', [QrScannerController::class, 'processCheckOut'])->name('check-out.process');
    Route::get('/assistance', [QrScannerController::class, 'assistancePage'])->name('assistance');
    Route::post('/assistance', [QrScannerController::class, 'processAssistance'])->name('assistance.process');
});
