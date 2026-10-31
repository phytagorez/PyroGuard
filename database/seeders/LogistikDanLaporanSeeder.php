<?php

namespace Database\Seeders;

use App\Models\LanggananPush;
use App\Models\LaporanAsap;
use App\Models\LogSiaran;
use App\Models\Pengguna;
use App\Models\SumberAir;
use App\Models\TitikPanas;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LogistikDanLaporanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zonas = Zona::all()->keyBy('kode');
        $admin = Pengguna::where('email', 'admin@bpbd.go.id')->first();
        $petugas = Pengguna::where('email', 'agus@tnbts.go.id')->first();
        $wisatawan = Pengguna::where('email', 'rina@wisatawan.id')->first();

        // ---------------------------------------------------------------------
        // 1. Titik Panas Satelit FIRMS (72 Jam Terakhir)
        // ---------------------------------------------------------------------
        $savana = $zonas['BRM-04'];
        $kaldera = $zonas['BRM-03'];

        TitikPanas::updateOrCreate(
            ['latitude' => -7.9720000, 'longitude' => 112.9660000],
            [
                'zona_id' => $savana->id,
                'kecerahan' => 348.50, // Suhu kecerahan Kelvin (panas tinggi)
                'sumber_satelit' => 'FIRMS VIIRS',
                'terdeteksi_pada' => Carbon::now()->subHours(6),
                'sumber' => 'aktual',
            ]
        );

        TitikPanas::updateOrCreate(
            ['latitude' => -7.9680000, 'longitude' => 112.9610000],
            [
                'zona_id' => $savana->id,
                'kecerahan' => 325.20,
                'sumber_satelit' => 'FIRMS MODIS',
                'terdeteksi_pada' => Carbon::now()->subHours(18),
                'sumber' => 'aktual',
            ]
        );

        TitikPanas::updateOrCreate(
            ['latitude' => -7.9410000, 'longitude' => 112.9500000],
            [
                'zona_id' => $kaldera->id,
                'kecerahan' => 312.00,
                'sumber_satelit' => 'FIRMS MODIS',
                'terdeteksi_pada' => Carbon::now()->subHours(36),
                'sumber' => 'aktual',
            ]
        );

        // ---------------------------------------------------------------------
        // 2. Sumber Air, Embung & Jalur Evakuasi
        // ---------------------------------------------------------------------
        $sumberAirs = [
            [
                'zona_id' => $savana->id,
                'nama' => 'Embung Savana Blok Jemplang',
                'jenis' => 'embung',
                'latitude' => -7.9650000,
                'longitude' => 112.9680000,
                'debit_air' => 'Kapasitas 25.000 Liter',
                'status_siaga' => 'Siaga & Terisi Penuh',
                'jalur_evakuasi_geojson' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [112.9680, -7.9650],
                        [112.9640, -7.9710],
                        [112.9600, -7.9780],
                    ],
                ],
            ],
            [
                'zona_id' => $zonas['BRM-01']->id,
                'nama' => 'Sumber Mata Air Banyu Biru Cemoro Lawang',
                'jenis' => 'sumber_air',
                'latitude' => -7.9210000,
                'longitude' => 112.9600000,
                'debit_air' => '150 Liter/menit',
                'status_siaga' => 'Aktif 24 Jam',
                'jalur_evakuasi_geojson' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [112.9600, -7.9210],
                        [112.9620, -7.9240],
                        [112.9650, -7.9280],
                    ],
                ],
            ],
            [
                'zona_id' => $zonas['BRM-02']->id,
                'nama' => 'Pos Pantau & Embung Wonokitri',
                'jenis' => 'pos_jaga',
                'latitude' => -7.8980000,
                'longitude' => 112.9350000,
                'debit_air' => 'Kapasitas 12.000 Liter',
                'status_siaga' => 'Siaga Regu Pemadam',
                'jalur_evakuasi_geojson' => null,
            ],
            [
                'zona_id' => $zonas['BRM-03']->id,
                'nama' => 'Titik Kumpul Lapangan Pura Poten',
                'jenis' => 'titik_kumpul',
                'latitude' => -7.9400000,
                'longitude' => 112.9520000,
                'debit_air' => 'Tandon Darurat 5.000 L',
                'status_siaga' => 'Zona Terbuka Aman',
                'jalur_evakuasi_geojson' => null,
            ],
            [
                'zona_id' => $zonas['BRM-05']->id,
                'nama' => 'Pos Logistik Pemadam Desa Ngadas',
                'jenis' => 'pos_jaga',
                'latitude' => -7.9870000,
                'longitude' => 112.9060000,
                'debit_air' => '80 Liter/menit',
                'status_siaga' => 'Siaga Relawan Tengger',
                'jalur_evakuasi_geojson' => null,
            ],
            [
                'zona_id' => $zonas['BRM-06']->id,
                'nama' => 'Danau Alami Ranu Pani & Pos SAR',
                'jenis' => 'sumber_air',
                'latitude' => -8.0100000,
                'longitude' => 112.9470000,
                'debit_air' => 'Danau Alami Melimpah',
                'status_siaga' => 'Pusat Logistik Sektor Selatan',
                'jalur_evakuasi_geojson' => null,
            ],
        ];

        foreach ($sumberAirs as $sumber) {
            SumberAir::updateOrCreate(['nama' => $sumber['nama']], $sumber);
        }

        // ---------------------------------------------------------------------
        // 3. Laporan Asap Lapangan Contoh
        // ---------------------------------------------------------------------
        LaporanAsap::updateOrCreate(
            ['nomor_tiket' => '#LP-0231'],
            [
                'zona_id' => $savana->id,
                'pelapor_id' => $wisatawan?->id,
                'ip_pelapor' => '182.253.14.92',
                'latitude' => -7.9695000,
                'longitude' => 112.9632000,
                'akurasi_gps' => 12.50,
                'jenis_temuan' => 'asap_tebal',
                'foto_1' => 'demo/laporan_savana_1.jpg',
                'foto_2' => null,
                'catatan' => 'Terlihat kepulan asap membubung pekat di dekat lereng bukit Teletubbies, terbawa angin kencang ke arah barat laut.',
                'status_tiket' => 'terverifikasi',
                'hasil_verifikasi' => 'api_nyata',
                'tiket_induk_id' => null,
                'foto_verifikasi' => 'demo/verifikasi_savana_1.jpg',
                'diverifikasi_oleh' => $petugas?->id,
                'diverifikasi_pada' => Carbon::now()->subMinutes(45),
                'sumber' => 'aktual',
            ]
        );

        LaporanAsap::updateOrCreate(
            ['nomor_tiket' => '#LP-0232'],
            [
                'zona_id' => $kaldera->id,
                'pelapor_id' => null,
                'ip_pelapor' => '114.124.201.5',
                'latitude' => -7.9430000,
                'longitude' => 112.9510000,
                'akurasi_gps' => 18.00,
                'jenis_temuan' => 'asap_tipis',
                'foto_1' => null,
                'foto_2' => null,
                'catatan' => 'Ada kepulan asap tipis di punggungan pasir barat Gunung Batok.',
                'status_tiket' => 'menuju_lokasi',
                'hasil_verifikasi' => null,
                'tiket_induk_id' => null,
                'foto_verifikasi' => null,
                'diverifikasi_oleh' => null,
                'diverifikasi_pada' => null,
                'sumber' => 'aktual',
            ]
        );

        // ---------------------------------------------------------------------
        // 4. Log Siaran Peringatan Dini Multi-Kanal
        // ---------------------------------------------------------------------
        LogSiaran::create([
            'jenis_siaran' => 'waspada',
            'zona_id' => $savana->id,
            'kanal' => 'telegram_publik',
            'judul_pesan' => 'WASPADA — Savana & Jemplang (BRM-04)',
            'isi_pesan' => "WASPADA — Savana & Jemplang (BRM-04)\nHari ke-11 tanpa hujan. FDI 62/100, angin 19 km/j.\nJangan membakar sampah. Padamkan puntung rokok.\nJangan menyalakan flare atau api terbuka.\nDarurat: 112 · Pantau: pyrog.uard/brm04",
            'disetujui_oleh' => $admin?->id,
            'jumlah_penerima' => 342,
            'adalah_simulasi' => false,
            'dikirim_pada' => Carbon::now()->subDays(3)->setHour(8)->setMinute(15),
        ]);

        LogSiaran::create([
            'jenis_siaran' => 'siaga_1',
            'zona_id' => $savana->id,
            'kanal' => 'grup_petugas',
            'judul_pesan' => 'SIAGA 1 — Savana & Jemplang (BRM-04)',
            'isi_pesan' => "SIAGA 1 — Savana & Jemplang (BRM-04)\nHari ke-19 tanpa hujan. FDI 86/100. Angin 28 km/j.\nDilarang keras membuat api terbuka sampai status dicabut.\nSiapkan peralatan pemadam dan pantau titik embung.\nDarurat: 112 · Info: pyrog.uard/brm04",
            'disetujui_oleh' => $admin?->id,
            'jumlah_penerima' => 24,
            'adalah_simulasi' => false,
            'dikirim_pada' => Carbon::now()->subHours(2),
        ]);

        // ---------------------------------------------------------------------
        // 5. Langganan Web Push Contoh
        // ---------------------------------------------------------------------
        LanggananPush::updateOrCreate(
            ['endpoint' => 'https://fcm.googleapis.com/fcm/send/demo-sub-rina-bromo-01'],
            [
                'pengguna_id' => $wisatawan?->id,
                'zona_id' => $savana->id,
                'public_key' => 'BLA...demoPublicKeyRina...',
                'auth_token' => 'demoAuthTokenRina',
                'aktif' => true,
            ]
        );
    }
}
