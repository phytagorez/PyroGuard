<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function csvAnalisisBromo(array $ganti = []): UploadedFile
{
    $row = array_replace([
        'NDVI_sebelum' => '0.6', 'NDVI_sesudah' => '0.2',
        'NDMI_sebelum' => '0.3', 'NDMI_sesudah' => '',
        'NBR_sebelum' => '0.7', 'NBR_sesudah' => '-0.1',
        'Hujan_Harian' => '0', 'Hujan_7_Hari' => '2.5',
        'Suhu_Permukaan' => '28.5', 'Jumlah_Hotspot_Piksel' => '12',
        'Longitude' => '112.9530', 'Latitude' => '-7.9425',
        'Tanggal_Kebakaran' => '2023-08-29', 'Lokasi' => 'Bantengan',
    ], $ganti);

    return UploadedFile::fake()->createWithContent('dataset.csv', implode(',', array_keys($row))."\n".implode(',', $row)."\n");
}

function poligonWilayahBromo(): array
{
    return [
        'type' => 'FeatureCollection',
        'features' => [[
            'type' => 'Feature', 'properties' => ['nama' => 'Wilayah pengujian'],
            'geometry' => ['type' => 'Polygon', 'coordinates' => [[[112.94, -7.94], [112.97, -7.94], [112.97, -7.97], [112.94, -7.94]]]],
        ]],
    ];
}

function unggahanWilayahBromo(array $geojson): UploadedFile
{
    return UploadedFile::fake()->createWithContent('wilayah.geojson', json_encode($geojson, JSON_THROW_ON_ERROR));
}

test('metadata publik membedakan administrasi lampiran dari wilayah analisis yang belum diimpor', function () {
    $this->getJson('/api/v1/geospatial')->assertOk()
        ->assertJsonPath('data.jumlah_fitur', 10)
        ->assertJsonPath('data.bukan_batas_tnbts', true)
        ->assertJsonPath('data.crs', 'EPSG:4326')
        ->assertJsonPath('tautan.wilayah', null)
        ->assertJsonPath('analisis', null);

    $this->getJson('/api/v1/geospatial/area')->assertNotFound();
});

test('batas administrasi dilayani sebagai geometri multipoligon asli dengan ring tertutup', function () {
    $response = $this->get('/api/v1/geospatial/boundaries');

    $response->assertOk()->assertHeader('Content-Type', 'application/geo+json; charset=UTF-8');
    $data = json_decode(file_get_contents($response->baseResponse->getFile()->getPathname()), true, flags: JSON_THROW_ON_ERROR);
    expect($data['features'])->toHaveCount(10);
    expect(array_column(array_column($data['features'], 'properties'), 'WADMKK'))->toContain('Malang', 'Lumajang', 'Probolinggo', 'Pasuruan');
    foreach ($data['features'] as $feature) {
        expect($feature['geometry']['type'])->toBe('MultiPolygon');
        foreach ($feature['geometry']['coordinates'] as $polygon) {
            foreach ($polygon as $ring) {
                expect($ring[0])->toBe($ring[array_key_last($ring)]);
            }
        }
    }
});

test('impor dataset hanya dapat diakses admin', function () {
    $this->get('/admin/dataset')->assertRedirect(route('login'));
    $this->post('/admin/dataset')->assertRedirect(route('login'));

    $this->actingAs(Pengguna::factory()->petugas()->create());
    $this->get('/admin/dataset')->assertForbidden();
    $this->post('/admin/dataset', ['dataset_csv' => csvAnalisisBromo()])->assertForbidden();
    Storage::disk('local')->assertMissing('geospatial/dataset.json');
});

test('admin dapat mengimpor poligon dan hasil historis tanpa mengubah nilai kosong menjadi nol', function () {
    $this->actingAs(Pengguna::factory()->admin()->create());
    $this->get('/admin/dataset')->assertOk()->assertSee('Impor hasil analisis');

    $this->post('/admin/dataset', [
        'dataset_csv' => csvAnalisisBromo(),
        'wilayah_geojson' => unggahanWilayahBromo(poligonWilayahBromo()),
    ])->assertRedirect(route('admin.dataset'))->assertSessionHasNoErrors();

    $this->getJson('/api/v1/geospatial')->assertOk()
        ->assertJsonPath('analisis.baris.0.Lokasi', 'Bantengan')
        ->assertJsonPath('analisis.baris.0.NDMI_sesudah', null)
        ->assertJsonPath('analisis.baris.0.Hujan_Harian', 0)
        ->assertJsonPath('analisis.baris.0.Tanggal_Kebakaran', '2023-08-29')
        ->assertJsonPath('tautan.wilayah', route('api.dataset.wilayah'));
    $this->getJson('/api/v1/geospatial/area')->assertOk()
        ->assertJsonPath('features.0.geometry.type', 'Polygon')
        ->assertJsonPath('features.0.properties.nama', 'Wilayah pengujian');
});

