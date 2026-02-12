<?php

use App\Http\Controllers\Api\QueryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.auth'])->prefix('v1')->group(function () {
    Route::get('/kids', [QueryController::class, 'kids']);
    Route::get('/attendance', [QueryController::class, 'attendance']);
    Route::get('/export/kids', [QueryController::class, 'exportKids']);
});
