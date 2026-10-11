<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Murid\RiwayatController;
use App\Http\Controllers\Api\Murid\ScanController;
use App\Http\Controllers\Api\Murid\StatusScanController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/*
| Pembatas login: maksimal 5 percobaan per menit untuk setiap kombinasi
| NIS/email dan alamat IP. Dikunci per akun agar Wi-Fi sekolah yang dipakai
| bersama tidak memblokir semua murid sekaligus.
*/
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by(strtolower((string) $request->input('identitas')).'|'.$request->ip());
});

// Tanpa login
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Wajib login (token Sanctum), untuk semua peran
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Endpoint untuk aplikasi murid. Endpoint petugas ditaruh di bawah awalan /petugas.
    Route::prefix('murid')->group(function () {
        Route::get('/status-scan', StatusScanController::class);
        Route::post('/scan', ScanController::class)->middleware('throttle:10,1');
        Route::get('/riwayat', RiwayatController::class);
    });
});