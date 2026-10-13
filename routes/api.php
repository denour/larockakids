<?php

use App\Http\Controllers\Api\QueryController;
use Illuminate\Support\Facades\Route;

// Cada ruta pide su permiso; `api.auth` sin permiso exige '*' (ver ApiKeyAuth).
Route::prefix('v1')->group(function () {
    Route::get('/kids', [QueryController::class, 'kids'])->middleware('api.auth:kids:read');
    Route::patch('/kids/{id}', [QueryController::class, 'updateKid'])->middleware('api.auth:kids:write');
    Route::post('/admin/fix-genders', [QueryController::class, 'fixGenders'])->middleware('api.auth');
    Route::get('/attendance', [QueryController::class, 'attendance'])->middleware('api.auth:attendance:read');
    Route::get('/export/kids', [QueryController::class, 'exportKids'])->middleware('api.auth:export');
});
