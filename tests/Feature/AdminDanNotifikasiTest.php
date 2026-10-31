<?php

use App\Models\LaporanAsap;
use App\Models\Pengguna;
use App\Models\Zona;
use App\Services\LayananBotTelegram;
use App\Services\LayananNotifikasiPush;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('layanan bot telegram memformat pesan baku sesuai struktur §14.1 design.md', function () {
    $telegram = app(LayananBotTelegram::class);
    $savana = Zona::where('kode', 'BRM-04')->first();

    // 1. Pesan Waspada
    $pesanWaspada = $telegram->formatPesan('waspada', $savana, ['cdd' => 11, 'fdi' => 62, 'angin' => 19]);
    expect($pesanWaspada)->toContain('WASPADA — Savana & Jemplang (BRM-04)')
        ->and($pesanWaspada)->toContain('Hari ke-11 tanpa hujan. FDI 62/100, angin 19 km/j.')
        ->and($pesanWaspada)->toContain('Jangan membakar sampah. Padamkan puntung rokok.')
        ->and($pesanWaspada)->toContain('Darurat: 112');

    // 2. Pesan Siaga 1
    $pesanSiaga = $telegram->formatPesan('siaga_1', $savana, ['cdd' => 19, 'fdi' => 86, 'angin' => 28]);
    expect($pesanSiaga)->toContain('SIAGA 1 — Savana & Jemplang (BRM-04)')
        ->and($pesanSiaga)->toContain('Hari ke-19 tanpa hujan. FDI 86/100. Angin 28 km/j.')
        ->and($pesanSiaga)->toContain('Dilarang keras membuat api terbuka')
        ->and($pesanSiaga)->toContain('Darurat: 112');

    // 3. Pesan Titik Api 3 km
    $pesanApi = $telegram->formatPesan('titik_api', $savana, ['jarak' => '1,8', 'arah' => 'barat daya', 'arah_angin' => 'barat laut']);
    expect($pesanApi)->toContain('Asap terverifikasi 1,8 km arah barat daya.')
        ->and($pesanApi)->toContain('Regu menuju lokasi. Angin ke barat laut.')
        ->and($pesanApi)->toContain('Jauhi area Savana & Jemplang. Ikuti petugas.');

    // 4. Pesan Pencabutan
    $pesanCabut = $telegram->formatPesan('pencabutan', $savana, ['fdi' => 41]);
    expect($pesanCabut)->toContain('SIAGA 1 DICABUT — Savana & Jemplang')
        ->and($pesanCabut)->toContain('FDI turun ke 41/100 setelah hujan.')
        ->and($pesanCabut)->toContain('Tetap jaga kebersihan lahan');
});

test('webhook telegram dapat menautkan akun dengan perintah /start KODE dan memproses callback tiket', function () {
    $agus = Pengguna::where('email', 'agus@tnbts.go.id')->first();
    $kode = $agus->kode_tautan_telegram;

    // 1. Kirim Webhook Telegram /start [KODE]
    $response = $this->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10001,
        'message' => [
            'message_id' => 1,
            'chat' => ['id' => 987654321],
            'text' => "/start {$kode}",
        ],
    ]);

    $response->assertOk()
        ->assertJson(['ok' => true, 'result' => ['status' => 'connected']]);

    expect($agus->fresh()->telegram_chat_id)->toBe('987654321');

    // 2. Kirim Webhook Callback Query Verifikasi Tiket Inline
    $laporan = LaporanAsap::where('nomor_tiket', '#LP-0231')->first();

    $responseCallback = $this->postJson('/api/v1/telegram/webhook', [
        'update_id' => 10002,
        'callback_query' => [
            'id' => 'cb-12345',
            'data' => "tiket:menuju:{$laporan->nomor_tiket}",
            'message' => ['chat' => ['id' => 987654321]],
        ],
    ]);

    $responseCallback->assertOk()
        ->assertJson(['ok' => true, 'result' => ['status' => 'callback_processed']]);

    expect($laporan->fresh()->status_tiket)->toBe('menuju_lokasi');
});

