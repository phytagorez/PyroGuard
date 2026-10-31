<?php

namespace App\Http\Controllers;

use App\Models\LaporanAsap;
use App\Models\PrakiraanRisiko;
use App\Models\StatusSiaga;
use App\Models\SumberAir;
use App\Models\TitikPanas;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller Mode Demo Interaktif (§6.13, §7.10, §15 design.md).
 * Memungkinkan juri & evaluator menguji seluruh dinamika sistem (hari 0-20, siaga 1, verifikasi)
 * dalam satu sesi tanpa memicu peringatan nyata.
 */
class ModeDemoController extends Controller
{
    /**
     * Tampilkan halaman Mode Demo Interaktif (/demo).
     */
    public function index(Request $request): View
    {
        // 1. Kunci sesi ke Mode Demo
        session(['mode_demo' => true]);

        $hari = (int) $request->query('hari', 15);
        $hari = max(0, min(20, $hari));

        // 2. Hitung metrik simulasi untuk 6 zona Bromo
        $zonasSimulasi = $this->hitungDataSimulasi($hari);

        // Update database untuk zona demo agar halaman admin/pantauan ikut sinkron
        $this->sinkronkanKeDatabase($zonasSimulasi, $hari);

        // Urutkan zona berdasarkan keparahan FDI
        uasort($zonasSimulasi, function ($a, $b) {
            $isSiagaA = in_array($a['siaga'], ['SIAGA_1', 'SIAGA_OTOMATIS']) ? 1000 : 0;
            $isSiagaB = in_array($b['siaga'], ['SIAGA_1', 'SIAGA_OTOMATIS']) ? 1000 : 0;

            return ($isSiagaB + $b['fdi']) <=> ($isSiagaA + $a['fdi']);
        });

        $zonaTertinggi = reset($zonasSimulasi);

        // 3. Titik panas & laporan terverifikasi simulasi
        $hotspots = TitikPanas::with('zona:id,kode,nama')
            ->orderBy('terdeteksi_pada', 'desc')
            ->take(10)
            ->get();

        $laporanTerverifikasi = LaporanAsap::with('zona:id,kode,nama')
            ->where('hasil_verifikasi', 'api_nyata')
            ->orderBy('diverifikasi_pada', 'desc')
            ->take(5)
            ->get();

        $sumberAirs = SumberAir::with('zona:id,kode,nama')->get();

        return view('demo.index', [
            'hari' => $hari,
            'zonasSimulasi' => $zonasSimulasi,
            'zonaTertinggi' => $zonaTertinggi,
            'hotspots' => $hotspots,
            'laporanTerverifikasi' => $laporanTerverifikasi,
            'sumberAirs' => $sumberAirs,
        ]);
    }

