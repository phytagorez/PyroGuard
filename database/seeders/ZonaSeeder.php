<?php

namespace Database\Seeders;

use App\Models\Zona;
use Illuminate\Database\Seeder;

class ZonaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zonas = [
            [
                'kode' => 'BRM-01',
                'nama' => 'Cemoro Lawang & Ngadisari',
                'deskripsi_lokasi' => 'Tepi kaldera utara/timur laut, permukiman warga Tengger, deretan homestay, dan pintu masuk utama wisatawan dari arah Probolinggo.',
                'jenis_lahan' => 'Tepi kaldera, permukiman & kawasan wisata',
                'akses_wisata' => 'Sangat Tinggi',
                'titik_tengah_lat' => -7.9234000,
                'titik_tengah_lng' => 112.9625000,
                'poligon_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [112.9550, -7.9150],
                        [112.9750, -7.9160],
                        [112.9780, -7.9320],
                        [112.9580, -7.9310],
                        [112.9550, -7.9150],
                    ]],
                ],
            ],
            [
                'kode' => 'BRM-02',
                'nama' => 'Wonokitri & Penanjakan',
                'deskripsi_lokasi' => 'Lereng barat laut kaldera, titik pandang utama sunrise (Penanjakan 1, Bukit Cinta, Bukit Kingkong), vegetasi cemara gunung dan jurang terjal.',
                'jenis_lahan' => 'Lereng perbukitan, hutan cemara & semak',
                'akses_wisata' => 'Tinggi',
                'titik_tengah_lat' => -7.9015000,
                'titik_tengah_lng' => 112.9380000,
                'poligon_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [112.9250, -7.8900],
                        [112.9500, -7.8920],
                        [112.9520, -7.9140],
                        [112.9260, -7.9120],
                        [112.9250, -7.8900],
                    ]],
                ],
            ],
            [
                'kode' => 'BRM-03',
                'nama' => 'Lautan Pasir & Kaldera',
                'deskripsi_lokasi' => 'Dasar kaldera pasir vulkanik luas, kawah aktif Gunung Bromo, Gunung Batok, dan Pura Luhur Poten. Sangat kering dan berdebu saat angin kencang.',
                'jenis_lahan' => 'Pasir vulkanik, vegetasi jarang & rumput liar',
                'akses_wisata' => 'Sangat Tinggi',
                'titik_tengah_lat' => -7.9420000,
                'titik_tengah_lng' => 112.9530000,
                'poligon_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [112.9350, -7.9250],
                        [112.9720, -7.9260],
                        [112.9700, -7.9550],
                        [112.9360, -7.9530],
                        [112.9350, -7.9250],
                    ]],
                ],
            ],
            [
                'kode' => 'BRM-04',
                'nama' => 'Savana & Jemplang',
                'deskripsi_lokasi' => 'Lembah savana padang rumput (Bukit Teletubbies) hingga persimpangan Jemplang. Memiliki biomassa alang-alang dan pakis kering dengan indeks kerentanan kebakaran tertinggi.',
                'jenis_lahan' => 'Padang rumput savana, semak belukar & pakis kering',
                'akses_wisata' => 'Tinggi (Spot Foto & Tur Jeep)',
                'titik_tengah_lat' => -7.9710000,
                'titik_tengah_lng' => 112.9645000,
                'poligon_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [112.9450, -7.9560],
                        [112.9850, -7.9580],
                        [112.9820, -7.9860],
                        [112.9460, -7.9840],
                        [112.9450, -7.9560],
                    ]],
                ],
            ],
            [
                'kode' => 'BRM-05',
                'nama' => 'Ngadas & Poncokusumo',
                'deskripsi_lokasi' => 'Pintu masuk sisi barat daya dari Malang. Meliputi Desa Adat Ngadas, ladang hortikultura warga Tengger, serta bentang hutan bambu dan cemara.',
                'jenis_lahan' => 'Permukiman adat, ladang pertanian & hutan lindung',
                'akses_wisata' => 'Sedang',
                'titik_tengah_lat' => -7.9890000,
                'titik_tengah_lng' => 112.9080000,
                'poligon_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [112.8900, -7.9750],
                        [112.9300, -7.9760],
                        [112.9280, -8.0050],
                        [112.8920, -8.0030],
                        [112.8900, -7.9750],
                    ]],
                ],
            ],
            [
                'kode' => 'BRM-06',
                'nama' => 'Ranu Pani & Senduro',
                'deskripsi_lokasi' => 'Kawasan selatan menuju Lumajang, pos pendakian Gunung Semeru, Danau Ranu Pani & Ranu Regulo, vegetasi hutan pegunungan basah.',
                'jenis_lahan' => 'Hutan pegunungan basah, danau & pos konservasi',
                'akses_wisata' => 'Sedang (Pendakian & Konservasi)',
                'titik_tengah_lat' => -8.0120000,
                'titik_tengah_lng' => 112.9490000,
                'poligon_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [112.9320, -7.9950],
                        [112.9700, -7.9960],
                        [112.9680, -8.0280],
                        [112.9340, -8.0260],
                        [112.9320, -7.9950],
                    ]],
                ],
            ],
        ];

        foreach ($zonas as $data) {
            Zona::updateOrCreate(['kode' => $data['kode']], $data);
        }
    }
}
