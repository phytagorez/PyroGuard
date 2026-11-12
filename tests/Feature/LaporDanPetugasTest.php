<?php

use App\Models\LaporanAsap;
use App\Models\Pengguna;
use App\Models\Zona;
use App\Services\LayananKompresiFoto;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('public');
});

test('halaman lapor (/lapor) menampilkan formulir 1 layar dengan gps, peta mini, dan chip jenis gejala', function () {
    $response = $this->get('/lapor');

    $response->assertOk()
        ->assertSee('Lapor Asap')
        ->assertSee('Titik Lokasi Temuan')
        ->assertSee('id="petaMiniLapor"', false)
        ->assertSee('Asap tipis')
        ->assertSee('Asap tebal')
        ->assertSee('Api terlihat')
        ->assertSee('Bekas terbakar')
        ->assertSee('Foto Lapangan')
        ->assertSee('Kirim laporan sekarang');
});

test('pelapor dapat mengirim laporan asap dan menerima nomor tiket unik', function () {
    $savana = Zona::where('kode', 'BRM-04')->first();

    $response = $this->post('/lapor', [
        'latitude' => -7.9650000,
        'longitude' => 112.9680000,
        'akurasi_gps' => 12,
        'zona_id' => $savana->id,
        'jenis_temuan' => 'asap_tebal',
        'catatan' => 'Asap hitam pekat mengepul dari arah tebing savana.',
    ]);

    $response->assertSessionHas('tiket_berhasil');

    $this->assertDatabaseHas('laporan_asap', [
        'zona_id' => $savana->id,
        'jenis_temuan' => 'asap_tebal',
        'status_tiket' => 'baru',
    ]);

    $laporan = LaporanAsap::latest('id')->first();
    expect($laporan->nomor_tiket)->toMatch('/^#LP-\d{4}$/');
});

test('pembatasan laju (rate limiting) memblokir laporan ke-4 dalam 15 menit', function () {
    $savana = Zona::where('kode', 'BRM-04')->first();

    // Kirim 3 laporan valid
    for ($i = 0; $i < 3; $i++) {
        $this->post('/lapor', [
            'latitude' => -7.9650000,
            'longitude' => 112.9680000,
            'zona_id' => $savana->id,
            'jenis_temuan' => 'asap_tipis',
        ])->assertSessionHas('tiket_berhasil');
    }

    // Laporan ke-4 harus ditolak dengan validasi rate limiting
    $response = $this->post('/lapor', [
        'latitude' => -7.9650000,
        'longitude' => 112.9680000,
        'zona_id' => $savana->id,
        'jenis_temuan' => 'asap_tipis',
    ]);

    $response->assertSessionHasErrors('laporan');
});

test('layanan kompresi foto mengompresi gambar berukuran besar ke maksimal 1200 px', function () {
    $layanan = app(LayananKompresiFoto::class);

    // Buat dummy foto resolusi 2400 x 1600 px (melebihi batas 1200 px)
    $fotoBesar = UploadedFile::fake()->image('uji_kebakaran.jpg', 2400, 1600);

    $pathHasil = $layanan->kompresDanSimpan($fotoBesar, 'laporan', 1200, 80);

    expect($pathHasil)->toBeString();
    Storage::disk('public')->assertExists($pathHasil);

    // Verifikasi dimensi foto hasil kompresi di disk
    $gambarKonten = Storage::disk('public')->get($pathHasil);
    $info = getimagesizefromstring($gambarKonten);

    expect($info[0])->toBeLessThanOrEqual(1200)
        ->and($info[1])->toBeLessThanOrEqual(1200);
});

test('middleware RBAC memblokir tamu dan pengguna umum dari dasbor petugas', function () {
    // 1. Tamu belum login diarahkan ke rute login
    $responseTamu = $this->get('/petugas');
    $responseTamu->assertRedirect(route('login'));

    // 2. Pengguna publik (wisatawan) mendapat respons 403 ramah
    $warga = Pengguna::factory()->create([
        'nama' => 'Budi Santoso',
        'email' => 'budi@warga.id',
        'peran' => 'publik',
        'password' => Hash::make('password123'),
    ]);

    $responsePublik = $this->actingAs($warga)->get('/petugas');
    $responsePublik->assertStatus(403)
        ->assertSee('Akses Khusus Petugas')
        ->assertSee('Halaman ini hanya untuk petugas');
});

