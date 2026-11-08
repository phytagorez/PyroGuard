<?php

use App\Http\Controllers\Api\V1\DatasetGeospasialController;
use App\Http\Controllers\Api\V1\LaporanAsapController;
use App\Http\Controllers\Api\V1\PrakiraanRisikoController;
use App\Http\Controllers\Api\V1\StatusSiagaController;
use App\Http\Controllers\Api\V1\SumberAirController;
use App\Http\Controllers\Api\V1\TelegramWebhookController;
use App\Http\Controllers\Api\V1\TitikPanasController;
use App\Http\Controllers\Api\V1\ZonaController;
use App\Http\Controllers\ModeDemoController;
use App\Services\LayananNotifikasiPush;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — PyroGuard Bromo
|--------------------------------------------------------------------------
| Sesuai spesifikasi kontrak data §16.2 design.md
*/

Route::prefix('v1')->group(function () {
    Route::get('/geospatial', [DatasetGeospasialController::class, 'index'])->name('api.dataset.index');
    Route::get('/geospatial/boundaries', [DatasetGeospasialController::class, 'batas'])->name('api.dataset.batas');
    Route::get('/geospatial/area', [DatasetGeospasialController::class, 'wilayah'])->name('api.dataset.wilayah');

    // 1. Prakiraan Risiko per Zona (Kontrak Utama §16.2)
    Route::get('/forecast/risk-zone', [PrakiraanRisikoController::class, 'show']);

    // 2. Daftar 6 Zona Bromo beserta GeoJSON & Ringkasan Risiko
    Route::get('/zones', [ZonaController::class, 'index']);
    Route::get('/zones/{kode}', [ZonaController::class, 'show']);

    // 3. Titik Panas Satelit FIRMS (72 jam terakhir)
    Route::get('/hotspots', [TitikPanasController::class, 'index']);

    // 4. Sumber Air, Pos Jaga & Jalur Evakuasi
    Route::get('/water-points', [SumberAirController::class, 'index']);

    // 5. Laporan Asap Warga & Tiket Petugas
    Route::post('/reports', [LaporanAsapController::class, 'store']);
    Route::patch('/reports/{id}/verify', [LaporanAsapController::class, 'verify']);

    // 6. Persetujuan / Pencabutan Status Siaga 1 oleh Admin
    Route::post('/alerts/{zone}/approve', [StatusSiagaController::class, 'approve']);

    // 7. Webhook Telegram Bot (§14.3)
    Route::post('/telegram/webhook', TelegramWebhookController::class);

    // 8. Langganan Web-Push Notification (§7.4)
    Route::post('/notifications/subscribe', function (Request $request, LayananNotifikasiPush $push) {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'public_key' => 'nullable|string',
            'auth_token' => 'nullable|string',
            'zona_id' => 'nullable|exists:zona,id',
        ]);

        $langganan = $push->simpanLangganan($validated, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Langganan notifikasi berhasil didaftarkan.',
            'data' => $langganan,
        ], 201);
    });

    // 9. API Mode Demo (§6.13)
    Route::post('/demo/simulate-day', [ModeDemoController::class, 'apiSimulateDay']);
});
