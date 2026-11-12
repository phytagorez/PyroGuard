<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('halaman peta risiko hanya memuat desa hasil analisis dan fitur cek lokasi', function () {
    $this->artisan('app:import-dataset-desa')->assertSuccessful();

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('id="petaPantauan"', false)
        ->assertSee('Cek lokasi saya')
        ->assertSee('Peta risiko desa')
        ->assertSee('Lapor asap');

    // 30 desa dikirim ke peta; zona BRM lama tidak ikut.
    expect(App\Models\Zona::desa()->count())->toBe(30);
});

test('halaman jalur evakuasi (/evakuasi) memuat peta, sumber air, arah angin, dan emergency bar', function () {
    $response = $this->get('/evakuasi');

    $response->assertOk()
        ->assertSee('Jalur Evakuasi')
        ->assertSee('Sumber Air')
        ->assertSee('id="petaEvakuasi"', false)
        ->assertSee('Angin Dominan Kawasan')
        ->assertSee('Embung Savana Blok Jemplang')
        ->assertSee('Telepon 112')
        ->assertSee('Bagikan lokasi');
});

test('halaman metode menampilkan sumber data formula dan status validasi yang jujur', function () {
    $response = $this->get('/metode');

    $response->assertOk()
        ->assertSee('Metode Perhitungan')
        ->assertSee('CDD (Consecutive Dry Days)')
        ->assertSee('FDI (Fire Danger Index)')
        ->assertSee('Poin_CDD')
        ->assertSee('Hasil evaluasi belum tersedia')
        ->assertSee('Sumber & status dataset', false)
        ->assertDontSee('91.2%')
        ->assertDontSee('8.4%')
        ->assertSee('Alat Bantu Keputusan');
});

test('halaman lapor dan notifikasi dapat diakses publik tanpa hambatan', function () {
    $responseLapor = $this->get('/lapor');
    $responseLapor->assertOk()
        ->assertSee('Lapor Asap')
        ->assertSee('Kirim laporan sekarang');

    $responseNotifikasi = $this->get('/notifikasi');
    $responseNotifikasi->assertOk()
        ->assertSee('Peringatan Dini')
        ->assertSee('Notifikasi Web Browser');
});
