<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('halaman utama pantauan (/) memuat banner status kawasan, peta leaflet, 6 zona bromo, dan fab lapor', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Pyro')
        ->assertSee('Guard')
        ->assertSee('Bromo')
        ->assertSee('STATUS KAWASAN TERTINGGI')
        ->assertSee('id="petaPantauan"', false)
        ->assertSee('Zonasi Risiko')
        ->assertSee('Titik Panas')
        ->assertSee('Logistik')
        ->assertSee('Jalur Evakuasi')
        ->assertSee('Sumber Air Terdekat')
        ->assertSee('Bukti Lapangan 72 Jam Terakhir')
        ->assertSee('Lapor asap')
        ->assertSee('BRM-01')
        ->assertSee('BRM-02')
        ->assertSee('BRM-03')
        ->assertSee('BRM-04')
        ->assertSee('BRM-05')
        ->assertSee('BRM-06');
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

test('halaman metode (/metode) menampilkan formula fdi, ambang batas, hasil backtesting, dan batas otoritas', function () {
    $response = $this->get('/metode');

    $response->assertOk()
        ->assertSee('Metode Perhitungan')
        ->assertSee('CDD (Consecutive Dry Days)')
        ->assertSee('FDI (Fire Danger Index)')
        ->assertSee('Poin_CDD')
        ->assertSee('91.2%')
        ->assertSee('8.4%')
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
