<?php

namespace App\Services;

use App\Models\CuacaHarian;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Layanan Sinkronisasi Cuaca Open-Meteo API
 * Bertanggung jawab mengambil data historis & prakiraan per zona Bromo
 * serta memicu penghitungan ulang risiko kebakaran.
 */
class LayananCuacaOpenMeteo
{
    /**
     * Injeksi dependensi Mesin Hitung Risiko.
     */
    public function __construct(
        protected LayananMesinRisiko $mesinRisiko
    ) {}

    /**
     * Sinkronkan cuaca satu zona dari Open-Meteo API.
     *
     * @return array{sukses: bool, zona: string, status_kesegaran: string, pesan: string, records_tercatat?: int}
     */
    public function sinkronCuacaZona(Zona $zona): array
    {
        $baseUrl = config('services.open_meteo.base_url', 'https://api.open-meteo.com/v1/forecast');

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->retry(2, 500)
                ->get($baseUrl, [
                    'latitude' => $zona->titik_tengah_lat,
                    'longitude' => $zona->titik_tengah_lng,
                    'daily' => 'precipitation_sum,relative_humidity_2m_min,wind_speed_10m_max,wind_direction_10m_dominant,temperature_2m_max',
                    'timezone' => 'Asia/Jakarta',
                    'past_days' => 30,
                    'forecast_days' => 4,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Respons Open-Meteo gagal dengan kode: '.$response->status());
            }

            $data = $response->json();
            $daily = $data['daily'] ?? [];

            if (empty($daily['time'])) {
                throw new \RuntimeException('Format respons harian Open-Meteo tidak valid.');
            }

            $count = 0;
            $dates = $daily['time'];
            $precip = $daily['precipitation_sum'] ?? [];
            $rhMin = $daily['relative_humidity_2m_min'] ?? [];
            $windSpeed = $daily['wind_speed_10m_max'] ?? [];
            $windDir = $daily['wind_direction_10m_dominant'] ?? [];
            $tempMax = $daily['temperature_2m_max'] ?? [];

            foreach ($dates as $idx => $tglString) {
                $tgl = Carbon::parse($tglString);
                $isFuture = $tgl->isAfter(Carbon::today());

                $arahTeks = $this->konversiDerajatKeArahMataAngin((float) ($windDir[$idx] ?? 135));

                CuacaHarian::updateOrCreate(
                    [
                        'zona_id' => $zona->id,
                        'tanggal' => $tgl->toDateString(),
                        'sumber' => 'aktual',
                    ],
                    [
                        'curah_hujan_mm' => (float) ($precip[$idx] ?? 0.0),
                        'kelembapan_min' => (float) ($rhMin[$idx] ?? 40.0),
                        'kecepatan_angin_maks' => (float) ($windSpeed[$idx] ?? 15.0),
                        'arah_angin' => $arahTeks,
                        'suhu_maks' => isset($tempMax[$idx]) ? (float) $tempMax[$idx] : null,
                        'status_data' => $isFuture ? 'prakiraan' : 'aktual',
                    ]
                );
                $count++;
            }

            // Hitung ulang risiko kebakaran zona berdasarkan data baru
            $this->mesinRisiko->hitungRisikoZona($zona, 'aktual');

            return [
                'sukses' => true,
                'zona' => $zona->kode,
                'status_kesegaran' => 'segar',
                'pesan' => "Berhasil menyinkronkan {$count} rekaman cuaca untuk {$zona->nama}.",
                'records_tercatat' => $count,
            ];
        } catch (Throwable $e) {
            Log::warning("Gagal menyinkronkan cuaca zona {$zona->kode}: {$e->getMessage()}");

            $kesegaran = $this->cekKesegaranData($zona);

            return [
                'sukses' => false,
                'zona' => $zona->kode,
                'status_kesegaran' => $kesegaran['status'],
                'pesan' => $kesegaran['label'],
            ];
        }
    }

    /**
     * Sinkronkan cuaca ke seluruh 6 zona Bromo.
     *
     * @return array<string, array>
     */
    public function sinkronSemuaZona(): array
    {
        $zonas = Zona::all();
        $hasil = [];

        foreach ($zonas as $zona) {
            $hasil[$zona->kode] = $this->sinkronCuacaZona($zona);
        }

        return $hasil;
    }

    /**
     * Periksa status kesegaran data sesuai §6.6 dan §9 design.md:
     * - Segar (< 2 jam)
     * - Perhatian (2-6 jam)
     * - Kedaluwarsa (> 6 jam)
     *
     * @return array{status: string, label: string, terakhir_diperbarui: ?string, durasi_jam: int}
     */
    public function cekKesegaranData(Zona $zona): array
    {
        $risiko = $zona->risikoTerbaru;

        if (! $risiko || ! $risiko->dihitung_pada) {
            return [
                'status' => 'kedaluwarsa',
                'label' => 'Data cuaca tidak tersedia. Ikuti instruksi resmi TNBTS/BPBD.',
                'terakhir_diperbarui' => null,
                'durasi_jam' => 999,
            ];
        }

        $waktuHitung = Carbon::parse($risiko->dihitung_pada);
        $diffJam = (int) $waktuHitung->diffInHours(Carbon::now());

        if ($diffJam < 2) {
            return [
                'status' => 'segar',
                'label' => 'DIPERBARUI '.$waktuHitung->format('H:i').' WIB · SUMBER OPEN-METEO',
                'terakhir_diperbarui' => $waktuHitung->toIso8601String(),
                'durasi_jam' => $diffJam,
            ];
        }

        if ($diffJam <= 6) {
            return [
                'status' => 'perhatian',
                'label' => "Data {$diffJam} jam lalu, pengambilan berikutnya sedang dicoba.",
                'terakhir_diperbarui' => $waktuHitung->toIso8601String(),
                'durasi_jam' => $diffJam,
            ];
        }

        return [
            'status' => 'kedaluwarsa',
            'label' => 'Data cuaca terakhir tidak dapat diperbarui. Ikuti instruksi resmi TNBTS/BPBD.',
            'terakhir_diperbarui' => $waktuHitung->toIso8601String(),
            'durasi_jam' => $diffJam,
        ];
    }

    /**
     * Konversi derajat arah angin (0 - 360°) ke arah mata angin bahasa Indonesia.
     */
    public function konversiDerajatKeArahMataAngin(float $derajat): string
    {
        $derajat = fmod($derajat, 360);
        if ($derajat < 0) {
            $derajat += 360;
        }

        return match (true) {
            $derajat >= 337.5 || $derajat < 22.5 => 'Utara',
            $derajat >= 22.5 && $derajat < 67.5 => 'Timur Laut',
            $derajat >= 67.5 && $derajat < 112.5 => 'Timur',
            $derajat >= 112.5 && $derajat < 157.5 => 'Tenggara',
            $derajat >= 157.5 && $derajat < 202.5 => 'Selatan',
            $derajat >= 202.5 && $derajat < 247.5 => 'Barat Daya',
            $derajat >= 247.5 && $derajat < 292.5 => 'Barat',
            default => 'Barat Laut',
        };
    }
}
