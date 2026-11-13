<?php

use App\Models\SumberAir;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function csvLogistik(string $isi): string
{
    $path = sys_get_temp_dir().'/logistik_'.uniqid().'.csv';
    file_put_contents($path, $isi);

    return $path;
}

beforeEach(function () {
    $this->seed(Database\Seeders\DatabaseSeeder::class);
    $this->artisan('app:import-dataset-desa')->assertSuccessful();
});

test('impor logistik menyimpan baris valid, menolak yang salah, dan tidak menggandakan', function () {
    $csv = csvLogistik("nama;jenis;latitude;longitude;kapasitas;status;sumber_data;tanggal_verifikasi;kontak\n"
        ."Embung Uji;embung;-7,9650;112,9680;25.000 L;Siaga;BBTNBTS;2026-11-20;Pak Budi\n"
        ."Salah Koordinat;embung;7.96;112.9;;;BBTNBTS;;\n"
        ."Tanpa Sumber;pos_jaga;-7.95;112.95;;;;;\n"
        ."CONTOH - hapus;embung;-7.95;112.95;;;BBTNBTS;;\n");

    $awal = SumberAir::count();

    $this->artisan('app:import-logistik', ['file' => $csv, '--dry-run' => true])->assertFailed();
    expect(SumberAir::count())->toBe($awal);

    $this->artisan('app:import-logistik', ['file' => $csv])->assertFailed();
    $this->artisan('app:import-logistik', ['file' => $csv])->assertFailed();

    expect(SumberAir::count())->toBe($awal + 1);

    $titik = SumberAir::where('nama', 'Embung Uji')->first();
    expect($titik->adalah_contoh)->toBeFalse()
        ->and($titik->sumber_data)->toBe('BBTNBTS')
        ->and($titik->zona->data_analisis)->not->toBeNull();
});

test('titik lama tanpa sumber data dianggap contoh dan bisa dihapus dengan --hapus-contoh', function () {
    expect(SumberAir::all()->every(fn ($s) => $s->adalah_contoh))->toBeTrue();

    $csv = csvLogistik("nama,jenis,latitude,longitude,sumber_data\nEmbung Nyata,embung,-7.9650,112.9680,BPBD\n");
    $this->artisan('app:import-logistik', ['file' => $csv, '--hapus-contoh' => true])->assertSuccessful();

    expect(SumberAir::count())->toBe(1)->and(SumberAir::first()->nama)->toBe('Embung Nyata');
});

test('titik terdekat dihitung dari koordinat dan halaman evakuasi menandai data contoh', function () {
    $hasil = SumberAir::terdekat(-7.9650, 112.9680, ['embung', 'sumber_air']);

    expect($hasil)->not->toBeNull()->and($hasil['jarak_km'])->toBeFloat();

    $this->get('/evakuasi')->assertOk()
        ->assertSee('Terdekat dari lokasimu')
        ->assertSee('Data contoh');
});

test('pesan Telegram laporan baru memuat air dan pos terdekat dengan tanda data contoh', function () {
    config(['services.telegram.bot_token' => 'token-uji', 'services.telegram.officer_group_id' => '-100123']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    $desa = Zona::desa()->first();
    $this->postJson('/lapor', ['latitude' => $desa->titik_tengah_lat, 'longitude' => $desa->titik_tengah_lng, 'jenis_temuan' => 'asap_tebal'])
        ->assertCreated();

    Http::assertSent(fn ($req) => str_contains($req->url(), '/sendMessage')
        && str_contains($req['text'], 'Air terdekat')
        && str_contains($req['text'], 'data contoh'));
});

test('halaman status laporan memakai kode acak dan tidak membocorkan koordinat', function () {
    config(['services.telegram.officer_group_id' => null]);

    $desa = Zona::desa()->first();
    $respons = $this->post('/lapor', ['latitude' => $desa->titik_tengah_lat, 'longitude' => $desa->titik_tengah_lng, 'jenis_temuan' => 'api_terlihat']);
    $respons->assertRedirect('/lapor')->assertSessionHas('status_url');

    $laporan = App\Models\LaporanAsap::latest('id')->first();
    expect($laporan->kode_pantau)->toHaveLength(12);

    $halaman = $this->get(route('lapor.status', $laporan->kode_pantau));
    $halaman->assertOk()
        ->assertSee($laporan->nomor_tiket)
        ->assertSee('Laporan diterima')
        ->assertDontSee((string) $laporan->latitude);

    // Nomor tiket berurutan tidak boleh bisa dipakai sebagai kunci tautan.
    $this->get('/lapor/status/'.ltrim($laporan->nomor_tiket, '#'))->assertNotFound();
    $this->get('/lapor/status/kodesalah123')->assertNotFound();

    // kode_pantau tidak ikut dalam JSON.
    expect($laporan->toArray())->not->toHaveKey('kode_pantau');

    $laporan->update(['status_tiket' => 'selesai', 'hasil_verifikasi' => 'bukan_api']);
    $this->get(route('lapor.status', $laporan->kode_pantau))->assertSee('bukan api');
});

test('formulir lapor tidak memilih jenis temuan secara bawaan dan memuat kotak 112', function () {
    $this->get('/lapor')->assertOk()
        ->assertSee('telepon', false)
        ->assertSee('Apa yang kamu lihat?');
});
