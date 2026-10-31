<?php

use App\Models\LaporanAsap;
use App\Models\Pengguna;
use App\Models\Zona;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('konfigurasi layanan eksternal terdaftar lengkap dengan kunci env', function () {
    expect(config('services.google'))->toHaveKeys(['client_id', 'client_secret', 'redirect'])
        ->and(config('services.telegram'))->toHaveKeys(['bot_token', 'bot_username', 'public_channel_id', 'officer_group_id', 'webhook_secret'])
        ->and(config('services.open_meteo'))->toHaveKey('base_url')
        ->and(config('services.firms'))->toHaveKeys(['api_key', 'base_url'])
        ->and(config('services.vapid'))->toHaveKeys(['public_key', 'private_key', 'subject']);
});

test('zona bromo terdaftar 6 wilayah dengan koordinat poligon geojson', function () {
    $zonas = Zona::orderBy('kode')->get();

    expect($zonas)->toHaveCount(6);

    $kodes = $zonas->pluck('kode')->all();
    expect($kodes)->toBe(['BRM-01', 'BRM-02', 'BRM-03', 'BRM-04', 'BRM-05', 'BRM-06']);

    foreach ($zonas as $zona) {
        expect($zona->poligon_geojson)->toBeArray()
            ->and($zona->poligon_geojson['type'])->toBe('Polygon')
            ->and($zona->poligon_geojson['coordinates'])->toBeArray()
            ->and($zona->titik_tengah_lat)->toBeFloat()
            ->and($zona->titik_tengah_lng)->toBeFloat();
    }
});

test('zona savana jemplang (brm-04) berstatus bahaya cdd 19 dan fdi 86', function () {
    $savana = Zona::where('kode', 'BRM-04')->first();

    expect($savana)->not->toBeNull();

    $risiko = $savana->risikoTerbaru;
    expect($risiko)->not->toBeNull()
        ->and($risiko->hari_tanpa_hujan)->toBe(19)
        ->and($risiko->indeks_bahaya_api)->toBe(86)
        ->and($risiko->kategori_risiko)->toBe('BAHAYA')
        ->and($risiko->status_siaga)->toBe('SIAGA_OTOMATIS')
        ->and($risiko->jam_rentan)->toBe('12:00 - 15:30 WIB');
});

test('tiga persona akun contoh terdaftar dengan peran sesuai skenario', function () {
    $admin = Pengguna::where('email', 'admin@bpbd.go.id')->first();
    $petugas = Pengguna::where('email', 'agus@tnbts.go.id')->first();
    $wisatawan = Pengguna::where('email', 'rina@wisatawan.id')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->peran)->toBe('admin');

    expect($petugas)->not->toBeNull()
        ->and($petugas->isPetugas())->toBeTrue()
        ->and($petugas->peran)->toBe('petugas')
        ->and($petugas->telegram_chat_id)->not->toBeNull();

    expect($wisatawan)->not->toBeNull()
        ->and($wisatawan->isPublik())->toBeTrue()
        ->and($wisatawan->peran)->toBe('publik');
});

test('relasi model elonquent berfungsi dengan bersih dan presisi', function () {
    $savana = Zona::where('kode', 'BRM-04')->first();

    expect($savana->cuacaHarian()->count())->toBe(30)
        ->and($savana->sumberAir()->count())->toBeGreaterThan(0)
        ->and($savana->titikPanas()->count())->toBeGreaterThan(0)
        ->and($savana->laporanAsap()->count())->toBeGreaterThan(0);

    $laporan = LaporanAsap::where('nomor_tiket', '#LP-0231')->first();
    expect($laporan->zona->kode)->toBe('BRM-04')
        ->and($laporan->hasil_verifikasi)->toBe('api_nyata')
        ->and($laporan->verifikator->email)->toBe('agus@tnbts.go.id');
});
