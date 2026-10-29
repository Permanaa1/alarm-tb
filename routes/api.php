<?php

use App\Http\Controllers\Api\V1\AktivasiController;
use App\Http\Controllers\Api\V1\LogMinumController;
use App\Http\Controllers\Api\V1\PasienController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('aktivasi', AktivasiController::class)->middleware('throttle:aktivasi');

    Route::middleware(['auth:sanctum', 'pasien', 'throttle:api-pasien'])->group(function () {
        Route::get('profil', [PasienController::class, 'profil']);
        Route::get('jadwal', [PasienController::class, 'jadwal']);
        Route::get('riwayat', [PasienController::class, 'riwayat']);
        Route::post('consent', [PasienController::class, 'consent']);
        Route::put('perangkat', [PasienController::class, 'perangkat']);
        Route::post('log-minum/sinkron', [LogMinumController::class, 'sinkron']);
    });
});