test('petugas terautentikasi dapat mengakses antrean tiket dan memverifikasi temuan', function () {
    $petugas = Pengguna::where('email', 'agus@tnbts.go.id')->first();
    $savana = Zona::where('kode', 'BRM-04')->first();

    // Buat tiket baru untuk diuji
    $tiket = LaporanAsap::create([
        'nomor_tiket' => '#LP-0299',
        'zona_id' => $savana->id,
        'latitude' => -7.9650000,
        'longitude' => 112.9680000,
        'jenis_temuan' => 'asap_tebal',
        'status_tiket' => 'baru',
    ]);

    // 1. Petugas mengakses /petugas
    $response = $this->actingAs($petugas)->get('/petugas');
    $response->assertOk()
        ->assertSee('DASBOR PETUGAS LAPANGAN TNBTS')
        ->assertSee('Antrean Verifikasi');

    // 2. Aksi Menuju Lokasi
    $this->actingAs($petugas)
        ->post(route('petugas.menuju-lokasi', $tiket->nomor_tiket))
        ->assertSessionHas('pesan');

    expect($tiket->fresh()->status_tiket)->toBe('menuju_lokasi');

    // 3. Aksi Verifikasi API NYATA
    $this->actingAs($petugas)
        ->post(route('petugas.verifikasi', $tiket->nomor_tiket), [
            'hasil_verifikasi' => 'api_nyata',
            'catatan' => 'Api membakar ilalang kering di sisi barat, angin 20 km/j.',
        ])
        ->assertSessionHas('pesan');

    $tiketSegar = $tiket->fresh();
    expect($tiketSegar->status_tiket)->toBe('terverifikasi')
        ->and($tiketSegar->hasil_verifikasi)->toBe('api_nyata');

    // Pastikan log siaran darurat 3 km tercatat
    $this->assertDatabaseHas('log_siaran', [
        'jenis_siaran' => 'titik_api',
        'zona_id' => $tiketSegar->zona_id,
    ]);

    // 4. Aksi Tandai Ditangani
    $this->actingAs($petugas)
        ->post(route('petugas.tandai-ditangani', $tiket->nomor_tiket));

    expect($tiket->fresh()->status_tiket)->toBe('ditangani');
});

test('pengguna dapat mendaftar sebagai petugas lapangan dan langsung terautentikasi', function () {
    $response = $this->post(route('daftar-petugas.post'), [
        'nama' => 'Bambang Irawan (Polhut)',
        'email' => 'bambang@tnbts.go.id',
        'nomor_telepon' => '081299887766',
        'password' => 'petugas123',
        'password_confirmation' => 'petugas123',
    ]);

    $response->assertRedirect('/petugas');

    $this->assertDatabaseHas('pengguna', [
        'email' => 'bambang@tnbts.go.id',
        'peran' => 'petugas',
    ]);

    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->email)->toBe('bambang@tnbts.go.id');
});

test('petugas dapat masuk menggunakan email dan keluar dari sistem', function () {
    // 1. Masuk
    $responseMasuk = $this->post(route('masuk.post'), [
        'login' => 'agus@tnbts.go.id',
        'password' => 'password',
    ]);

    $responseMasuk->assertRedirect('/petugas');
    expect(Auth::id())->not->toBeNull();

    // 2. Keluar
    $responseKeluar = $this->post(route('keluar'));
    $responseKeluar->assertRedirect('/');
    expect(Auth::check())->toBeFalse();
});

test('google oauth redirect memberikan peringatan ramah saat kredensial env belum dikonfigurasi', function () {
    config(['services.google.client_id' => '[MASUKKAN CLIENT ID DI .ENV]']);

    $response = $this->get(route('auth.google'));

    $response->assertRedirect(route('masuk'))
        ->assertSessionHas('peringatan_google');
});

test('laporan baru ditempelkan ke desa yang memuat titik dan memicu pesan Telegram ke petugas', function () {
    $this->seed(Database\Seeders\DatabaseSeeder::class);
    $this->artisan('app:import-dataset-desa')->assertSuccessful();

    config(['services.telegram.bot_token' => 'token-uji', 'services.telegram.officer_group_id' => '-100123']);
    Illuminate\Support\Facades\Http::fake(['api.telegram.org/*' => Illuminate\Support\Facades\Http::response(['ok' => true])]);

    $desa = App\Models\Zona::desa()->where('nama', 'Sariwani')->first();
    $titik = $desa->poligon_geojson;
    $ring = ($titik['type'] === 'MultiPolygon' ? $titik['coordinates'][0][0] : $titik['coordinates'][0]);
    $lng = array_sum(array_column($ring, 0)) / count($ring);
    $lat = array_sum(array_column($ring, 1)) / count($ring);
    $dalam = App\Models\Zona::cariDesaDi($lat, $lng);

    $this->postJson('/lapor', ['latitude' => $lat, 'longitude' => $lng, 'jenis_temuan' => 'asap_tipis'])
        ->assertCreated();

    $laporan = App\Models\LaporanAsap::latest('id')->first();
    expect($laporan->zona_id)->toBe($dalam->id)
        ->and($laporan->zona->data_analisis)->not->toBeNull();

    Illuminate\Support\Facades\Http::assertSent(fn ($req) => str_contains($req->url(), '/sendMessage')
        && $req['chat_id'] === '-100123'
        && str_contains($req['text'], $laporan->nomor_tiket));
});
