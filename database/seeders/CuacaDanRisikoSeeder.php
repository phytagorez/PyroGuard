<?php

namespace Database\Seeders;

use App\Models\CuacaHarian;
use App\Models\PrakiraanRisiko;
use App\Models\StatusSiaga;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CuacaDanRisikoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zonas = Zona::all()->keyBy('kode');
        $today = Carbon::today();

        // ---------------------------------------------------------------------
        // 1. Data Konfigurasi Kalibrasi Risiko per Zona (§16.3 design.md)
        // ---------------------------------------------------------------------
        $risikoData = [
            'BRM-01' => [
                'cdd' => 8,
                'rh' => 42.0,
                'angin' => 18.0,
                'jam_rentan' => '12:30 - 15:00 WIB',
                'arah' => 'Timur Laut',
                'kategori' => 'WASPADA',
                'siaga' => 'NORMAL',
            ],
            'BRM-02' => [
                'cdd' => 6,
                'rh' => 50.0,
                'angin' => 16.0,
                'jam_rentan' => '13:00 - 14:30 WIB',
                'arah' => 'Timur',
                'kategori' => 'AMAN',
                'siaga' => 'NORMAL',
            ],
            'BRM-03' => [
                'cdd' => 12,
                'rh' => 36.0,
                'angin' => 22.0,
                'jam_rentan' => '11:30 - 15:30 WIB',
                'arah' => 'Tenggara',
                'kategori' => 'WASPADA',
                'siaga' => 'NORMAL',
            ],
            'BRM-04' => [
                // Savana Bromo: Sesuai spesifikasi PRD & design.md
                'cdd' => 19,
                'rh' => 31.0,
                'angin' => 28.0,
                'jam_rentan' => '12:00 - 15:30 WIB',
                'arah' => 'Tenggara',
                'kategori' => 'BAHAYA',
                'siaga' => 'SIAGA_OTOMATIS',
            ],
            'BRM-05' => [
                'cdd' => 4,
                'rh' => 55.0,
                'angin' => 14.0,
                'jam_rentan' => '13:00 - 15:00 WIB',
                'arah' => 'Selatan',
                'kategori' => 'AMAN',
                'siaga' => 'NORMAL',
            ],
            'BRM-06' => [
                'cdd' => 2,
                'rh' => 68.0,
                'angin' => 10.0,
                'jam_rentan' => '13:30 - 14:30 WIB',
                'arah' => 'Barat Daya',
                'kategori' => 'AMAN',
                'siaga' => 'NORMAL',
            ],
        ];

        $cddTargets = [
            'BRM-01' => 8,  // Cemoro Lawang: 8 hari kering
            'BRM-02' => 6,  // Wonokitri: 6 hari kering
            'BRM-03' => 12, // Lautan Pasir: 12 hari kering
            'BRM-04' => 19, // Savana: 19 hari kering berturut-turut (BAHAYA)
            'BRM-05' => 4,  // Ngadas: 4 hari kering
            'BRM-06' => 2,  // Ranu Pani: 2 hari kering
        ];

        foreach ($zonas as $kode => $zona) {
            $dryStreak = $cddTargets[$kode] ?? 5;

            for ($i = 29; $i >= 0; $i--) {
                $tanggal = $today->copy()->subDays($i);

                // Hari di dalam streak kering -> curah hujan 0 mm (< 1 mm)
                if ($i < $dryStreak) {
                    $curahHujan = 0.00;
                    if ($i === 0 && isset($risikoData[$kode])) {
                        // Hari ini: gunakan parameter kalibrasi persis sesuai §16.3 design.md
                        $kelembapan = $risikoData[$kode]['rh'];
                        $angin = $risikoData[$kode]['angin'];
                        $suhu = 26.5;
                    } else {
                        $kelembapan = match ($kode) {
                            'BRM-04' => rand(28, 33), // Savana sangat kering
                            'BRM-03' => rand(32, 38),
                            'BRM-01' => rand(38, 45),
                            default => rand(45, 60),
                        };
                        $angin = match ($kode) {
                            'BRM-04' => rand(25, 30), // Angin kencang savana
                            'BRM-03' => rand(20, 26),
                            default => rand(12, 20),
                        };
                        $suhu = match ($kode) {
                            'BRM-04', 'BRM-03' => rand(24, 28),
                            default => rand(18, 23),
                        };
                    }
                } else {
                    // Hari hujan pemutus streak sebelumnya
                    $curahHujan = ($i === $dryStreak) ? rand(6, 18) : (rand(0, 3) === 0 ? rand(2, 10) : 0);
                    $kelembapan = rand(65, 88);
                    $angin = rand(10, 18);
                    $suhu = rand(17, 22);
                }

                CuacaHarian::updateOrCreate(
                    [
                        'zona_id' => $zona->id,
                        'tanggal' => $tanggal->toDateString(),
                        'sumber' => 'aktual',
                    ],
                    [
                        'curah_hujan_mm' => $curahHujan,
                        'kelembapan_min' => $kelembapan,
                        'kecepatan_angin_maks' => $angin,
                        'arah_angin' => 'Tenggara',
                        'suhu_maks' => $suhu,
                        'status_data' => 'aktual',
                    ]
                );
            }
        }

        // ---------------------------------------------------------------------
        // 2. Prakiraan Risiko Aktual Hari Ini
        // Formula: FDI = min(100, CDD*2.85 + (100 - RH_min)*0.35 + angin*0.3)
        // ---------------------------------------------------------------------
        foreach ($risikoData as $kode => $cfg) {
            $zona = $zonas[$kode];
            $poinCdd = round($cfg['cdd'] * 2.85, 2);
            $poinRh = round((100 - $cfg['rh']) * 0.35, 2);
            $poinAngin = round($cfg['angin'] * 0.30, 2);
            $fdi = (int) min(100, round($poinCdd) + round($poinRh) + round($poinAngin));

            PrakiraanRisiko::updateOrCreate(
                [
                    'zona_id' => $zona->id,
                    'sumber' => 'aktual',
                ],
                [
                    'hari_tanpa_hujan' => $cfg['cdd'],
                    'proyeksi_cdd_24j' => $cfg['cdd'] + 1,
                    'proyeksi_cdd_48j' => $cfg['cdd'] + 2,
                    'proyeksi_cdd_72j' => $cfg['cdd'] + 3,
                    'indeks_bahaya_api' => $fdi,
                    'kategori_risiko' => $cfg['kategori'],
                    'poin_cdd' => $poinCdd,
                    'poin_kelembapan' => $poinRh,
                    'poin_angin' => $poinAngin,
                    'jam_rentan' => $cfg['jam_rentan'],
                    'kecepatan_angin_kmj' => $cfg['angin'],
                    'arah_angin' => $cfg['arah'],
                    'status_siaga' => $cfg['siaga'],
                    'status_data' => 'aktual',
                    'dihitung_pada' => Carbon::now()->setHour(7)->setMinute(0)->setSecond(0),
                ]
            );

            // Record status siaga untuk zona yang siaga
            if ($cfg['siaga'] !== 'NORMAL') {
                StatusSiaga::updateOrCreate(
                    [
                        'zona_id' => $zona->id,
                        'sumber' => 'aktual',
                    ],
                    [
                        'status' => $cfg['siaga'],
                        'disetujui_oleh' => null,
                        'catatan_persetujuan' => 'Pemicu otomatis mesin risiko: CDD 19 hari dan FDI 86 melampaui batas ambang bahaya savana.',
                        'disetujui_pada' => null,
                        'dicabut_oleh' => null,
                        'dicabut_pada' => null,
                    ]
                );
            }
        }

        // ---------------------------------------------------------------------
        // 3. Data Beku Mode Demo (§15 design.md: Hari ke-0, 15, dan 20)
        // ---------------------------------------------------------------------
        // Skenario Demo Hari ke-0: Awal Kemarau (Semua AMAN)
        foreach ($zonas as $kode => $zona) {
            PrakiraanRisiko::create([
                'zona_id' => $zona->id,
                'hari_tanpa_hujan' => 1,
                'proyeksi_cdd_24j' => 2,
                'proyeksi_cdd_48j' => 3,
                'proyeksi_cdd_72j' => 4,
                'indeks_bahaya_api' => 22,
                'kategori_risiko' => 'AMAN',
                'poin_cdd' => 2.85,
                'poin_kelembapan' => 12.5,
                'poin_angin' => 4.5,
                'jam_rentan' => '13:00 - 14:00 WIB',
                'kecepatan_angin_kmj' => 15.0,
                'arah_angin' => 'Timur',
                'status_siaga' => 'NORMAL',
                'status_data' => 'prakiraan',
                'sumber' => 'demo',
                'dihitung_pada' => Carbon::now()->subDays(20),
            ]);
        }

        // Skenario Demo Hari ke-15: Kebakaran September 2023 (Savana BAHAYA & SIAGA_1)
        $savana = $zonas['BRM-04'];
        PrakiraanRisiko::create([
            'zona_id' => $savana->id,
            'hari_tanpa_hujan' => 15,
            'proyeksi_cdd_24j' => 16,
            'proyeksi_cdd_48j' => 17,
            'proyeksi_cdd_72j' => 18,
            'indeks_bahaya_api' => 82,
            'kategori_risiko' => 'BAHAYA',
            'poin_cdd' => 42.75,
            'poin_kelembapan' => 28.5,
            'poin_angin' => 9.0,
            'jam_rentan' => '11:00 - 16:00 WIB',
            'kecepatan_angin_kmj' => 30.0,
            'arah_angin' => 'Tenggara',
            'status_siaga' => 'SIAGA_1',
            'status_data' => 'prakiraan',
            'sumber' => 'demo',
            'dihitung_pada' => Carbon::now()->subDays(5),
        ]);

        // Skenario Demo Hari ke-20: Hujan Pertama (Status DICABUT)
        PrakiraanRisiko::create([
            'zona_id' => $savana->id,
            'hari_tanpa_hujan' => 0,
            'proyeksi_cdd_24j' => 0,
            'proyeksi_cdd_48j' => 1,
            'proyeksi_cdd_72j' => 2,
            'indeks_bahaya_api' => 38,
            'kategori_risiko' => 'AMAN',
            'poin_cdd' => 0.0,
            'poin_kelembapan' => 10.5,
            'poin_angin' => 5.2,
            'jam_rentan' => '13:00 - 14:00 WIB',
            'kecepatan_angin_kmj' => 17.0,
            'arah_angin' => 'Barat',
            'status_siaga' => 'DICABUT',
            'status_data' => 'prakiraan',
            'sumber' => 'demo',
            'dihitung_pada' => Carbon::now(),
        ]);
    }
}
