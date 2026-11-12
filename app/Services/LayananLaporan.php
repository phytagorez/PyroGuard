<?php

namespace App\Services;

use App\Models\LaporanAsap;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Layanan Penanganan Laporan Asap & Tiket Petugas Lapangan
 */
class LayananLaporan
{
    /**
     * Konstruktor layanan penanganan laporan dengan promosi properti.
     */
    public function __construct(
        public LayananKompresiFoto $kompresiFoto
    ) {}

    /**
     * Buat laporan asap baru dari warga/wisatawan/petugas.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function buatLaporan(array $data, ?int $pelaporId = null, ?string $ip = null): LaporanAsap
    {
        // 1. Batas Laju Pelaporan: Maksimal 3 laporan per 15 menit (§6.8 design.md)
        $this->periksaBatasLaju($pelaporId, $ip);

        // 2. Tentukan zona jika belum disediakan secara eksplisit
        $zonaId = $data['zona_id'] ?? $this->tentukanZonaTerdekat((float) $data['latitude'], (float) $data['longitude']);

        // 3. Generate nomor tiket unik (#LP-XXXX)
        $nomorTiket = $this->generateNomorTiket();

        // 4. Proses berkas foto dengan kompresi maksimal 1200 px jika ada
        $pathFoto1 = $this->simpanFoto($data['foto_1'] ?? null);
        $pathFoto2 = $this->simpanFoto($data['foto_2'] ?? null);

        // 5. Simpan laporan ke database
        $laporan = LaporanAsap::create([
            'nomor_tiket' => $nomorTiket,
            'zona_id' => $zonaId,
            'pelapor_id' => $pelaporId,
            'ip_pelapor' => $ip,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'akurasi_gps' => $data['akurasi_gps'] ?? null,
            'jenis_temuan' => $data['jenis_temuan'],
            'foto_1' => $pathFoto1,
            'foto_2' => $pathFoto2,
            'catatan' => $data['catatan'] ?? null,
            'status_tiket' => 'baru',
            'hasil_verifikasi' => null,
            'sumber' => $data['sumber'] ?? 'aktual',
        ]);

        // 6. Kabari petugas lewat Telegram. Kegagalan Telegram tidak boleh
        //    menggagalkan laporan warga, jadi dibungkus try/catch.
        try {
            app(LayananBotTelegram::class)->kirimPeringatanLaporanBaru($laporan->load('zona'));
        } catch (\Throwable $e) {
            Log::warning('Peringatan Telegram laporan baru gagal: '.$e->getMessage());
        }

        return $laporan;
    }

    /**
     * Verifikasi laporan oleh petugas lapangan TNBTS (§6.9 & §7.7 design.md).
     *
     * @param  array<string, mixed>  $data
     */
    public function verifikasiLaporan(LaporanAsap $laporan, array $data, ?int $petugasId = null): LaporanAsap
    {
        $hasil = $data['hasil_verifikasi']; // 'api_nyata', 'bukan_api', 'ganda'

        $statusBaru = match ($hasil) {
            'api_nyata' => 'terverifikasi',
            'bukan_api' => 'selesai',
            'ganda' => 'selesai',
            default => 'terverifikasi',
        };

        $pathFotoVerifikasi = $this->simpanFoto($data['foto_verifikasi'] ?? null) ?? $laporan->foto_verifikasi;

        $laporan->update([
            'status_tiket' => $statusBaru,
            'hasil_verifikasi' => $hasil,
            'tiket_induk_id' => $hasil === 'ganda' ? ($data['tiket_induk_id'] ?? null) : null,
            'foto_verifikasi' => $pathFotoVerifikasi,
            'catatan' => isset($data['catatan']) && ! empty($data['catatan']) ? ($laporan->catatan ? $laporan->catatan."\n[Petugas]: ".$data['catatan'] : '[Petugas]: '.$data['catatan']) : $laporan->catatan,
            'diverifikasi_oleh' => $petugasId ?? $laporan->diverifikasi_oleh,
            'diverifikasi_pada' => Carbon::now(),
        ]);

        // Jika terverifikasi api_nyata, naikkan skor kepercayaan pelapor (§7.7)
        if ($hasil === 'api_nyata' && $laporan->pelapor) {
            $laporan->pelapor->increment('skor_kepercayaan', 10);
        }

        return $laporan->fresh(['zona', 'pelapor', 'verifikator', 'tiketInduk']);
    }