    /**
     * API / Endpoint POST untuk simulasi penggeser hari kemarau secara reaktif (§6.13).
     */
    public function simulasiHari(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hari' => 'required|integer|min:0|max:20',
        ]);

        $hari = (int) $validated['hari'];
        session(['mode_demo' => true]);

        $zonasSimulasi = $this->hitungDataSimulasi($hari);

        // Simpan ke basis data agar seluruh modul (/admin/siaga, /admin/tinjauan) langsung sinkron
        $this->sinkronkanKeDatabase($zonasSimulasi, $hari);

        // Urutkan zona berdasarkan keparahan
        uasort($zonasSimulasi, function ($a, $b) {
            $isSiagaA = in_array($a['siaga'], ['SIAGA_1', 'SIAGA_OTOMATIS']) ? 1000 : 0;
            $isSiagaB = in_array($b['siaga'], ['SIAGA_1', 'SIAGA_OTOMATIS']) ? 1000 : 0;

            return ($isSiagaB + $b['fdi']) <=> ($isSiagaA + $a['fdi']);
        });

        $zonaTertinggi = reset($zonasSimulasi);

        return response()->json([
            'sukses' => true,
            'hari' => $hari,
            'zonaTertinggi' => $zonaTertinggi,
            'zonas' => array_values($zonasSimulasi),
            'pesan' => "Data simulasi hari ke-{$hari} berhasil dihitung dan disinkronkan.",
        ]);
    }

    /**
     * Endpoint API publik /api/v1/demo/simulate-day.
     */
    public function apiSimulateDay(Request $request): JsonResponse
    {
        return $this->simulasiHari($request);
    }

    /**
     * Matikan mode demo dan kembalikan ke data aktual (§7.10).
     */
    public function resetDemo(): RedirectResponse
    {
        session()->forget('mode_demo');

        return redirect()->route('pantauan')->with(
            'pesan',
            'Mode Demo dinonaktifkan. Sistem kembali menampilkan data operasional aktual.'
        );
    }

    /**
     * Buat laporan contoh asap cepat di Savana untuk menguji alur tiket petugas lapangan.
     */
    public function buatLaporanContoh(): RedirectResponse
    {
        session(['mode_demo' => true]);

        $savana = Zona::where('kode', 'BRM-04')->first() ?? Zona::first();

        $laporan = LaporanAsap::create([
            'nomor_tiket' => 'LP-DEMO-'.strtoupper(bin2hex(random_bytes(2))),
            'zona_id' => $savana->id,
            'pelapor_id' => auth()->id(),
            'nama_pelapor' => auth()->user()?->nama ?? 'Wisatawan Demo',
            'nomor_kontak' => '081234567890',
            'latitude' => -7.95420,
            'longitude' => 112.95240,
            'akurasi_meter' => 12,
            'jenis_temuan' => 'asap_tebal',
            'catatan' => '[SIMULASI DEMO] Terlihat kepulan asap tebal membubung di lereng Savana arah bukit Teletubbies.',
            'status_tiket' => 'baru',
            'skor_kepercayaan' => 90,
            'ip_pelapor' => '127.0.0.1',
        ]);

        return redirect()->route('petugas.index')->with(
            'pesan',
            "Laporan simulasi {$laporan->nomor_tiket} berhasil dibuat di {$savana->nama}. Silakan verifikasi sebagai petugas."
        );
    }

    /**
     * Hitung parameter simulasi untuk ke-6 zona pada hari ke-$hari (§16.3 & §15).
     *
     * @return array<string, array<string, mixed>>
     */
    public function hitungDataSimulasi(int $hari): array
    {
        $zonas = Zona::all();
        $dataZona = [];

        foreach ($zonas as $z) {
            $kode = $z->kode;

            if ($hari === 15 && $kode === 'BRM-04') {
                // Skenario 2: Kebakaran September 2023 (§15 & §6.13)
                $cdd = 19;
                $rhMin = 31.0;
                $angin = 28.0;
                $fdi = 86;
                $kategori = 'BAHAYA';
                $siaga = 'SIAGA_OTOMATIS';
                $arah = 'Tenggara';
                $jamRentan = '12:00 - 15:30 WIB';
            } elseif ($hari === 20) {
                // Skenario 3: Hujan Pertama (§15 & §6.13)
                $cdd = 0;
                $rhMin = 65.0;
                $angin = 16.0;
                $fdi = $kode === 'BRM-04' ? 41 : 24;
                $kategori = 'AMAN';
                $siaga = $kode === 'BRM-04' ? 'DICABUT' : 'NORMAL';
                $arah = 'Barat Daya';
                $jamRentan = '13:00 - 14:00 WIB';
            } elseif ($hari === 0) {
                // Skenario 1: Awal Kemarau (§15 & §6.13)
                $cdd = 1;
                $rhMin = 70.0;
                $angin = 12.0;
                $fdi = 22;
                $kategori = 'AMAN';
                $siaga = 'NORMAL';
                $arah = 'Timur';
                $jamRentan = '13:00 - 14:30 WIB';
            } else {
                // Kurva transisi dinamis hari 1-19
                $faktorZona = match ($kode) {
                    'BRM-04' => ['cdd' => 1.27, 'rh' => 2.6, 'angin' => 1.05, 'arah' => 'Tenggara'],
                    'BRM-03' => ['cdd' => 0.80, 'rh' => 2.4, 'angin' => 0.80, 'arah' => 'Tenggara'],
                    'BRM-01' => ['cdd' => 0.55, 'rh' => 2.2, 'angin' => 0.60, 'arah' => 'Timur Laut'],
                    'BRM-02' => ['cdd' => 0.40, 'rh' => 1.8, 'angin' => 0.40, 'arah' => 'Timur'],
                    'BRM-05' => ['cdd' => 0.30, 'rh' => 1.7, 'angin' => 0.35, 'arah' => 'Selatan'],
                    'BRM-06' => ['cdd' => 0.20, 'rh' => 1.5, 'angin' => 0.20, 'arah' => 'Barat Daya'],
                    default => ['cdd' => 0.50, 'rh' => 2.0, 'angin' => 0.50, 'arah' => 'Timur'],
                };

                $cdd = (int) round($hari * $faktorZona['cdd']);
                $rhMin = (float) max(25.0, 75.0 - ($hari * $faktorZona['rh']));
                $angin = (float) (10.0 + round($hari * $faktorZona['angin']));
                $arah = $faktorZona['arah'];

                $poinCdd = round($cdd * 2.85, 2);
                $poinKelembapan = round((100.0 - $rhMin) * 0.35, 2);
                $poinAngin = round($angin * 0.30, 2);
                $fdi = (int) min(100, round($poinCdd) + round($poinKelembapan) + round($poinAngin));

                $kategori = match (true) {
                    $cdd <= 7 => 'AMAN',
                    $cdd <= 14 => 'WASPADA',
                    default => 'BAHAYA',
                };

                $siaga = ($kategori === 'BAHAYA' && $fdi > 80) ? 'SIAGA_OTOMATIS' : 'NORMAL';
                $jamRentan = match ($kategori) {
                    'BAHAYA' => '12:00 - 15:30 WIB',
                    'WASPADA' => '12:30 - 15:00 WIB',
                    default => '13:00 - 14:30 WIB',
                };
            }

            $poinCdd = round($cdd * 2.85, 1);
            $poinKelembapan = round((100.0 - $rhMin) * 0.35, 1);
            $poinAngin = round($angin * 0.30, 1);

            $dataZona[$kode] = [
                'id' => $z->id,
                'kode' => $z->kode,
                'nama' => $z->nama,
                'poligon_geojson' => $z->poligon_geojson,
                'jenis_lahan' => $z->jenis_lahan,
                'akses_wisata' => $z->akses_wisata,
                'cdd' => $cdd,
                'proyeksi_24j' => $cdd + ($cdd > 0 ? 1 : 0),
                'proyeksi_48j' => $cdd + ($cdd > 0 ? 2 : 1),
                'proyeksi_72j' => $cdd + ($cdd > 0 ? 3 : 2),
                'fdi' => $fdi,
                'kategori' => $kategori,
                'siaga' => $siaga,
                'poin_cdd' => $poinCdd,
                'poin_kelembapan' => $poinKelembapan,
                'poin_angin' => $poinAngin,
                'rh_min' => $rhMin,
                'angin_kmj' => $angin,
                'arah_angin' => $arah,
                'jam_rentan' => $jamRentan,
                'penyumbang_utama' => ($poinCdd >= $poinKelembapan && $poinCdd >= $poinAngin)
                    ? 'Kekeringan Akumulatif (CDD)'
                    : 'Kelembapan Udara Rendah',
            ];
        }

        return $dataZona;
    }

    /**
     * Sinkronkan hasil simulasi ke tabel database untuk konsistensi layar lain.
     *
     * @param  array<string, array<string, mixed>>  $zonasSimulasi
     */
    private function sinkronkanKeDatabase(array $zonasSimulasi, int $hari): void
    {
        foreach ($zonasSimulasi as $data) {
            PrakiraanRisiko::updateOrCreate(
                [
                    'zona_id' => $data['id'],
                    'sumber' => 'demo',
                ],
                [
                    'hari_tanpa_hujan' => $data['cdd'],
                    'proyeksi_cdd_24j' => $data['proyeksi_24j'],
                    'proyeksi_cdd_48j' => $data['proyeksi_48j'],
                    'proyeksi_cdd_72j' => $data['proyeksi_72j'],
                    'indeks_bahaya_api' => $data['fdi'],
                    'kategori_risiko' => $data['kategori'],
                    'poin_cdd' => $data['poin_cdd'],
                    'poin_kelembapan' => $data['poin_kelembapan'],
                    'poin_angin' => $data['poin_angin'],
                    'jam_rentan' => $data['jam_rentan'],
                    'kecepatan_angin_kmj' => $data['angin_kmj'],
                    'arah_angin' => $data['arah_angin'],
                    'status_siaga' => $data['siaga'],
                    'status_data' => 'prakiraan',
                    'dihitung_pada' => Carbon::now(),
                ]
            );

            // Perbarui status siaga jika siaga_otomatis atau dicabut
            if ($data['siaga'] === 'SIAGA_OTOMATIS') {
                StatusSiaga::updateOrCreate(
                    [
                        'zona_id' => $data['id'],
                        'sumber' => 'demo',
                    ],
                    [
                        'status' => 'SIAGA_OTOMATIS',
                        'catatan_persetujuan' => "Pemicu otomatis simulasi hari ke-{$hari}: CDD {$data['cdd']} hari, FDI {$data['fdi']}/100.",
                        'disetujui_oleh' => null,
                        'disetujui_pada' => null,
                    ]
                );
            } elseif ($data['siaga'] === 'DICABUT') {
                StatusSiaga::updateOrCreate(
                    [
                        'zona_id' => $data['id'],
                        'sumber' => 'demo',
                    ],
                    [
                        'status' => 'DICABUT',
                        'catatan_persetujuan' => 'Pencabutan status otomatis setelah hujan turun pada hari ke-20.',
                        'dicabut_pada' => Carbon::now(),
                    ]
                );
            }
        }
    }
}
