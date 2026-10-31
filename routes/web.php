<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AutentikasiController;
use App\Http\Controllers\EvakuasiController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\LaporanAsapController;
use App\Http\Controllers\MetodeController;
use App\Http\Controllers\ModeDemoController;
use App\Http\Controllers\PantauanController;
use App\Http\Controllers\TiketVerifikasiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - PyroGuard Sistem Peringatan Dini Bromo
|--------------------------------------------------------------------------
*/

// Layar Utama Publik (§7.1)
Route::get('/', PantauanController::class)->name('pantauan');

// Layar Evakuasi & Sumber Air (§7.5)
Route::get('/evakuasi', EvakuasiController::class)->name('evakuasi');

// Layar Metode & Keterbatasan Formula (§7.6)
Route::get('/metode', MetodeController::class)->name('metode');

// Layar Pengaturan Notifikasi & Kanal Siaga (§7.4)
Route::get('/notifikasi', function () {
    return view('notifikasi.index');
})->name('notifikasi');

// Mode Demo Interaktif & Penggeser Hari Kemarau (§6.13, §7.10, §15)
Route::get('/demo', [ModeDemoController::class, 'index'])->name('demo');
Route::post('/demo/simulasi', [ModeDemoController::class, 'simulasiHari'])->name('demo.simulasi');
Route::post('/demo/reset', [ModeDemoController::class, 'resetDemo'])->name('demo.reset');
Route::post('/demo/buat-laporan-contoh', [ModeDemoController::class, 'buatLaporanContoh'])->name('demo.buat-laporan-contoh');

// Layar Lapor Asap Warga (§6.8 & §7.3)
Route::get('/lapor', [LaporanAsapController::class, 'create'])->name('lapor.index');
Route::post('/lapor', [LaporanAsapController::class, 'store'])->name('lapor.store');

// Autentikasi & Masuk (§7.9)
Route::get('/masuk', [AutentikasiController::class, 'tampilMasuk'])->name('masuk');
Route::get('/login', fn () => redirect()->route('masuk'))->name('login');
Route::post('/masuk', [AutentikasiController::class, 'masuk'])->name('masuk.post');
Route::post('/keluar', [AutentikasiController::class, 'keluar'])->name('keluar');

// Pendaftaran Petugas Dinas TNBTS
Route::get('/daftar-petugas', [AutentikasiController::class, 'tampilDaftarPetugas'])->name('daftar-petugas');
Route::post('/daftar-petugas', [AutentikasiController::class, 'daftarPetugas'])->name('daftar-petugas.post');

// Google OAuth
Route::get('/auth/google', [GoogleOAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('auth.google.callback');

// Alur Kerja Petugas Lapangan TNBTS (§6.9 & §7.7) - Terproteksi RBAC
Route::middleware(['auth', 'peran:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/', [TiketVerifikasiController::class, 'index'])->name('index');
    Route::post('/{laporan:nomor_tiket}/menuju-lokasi', [TiketVerifikasiController::class, 'menujuLokasi'])->name('menuju-lokasi');
    Route::post('/{laporan:nomor_tiket}/verifikasi', [TiketVerifikasiController::class, 'verifikasi'])->name('verifikasi');
    Route::post('/{laporan:nomor_tiket}/tandai-ditangani', [TiketVerifikasiController::class, 'tandaiDitangani'])->name('tandai-ditangani');
});

// Dasbor Otoritas Admin BPBD (§6.10, §7.8, §14) - Terproteksi RBAC Admin
Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.tinjauan'));
    Route::get('/tinjauan', [AdminController::class, 'tinjauan'])->name('tinjauan');
    Route::get('/siaga', [AdminController::class, 'siaga'])->name('siaga');
    Route::post('/siaga/{zona}/setujui', [AdminController::class, 'setujuiSiaga'])->name('siaga.setujui');
    Route::post('/siaga/{zona}/cabut', [AdminController::class, 'cabutSiaga'])->name('siaga.cabut');
    Route::get('/siaran', [AdminController::class, 'siaran'])->name('siaran');
    Route::post('/siaran', [AdminController::class, 'kirimSiaran'])->name('siaran.kirim');
    Route::get('/log-siaran', [AdminController::class, 'logSiaran'])->name('log-siaran');
    Route::get('/log-siaran/ekspor', [AdminController::class, 'eksporLogSiaranCsv'])->name('log-siaran.ekspor');
    Route::get('/laporan', [AdminController::class, 'laporan'])->name('laporan');
    Route::post('/toggle-demo', [AdminController::class, 'toggleDemo'])->name('toggle-demo');
});
