<?php

namespace App\Services;

use App\Models\CuacaHarian;
use App\Models\PrakiraanRisiko;
use App\Models\StatusSiaga;
use App\Models\Zona;
use Carbon\Carbon;

/**
 * Mesin Hitung Risiko Kebakaran Hutan & Lahan PyroGuard (Risk Engine)
 * Mengimplementasikan kalkulasi CDD (Consecutive Dry Days) dan FDI (Fire Danger Index)
 * sesuai spesifikasi §16.3 design.md.
 */
class LayananMesinRisiko
{
    /**
     * Hitung indeks risiko kebakaran untuk satu zona berdasarkan riwayat cuaca.
     */
    public function hitungRisikoZona(Zona $zona, string $sumber = 'aktual'): PrakiraanRisiko
    {
        $today = Carbon::today();

        // 1. Hitung CDD_now: jumlah hari berturut-turut curah hujan < 1 mm mundur dari hari ini
        $riwayatCuaca = CuacaHarian::where('zona_id', $zona->id)
            ->where('sumber', $sumber)
            ->whereDate('tanggal', '<=', $today)
            ->orderBy('tanggal', 'desc')
            ->get();

        $cddNow = 0;
        foreach ($riwayatCuaca as $cuaca) {
            if ((float) $cuaca->curah_hujan_mm < 1.0) {
                $cddNow++;
            } else {
                break; // Hujan >= 1 mm mereset streak CDD
            }
        }

        // 2. Hitung CDD_proy (+24j, +48j, +72j) berdasarkan prakiraan ke depan
        $prakiraanCuaca = CuacaHarian::where('zona_id', $zona->id)
            ->where('sumber', $sumber)
            ->whereDate('tanggal', '>', $today)
            ->orderBy('tanggal', 'asc')
            ->take(3)
            ->get();

        $proyeksi24j = $cddNow + 1;
        $proyeksi48j = $cddNow + 2;
        $proyeksi72j = $cddNow + 3;

        if ($prakiraanCuaca->isNotEmpty()) {
            $streakBerjalan = $cddNow;
            $hariKe = 1;
            foreach ($prakiraanCuaca as $item) {
                if ((float) $item->curah_hujan_mm < 1.0) {
                    $streakBerjalan++;
                } else {
                    $streakBerjalan = 0; // Reset jika diprediksi hujan >= 1 mm
                }

                if ($hariKe === 1) {
                    $proyeksi24j = $streakBerjalan;
                } elseif ($hariKe === 2) {
                    $proyeksi48j = $streakBerjalan;
                } elseif ($hariKe === 3) {
                    $proyeksi72j = $streakBerjalan;
                }
                $hariKe++;
            }
        }

        // 3. Ambil parameter cuaca harian terkini (hari ini atau catatan paling baru)
        $cuacaTerkini = $riwayatCuaca->first();
        $rhMin = $cuacaTerkini ? (float) $cuacaTerkini->kelembapan_min : 35.0;
        $kecepatanAngin = $cuacaTerkini ? (float) $cuacaTerkini->kecepatan_angin_maks : 20.0;
        $arahAngin = $cuacaTerkini ? $cuacaTerkini->arah_angin : 'Tenggara';

        // 4. Hitung kontribusi poin dan FDI (§16.3 design.md)
        // FDI = min(100, CDD*2.85 + (100 - RH_min)*0.35 + angin*0.3)
        $poinCdd = round($cddNow * 2.85, 2);
        $poinKelembapan = round((100 - $rhMin) * 0.35, 2);
        $poinAngin = round($kecepatanAngin * 0.30, 2);

        // Bulatkan ke bilangan bulat per faktor untuk menjaga integritas angka layar "Mengapa"
        $fdi = (int) min(100, round($poinCdd) + round($poinKelembapan) + round($poinAngin));

        // 5. Tentukan Kategori Risiko
        $kategoriRisiko = match (true) {
            $cddNow <= 7 => 'AMAN',
            $cddNow <= 14 => 'WASPADA',
            default => 'BAHAYA',
        };

        // 6. Tentukan Jendela Jam Rentan
        $jamRentan = match ($kategoriRisiko) {
            'BAHAYA' => '12:00 - 15:30 WIB',
            'WASPADA' => '12:30 - 15:00 WIB',
            default => '13:00 - 14:30 WIB',
        };

        // 7. Tentukan Status Siaga Otomatis (§16.3 design.md)
        // Cek status siaga saat ini di database
        $siagaSaatIni = StatusSiaga::where('zona_id', $zona->id)
            ->where('sumber', $sumber)
            ->latest('id')
            ->first();

        $statusSiagaBaru = 'NORMAL';

        if ($siagaSaatIni && $siagaSaatIni->status === 'SIAGA_1') {
            // Jika sudah disetujui SIAGA_1 oleh admin, pertahankan sampai dicabut resmi
            $statusSiagaBaru = 'SIAGA_1';
        } elseif ($kategoriRisiko === 'BAHAYA' && $fdi > 80) {
            // Bahaya DAN FDI > 80 memicu SIAGA_OTOMATIS (menunggu persetujuan Admin)
            $statusSiagaBaru = 'SIAGA_OTOMATIS';

            if (! $siagaSaatIni || $siagaSaatIni->status !== 'SIAGA_OTOMATIS') {
                StatusSiaga::create([
                    'zona_id' => $zona->id,
                    'status' => 'SIAGA_OTOMATIS',
                    'catatan_persetujuan' => "Pemicu otomatis sistem: {$zona->nama} mencapai CDD {$cddNow} hari dan FDI {$fdi}/100.",
                    'sumber' => $sumber,
                ]);
            }
        } elseif ($siagaSaatIni && $siagaSaatIni->status === 'SIAGA_OTOMATIS') {
            // Jika kondisi mereda dan belum disetujui, kembalikan ke NORMAL
            $statusSiagaBaru = 'NORMAL';
        }

        // 8. Simpan hasil ke tabel prakiraan_risiko (FR-ENG-06)
        return PrakiraanRisiko::updateOrCreate(
            [
                'zona_id' => $zona->id,
                'sumber' => $sumber,
            ],
            [
                'hari_tanpa_hujan' => $cddNow,
                'proyeksi_cdd_24j' => $proyeksi24j,
                'proyeksi_cdd_48j' => $proyeksi48j,
                'proyeksi_cdd_72j' => $proyeksi72j,
                'indeks_bahaya_api' => $fdi,
                'kategori_risiko' => $kategoriRisiko,
                'poin_cdd' => $poinCdd,
                'poin_kelembapan' => $poinKelembapan,
                'poin_angin' => $poinAngin,
                'jam_rentan' => $jamRentan,
                'kecepatan_angin_kmj' => $kecepatanAngin,
                'arah_angin' => $arahAngin,
                'status_siaga' => $statusSiagaBaru,
                'status_data' => 'aktual',
                'dihitung_pada' => Carbon::now(),
            ]
        );
    }

    /**
     * Hitung ulang risiko untuk semua zona yang terdaftar.
     *
     * @return array<string, PrakiraanRisiko>
     */
    public function hitungSemuaZona(string $sumber = 'aktual'): array
    {
        $hasil = [];
        $zonas = Zona::all();

        foreach ($zonas as $zona) {
            $hasil[$zona->kode] = $this->hitungRisikoZona($zona, $sumber);
        }

        return $hasil;
    }
}