test('mengimpor ulang csv mempertahankan poligon yang sudah ada', function () {
    $this->actingAs(Pengguna::factory()->admin()->create());
    $this->post('/admin/dataset', ['wilayah_geojson' => unggahanWilayahBromo(poligonWilayahBromo())])->assertSessionHasNoErrors();

    $this->post('/admin/dataset', ['dataset_csv' => csvAnalisisBromo()])->assertSessionHasNoErrors();

    $this->getJson('/api/v1/geospatial/area')->assertOk()->assertJsonPath('features.0.properties.nama', 'Wilayah pengujian');
});

test('csv dengan nilai tidak valid ditolak tanpa mengubah dataset sebelumnya', function (array $nilai) {
    $this->actingAs(Pengguna::factory()->admin()->create());
    $this->post('/admin/dataset', ['dataset_csv' => csvAnalisisBromo()])->assertSessionHasNoErrors();
    $sebelumnya = Storage::disk('local')->get('geospatial/dataset.json');

    $this->post('/admin/dataset', ['dataset_csv' => csvAnalisisBromo($nilai)])->assertSessionHasErrors('dataset_csv');

    expect(Storage::disk('local')->get('geospatial/dataset.json'))->toBe($sebelumnya);
})->with([
    'indeks di luar rentang' => [['NDVI_sebelum' => '1.5']],
    'hujan negatif' => [['Hujan_Harian' => '-1']],
    'tanggal tidak valid' => [['Tanggal_Kebakaran' => '2023-02-30']],
    'koordinat bukan angka' => [['Longitude' => 'abc']],
    'deteksi piksel pecahan' => [['Jumlah_Hotspot_Piksel' => '2.5']],
]);

test('impor menolak file kosong dan csv dengan kolom berbeda', function () {
    $this->actingAs(Pengguna::factory()->admin()->create());

    $this->post('/admin/dataset')->assertSessionHasErrors(['dataset_csv', 'wilayah_geojson']);
    $this->post('/admin/dataset', ['dataset_csv' => UploadedFile::fake()->createWithContent('salah.csv', "Lokasi,Nilai\nBromo,9\n")])->assertSessionHasErrors('dataset_csv');

    Storage::disk('local')->assertMissing('geospatial/dataset.json');
});

test('geometri point tidak dapat menggantikan wilayah poligon', function () {
    $this->actingAs(Pengguna::factory()->admin()->create());
    $wilayah = poligonWilayahBromo();
    $wilayah['features'][0]['geometry'] = ['type' => 'Point', 'coordinates' => [112.953, -7.9425]];

    $this->post('/admin/dataset', ['dataset_csv' => csvAnalisisBromo(), 'wilayah_geojson' => unggahanWilayahBromo($wilayah)])->assertSessionHasErrors('wilayah_geojson');

    Storage::disk('local')->assertMissing('geospatial/dataset.json');
});

test('impor menolak ring terbuka koordinat proyeksi dan json rusak', function () {
    $this->actingAs(Pengguna::factory()->admin()->create());
    $wilayah = poligonWilayahBromo();
    $wilayah['features'][0]['geometry']['coordinates'][0][3] = [112.95, -7.95];
    $this->post('/admin/dataset', ['wilayah_geojson' => unggahanWilayahBromo($wilayah)])->assertSessionHasErrors('wilayah_geojson');

    $wilayah = poligonWilayahBromo();
    $wilayah['features'][0]['geometry']['coordinates'][0][1] = [700000, 9000000];
    $this->post('/admin/dataset', ['wilayah_geojson' => unggahanWilayahBromo($wilayah)])->assertSessionHasErrors('wilayah_geojson');
    $this->post('/admin/dataset', ['wilayah_geojson' => UploadedFile::fake()->createWithContent('rusak.geojson', '{rusak}')])->assertSessionHasErrors('wilayah_geojson');

    Storage::disk('local')->assertMissing('geospatial/dataset.json');
});
