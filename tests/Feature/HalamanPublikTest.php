<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('halaman pantauan memisahkan batas administrasi, referensi zona, dan data historis', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Pyro')
        ->assertSee('Guard')
        ->assertSee('Bromo')
        ->assertSee('id="petaPantauan"', false)
        ->assertSee('Batas administrasi')
        ->assertSee('Wilayah analisis GEE')
        ->assertSee('Referensi zona')
        ->assertSee('Logistik')
        ->assertSee('bukan batas wilayah')
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