    /**
     * Perbarui status alur kerja petugas (mis. Menuju Lokasi atau Tandai Ditangani).
     */
    public function perbaruiStatusTiket(LaporanAsap $laporan, string $statusBaru, ?int $petugasId = null): LaporanAsap
    {
        $updateData = [
            'status_tiket' => $statusBaru,
        ];

        if ($petugasId && ! $laporan->diverifikasi_oleh) {
            $updateData['diverifikasi_oleh'] = $petugasId;
        }

        $laporan->update($updateData);

        return $laporan->fresh(['zona', 'pelapor', 'verifikator', 'tiketInduk']);
    }

    /**
     * Hitung sisa batas laju pelaporan (3 laporan per 15 menit) beserta waktu tunggu detik.
     *
     * @return array{kuota_terpakai: int, sisa_kuota: int, terkunci: bool, detik_tunggu: int}
     */
    public function hitungSisaBatasLaju(?int $pelaporId, ?string $ip): array
    {
        $batasWaktu = Carbon::now()->subMinutes(15);
        $query = LaporanAsap::where('created_at', '>=', $batasWaktu);

        if ($pelaporId) {
            $query->where('pelapor_id', $pelaporId);
        } elseif ($ip) {
            $query->where('ip_pelapor', $ip);
        } else {
            return [
                'kuota_terpakai' => 0,
                'sisa_kuota' => 3,
                'terkunci' => false,
                'detik_tunggu' => 0,
            ];
        }

        $laporanTerakhir = (clone $query)->latest('created_at')->first();
        $jumlah = $query->count();
        $sisaKuota = max(0, 3 - $jumlah);
        $detikTunggu = 0;

        if ($sisaKuota === 0 && $laporanTerakhir) {
            $resetWaktu = Carbon::parse($laporanTerakhir->created_at)->addMinutes(15);
            $detikTunggu = max(0, Carbon::now()->diffInSeconds($resetWaktu, false));
        }

        return [
            'kuota_terpakai' => $jumlah,
            'sisa_kuota' => $sisaKuota,
            'terkunci' => $sisaKuota === 0,
            'detik_tunggu' => (int) $detikTunggu,
        ];
    }

    /**
     * Periksa pembatasan laju laporan: 3 laporan per 15 menit.
     *
     * @throws ValidationException
     */
    protected function periksaBatasLaju(?int $pelaporId, ?string $ip): void
    {
        $statusBatas = $this->hitungSisaBatasLaju($pelaporId, $ip);

        if ($statusBatas['terkunci']) {
            throw ValidationException::withMessages([
                'laporan' => ['Terlalu banyak laporan dari perangkat ini. Silakan coba lagi dalam beberapa menit (§6.8 design.md).'],
            ]);
        }
    }

    /**
     * Tentukan zona terdekat berdasarkan koordinat latitude & longitude jika zona_id tidak dikirim.
     */
    public function tentukanZonaTerdekat(float $lat, float $lng): int
    {
        // Utamakan desa yang poligonnya memuat titik laporan (analisis 30 desa).
        $desa = Zona::cariDesaDi($lat, $lng);
        if ($desa) {
            return $desa->id;
        }

        // Cadangan: zona terdekat bila data desa belum diimpor.
        $zonas = Zona::all();
        $zonaTerpilih = $zonas->first();
        $jarakTerkecil = PHP_FLOAT_MAX;

        foreach ($zonas as $zona) {
            $dLat = $zona->titik_tengah_lat - $lat;
            $dLng = $zona->titik_tengah_lng - $lng;
            $jarak = ($dLat * $dLat) + ($dLng * $dLng);

            if ($jarak < $jarakTerkecil) {
                $jarakTerkecil = $jarak;
                $zonaTerpilih = $zona;
            }
        }

        return $zonaTerpilih->id;
    }

    /**
     * Generate nomor tiket berurutan unik, contoh: #LP-0233
     */
    protected function generateNomorTiket(): string
    {
        $lastId = LaporanAsap::max('id') ?? 0;
        $urutan = $lastId + 1;

        return sprintf('#LP-%04d', $urutan);
    }

    /**
     * Simpan file foto yang diunggah ke storage public dengan kompresi maksimal 1200 px.
     */
    protected function simpanFoto(mixed $foto): ?string
    {
        if ($foto instanceof UploadedFile) {
            return $this->kompresiFoto->kompresDanSimpan($foto);
        }

        if (is_string($foto) && ! empty($foto)) {
            return $foto;
        }

        return null;
    }
}