test('api langganan web push mendaftarkan endpoint browser dan mencatat siaran', function () {
    $savana = Zona::where('kode', 'BRM-04')->first();

    // 1. Mendaftarkan langganan push
    $response = $this->postJson('/api/v1/notifications/subscribe', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/fake-subscription-endpoint',
        'public_key' => 'BPFakePublicKey1234567890',
        'auth_token' => 'AuthTokenFake123',
        'zona_id' => $savana->id,
    ]);

    $response->assertCreated()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('langganan_push', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/fake-subscription-endpoint',
        'zona_id' => $savana->id,
        'aktif' => true,
    ]);

    // 2. Kirim siaran push via LayananNotifikasiPush
    $pushService = app(LayananNotifikasiPush::class);
    $penerima = $pushService->kirimSiaranPush(
        'Uji Siaran Darurat',
        'Peringatan bahaya api savana.',
        $savana->id,
        null,
        'waspada'
    );

    expect($penerima)->toBeGreaterThanOrEqual(1);

    $this->assertDatabaseHas('log_siaran', [
        'kanal' => 'web_push',
        'zona_id' => $savana->id,
    ]);
});

test('admin dapat mengakses seluruh layar otoritas bpbd dan non-admin diblokir', function () {
    $admin = Pengguna::where('email', 'admin@bpbd.go.id')->first();
    $petugas = Pengguna::where('email', 'agus@tnbts.go.id')->first();

    // 1. Non-admin diblokir dengan 403 ramah
    $this->actingAs($petugas)->get('/admin/tinjauan')
        ->assertStatus(403)
        ->assertSee('Akses Khusus Petugas')
        ->assertSee('Halaman ini hanya untuk petugas');

    // 2. Admin dapat mengakses seluruh layar
    $this->actingAs($admin)->get('/admin/tinjauan')->assertOk()->assertSee('Tinjauan Prediksi Risiko');
    $this->actingAs($admin)->get('/admin/siaga')->assertOk()->assertSee('Persetujuan Status Siaga 1');
    $this->actingAs($admin)->get('/admin/siaran')->assertOk()->assertSee('Editor Siaran Multi-Kanal');
    $this->actingAs($admin)->get('/admin/log-siaran')->assertOk()->assertSee('Log Siaran');
    $this->actingAs($admin)->get('/admin/laporan')->assertOk()->assertSee('Manajemen Laporan Lapangan');
});

test('alur persetujuan siaga 1 dua langkah mengubah status dan merekam jejak audit resmi', function () {
    $admin = Pengguna::where('email', 'admin@bpbd.go.id')->first();
    $savana = Zona::where('kode', 'BRM-04')->first();

    // 1. Setujui Siaga 1
    $response = $this->actingAs($admin)->post(route('admin.siaga.setujui', $savana->id), [
        'catatan_persetujuan' => 'Kondisi kemarau ekstrem CDD 19 hari, Siaga 1 resmi diberlakukan.',
    ]);

    $response->assertRedirect(route('admin.siaga'))
        ->assertSessionHas('pesan');

    $this->assertDatabaseHas('status_siaga', [
        'zona_id' => $savana->id,
        'status' => 'SIAGA_1',
        'disetujui_oleh' => $admin->id,
    ]);

    // Pastikan log siaran tercatat
    $this->assertDatabaseHas('log_siaran', [
        'jenis_siaran' => 'siaga_1',
        'zona_id' => $savana->id,
    ]);

    // 2. Cabut Siaga 1
    $responseCabut = $this->actingAs($admin)->post(route('admin.siaga.cabut', $savana->id), [
        'catatan_pencabutan' => 'Hujan lebat 20 mm telah membasahi savana, status Siaga 1 dicabut.',
    ]);

    $responseCabut->assertRedirect(route('admin.siaga'))
        ->assertSessionHas('pesan');

    $this->assertDatabaseHas('status_siaga', [
        'zona_id' => $savana->id,
        'status' => 'DICABUT',
        'dicabut_oleh' => $admin->id,
    ]);
});

test('fitur ekspor csv menghasilkan unduhan berkas stream log siaran dengan header yang valid', function () {
    $admin = Pengguna::where('email', 'admin@bpbd.go.id')->first();

    $response = $this->actingAs($admin)->get(route('admin.log-siaran.ekspor'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('Jenis Siaran')
        ->and($content)->toContain('Kanal Pengiriman')
        ->and($content)->toContain('Savana & Jemplang');
});

test('sakelar global mode demo dapat diaktifkan dan dinonaktifkan', function () {
    $admin = Pengguna::where('email', 'admin@bpbd.go.id')->first();

    // 1. Toggle ON
    $this->actingAs($admin)->post(route('admin.toggle-demo'))
        ->assertSessionHas('mode_demo', true);

    // 2. Toggle OFF
    $this->actingAs($admin)->post(route('admin.toggle-demo'))
        ->assertSessionHas('mode_demo', false);
});
