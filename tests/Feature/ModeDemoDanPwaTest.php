<?php

use App\Models\Pengguna;
use Database\Seeders\PenggunaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Jalankan seeder kawasan Bromo
    $this->seed(ZonaSeeder::class);
    $this->seed(PenggunaSeeder::class);
});

test('halaman mode demo dapat diakses dan menampilkan komponen DemoDial serta 3 skenario', function () {
    $response = $this->get('/demo');

    $response->assertOk()
        ->assertSee('MODE DEMO · [SIMULASI]')
        ->assertSee('Penggeser Hari Kemarau')
        ->assertSee('Awal Kemarau (Hari 0)')
        ->assertSee('Kebakaran Sept 2023 (Hari 15)')
        ->assertSee('Hujan Pertama (Hari 20)')
        ->assertSee('Kembali ke Data Aktual');

    expect(session('mode_demo'))->toBeTrue();
});

test('simulasi hari ke-0 menghasilkan status AMAN untuk seluruh 6 zona Bromo', function () {
    $response = $this->postJson('/demo/simulasi', ['hari' => 0]);

    $response->assertOk()
        ->assertJson([
            'sukses' => true,
            'hari' => 0,
        ]);

    $zonas = $response->json('zonas');
    expect($zonas)->toBeArray()->toHaveCount(6);

    foreach ($zonas as $z) {
        expect($z['kategori'])->toBe('AMAN')
            ->and($z['siaga'])->toBe('NORMAL');
    }
});

test('simulasi hari ke-15 memicu status BAHAYA dan SIAGA_OTOMATIS di zona Savana BRM-04', function () {
    $response = $this->postJson('/demo/simulasi', ['hari' => 15]);

    $response->assertOk()
        ->assertJson([
            'sukses' => true,
            'hari' => 15,
        ]);

    $zonas = collect($response->json('zonas'))->keyBy('kode');
    $savana = $zonas['BRM-04'];

    expect($savana['cdd'])->toBe(19)
        ->and($savana['fdi'])->toBe(86)
        ->and($savana['kategori'])->toBe('BAHAYA')
        ->and($savana['siaga'])->toBe('SIAGA_OTOMATIS');
});

test('simulasi hari ke-20 menyimulasikan hujan pertama dan status SIAGA 1 DICABUT', function () {
    $response = $this->postJson('/demo/simulasi', ['hari' => 20]);

    $response->assertOk()
        ->assertJson([
            'sukses' => true,
            'hari' => 20,
        ]);

    $zonas = collect($response->json('zonas'))->keyBy('kode');
    $savana = $zonas['BRM-04'];

    expect($savana['cdd'])->toBe(0)
        ->and($savana['fdi'])->toBe(41)
        ->and($savana['kategori'])->toBe('AMAN')
        ->and($savana['siaga'])->toBe('DICABUT');
});

test('endpoint api publik /api/v1/demo/simulate-day merespons kalkulasi json yang valid', function () {
    $response = $this->postJson('/api/v1/demo/simulate-day', ['hari' => 10]);

    $response->assertOk()
        ->assertJsonStructure([
            'sukses',
            'hari',
            'zonaTertinggi',
            'zonas',
            'pesan',
        ]);
});

test('tombol buat laporan simulasi membuat tiket dan mengarahkan ke layar verifikasi petugas', function () {
    $petugas = Pengguna::factory()->petugas()->create();

    $response = $this->actingAs($petugas)->post('/demo/buat-laporan-contoh');

    $response->assertRedirect(route('petugas.index'));
    $this->assertDatabaseHas('laporan_asap', [
        'ip_pelapor' => '127.0.0.1',
        'status_tiket' => 'baru',
    ]);
});

test('reset demo mengembalikan sesi dan mengarahkan ke halaman pantauan aktual', function () {
    session(['mode_demo' => true]);

    $response = $this->post('/demo/reset');

    $response->assertRedirect(route('pantauan'));
    expect(session('mode_demo'))->toBeNull();
});

test('manifest pwa dan service worker berstatus valid dan dapat dibaca', function () {
    $manifestPath = public_path('manifest.json');
    expect(file_exists($manifestPath))->toBeTrue();

    $manifest = json_decode(file_get_contents($manifestPath), true);
    expect($manifest)->toBeArray()
        ->and($manifest['name'])->toContain('PyroGuard')
        ->and($manifest['start_url'])->toBe('/')
        ->and($manifest['display'])->toBe('standalone');

    $swPath = public_path('sw.js');
    expect(file_exists($swPath))->toBeTrue();

    $swContent = file_get_contents($swPath);
    expect($swContent)->toContain('pyroguard-shell-v2')
        ->and($swContent)->toContain('tile.openstreetmap.org');
});
