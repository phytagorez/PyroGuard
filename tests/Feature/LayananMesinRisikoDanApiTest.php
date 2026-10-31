<?php

use App\Models\LaporanAsap;
use App\Models\PrakiraanRisiko;
use App\Models\StatusSiaga;
use App\Models\Zona;
use App\Services\LayananCuacaOpenMeteo;
use App\Services\LayananMesinRisiko;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('layanan mesin risiko mampu menghitung cdd, fdi, dan memicu siaga otomatis', function () {
    $savana = Zona::where('kode', 'BRM-04')->first();
    $layanan = app(LayananMesinRisiko::class);

    $risiko = $layanan->hitungRisikoZona($savana, 'aktual');

    expect($risiko)->toBeInstanceOf(PrakiraanRisiko::class)
        ->and($risiko->hari_tanpa_hujan)->toBe(19)
        ->and($risiko->indeks_bahaya_api)->toBe(86)
        ->and($risiko->kategori_risiko)->toBe('BAHAYA')
        ->and($risiko->status_siaga)->toBe('SIAGA_OTOMATIS')
        ->and($risiko->jam_rentan)->toBe('12:00 - 15:30 WIB');
});

test('layanan open-meteo mampu menyinkronkan cuaca aktual', function () {
    $zona = Zona::where('kode', 'BRM-01')->first();
    $layanan = app(LayananCuacaOpenMeteo::class);

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'daily' => [
                'time' => [now()->toDateString()],
                'precipitation_sum' => [0.0],
                'relative_humidity_2m_min' => [40.0],
                'wind_speed_10m_max' => [18.0],
                'wind_direction_10m_dominant' => [135.0], // Tenggara
                'temperature_2m_max' => [23.0],
            ],
        ], 200),
    ]);

    $hasilSukses = $layanan->sinkronCuacaZona($zona);
    expect($hasilSukses['sukses'])->toBeTrue()
        ->and($hasilSukses['status_kesegaran'])->toBe('segar');
});

test('layanan open-meteo mampu menangani kegagalan api cuaca dan mengaktifkan fallback kesegaran data', function () {
    $zona = Zona::where('kode', 'BRM-01')->first();
    $layanan = app(LayananCuacaOpenMeteo::class);

    Http::fake([
        'api.open-meteo.com/*' => Http::response([], 500),
    ]);

    $hasilGagal = $layanan->sinkronCuacaZona($zona);
    expect($hasilGagal['sukses'])->toBeFalse()
        ->and($hasilGagal)->toHaveKey('status_kesegaran')
        ->and($hasilGagal)->toHaveKey('pesan');
});

test('artisan command pyroguard:sinkron-cuaca berjalan sukses', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'daily' => [
                'time' => [now()->toDateString()],
                'precipitation_sum' => [0.0],
                'relative_humidity_2m_min' => [40.0],
                'wind_speed_10m_max' => [18.0],
                'wind_direction_10m_dominant' => [135.0],
                'temperature_2m_max' => [23.0],
            ],
        ], 200),
    ]);

    $this->artisan('pyroguard:sinkron-cuaca', ['--zona' => 'BRM-04'])
        ->assertSuccessful();
});

test('kontrak api GET /api/v1/forecast/risk-zone mengembalikan format persis §16.2 design.md', function () {
    $response = $this->getJson('/api/v1/forecast/risk-zone?zone=BRM-04');

    $response->assertOk()
        ->assertJsonStructure([
            'zone_id',
            'name',
            'consecutive_dry_days',
            'projection' => ['h24', 'h48', 'h72'],
            'fire_danger_index',
            'risk_category',
            'factor_contribution' => ['cdd', 'humidity', 'wind'],
            'peak_risk_window',
            'wind' => ['speed_kmh', 'from'],
            'alert_status',
            'data_state',
            'computed_at',
        ])
        ->assertJson([
            'zone_id' => 'BRM-04',
            'consecutive_dry_days' => 19,
            'fire_danger_index' => 86,
            'risk_category' => 'BAHAYA',
            'alert_status' => 'SIAGA_OTOMATIS',
            'peak_risk_window' => '12:00 - 15:30 WIB',
            'wind' => [
                'speed_kmh' => 28,
                'from' => 'Tenggara',
            ],
            'factor_contribution' => [
                'cdd' => 54,
                'humidity' => 24,
                'wind' => 8,
            ],
        ]);
});

test('kontrak api GET /api/v1/zones mengembalikan 6 zona dengan geojson dan risiko', function () {
    $response = $this->getJson('/api/v1/zones');

    $response->assertOk()
        ->assertJsonPath('total', 6)
        ->assertJsonStructure([
            'status',
            'total',
            'data' => [
                '*' => [
                    'id',
                    'kode',
                    'nama',
                    'poligon_geojson',
                    'titik_tengah',
                    'risiko',
                    'kesegaran_data',
                ],
            ],
        ]);
});

test('kontrak api GET /api/v1/hotspots dan GET /api/v1/water-points berfungsi', function () {
    $hotspots = $this->getJson('/api/v1/hotspots');
    $hotspots->assertOk()
        ->assertJsonStructure(['status', 'rentang_waktu_jam', 'total', 'data']);

    $water = $this->getJson('/api/v1/water-points');
    $water->assertOk()
        ->assertJsonStructure(['status', 'total', 'data']);
});

test('kontrak api POST /api/v1/reports membuat tiket laporan asap baru', function () {
    $payload = [
        'latitude' => -7.9691234,
        'longitude' => 112.9641234,
        'jenis_temuan' => 'asap_tebal',
        'catatan' => 'Uji laporan warga dekat savana',
        'akurasi_gps' => 10.5,
    ];

    $response = $this->postJson('/api/v1/reports', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'pesan',
            'data' => [
                'id',
                'nomor_tiket',
                'zona',
                'jenis_temuan',
                'status_tiket',
            ],
        ]);

    $nomorTiket = $response->json('data.nomor_tiket');
    expect($nomorTiket)->toStartWith('#LP-');
});

test('kontrak api PATCH /api/v1/reports/{id}/verify memverifikasi tiket oleh petugas', function () {
    $laporan = LaporanAsap::where('nomor_tiket', '#LP-0232')->first();

    $response = $this->patchJson("/api/v1/reports/{$laporan->id}/verify", [
        'hasil_verifikasi' => 'api_nyata',
        'catatan' => 'Sudah dicek ke lokasi, benar titik api semak.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status_tiket', 'terverifikasi')
        ->assertJsonPath('data.hasil_verifikasi', 'api_nyata');
});

test('kontrak api POST /api/v1/alerts/{zone}/approve menyetujui siaga 1 dan mencabut siaga', function () {
    // 1. Setujui Siaga 1
    $responseApprove = $this->postJson('/api/v1/alerts/BRM-04/approve', [
        'tindakan' => 'setujui',
        'catatan' => 'Kondisi kemarau ekstrem, tetapkan Siaga 1.',
    ]);

    $responseApprove->assertOk()
        ->assertJsonPath('data.status_siaga', 'SIAGA_1');

    $statusAktif = StatusSiaga::where('zona_id', Zona::where('kode', 'BRM-04')->first()->id)->latest('id')->first();
    expect($statusAktif->status)->toBe('SIAGA_1');

    // 2. Cabut Siaga
    $responseRevoke = $this->postJson('/api/v1/alerts/BRM-04/approve', [
        'tindakan' => 'cabut',
        'catatan' => 'Hujan lebat telah turun, status dicabut.',
    ]);

    $responseRevoke->assertOk()
        ->assertJsonPath('data.status_siaga', 'DICABUT');
});
