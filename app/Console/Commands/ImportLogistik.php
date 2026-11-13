<?php

namespace App\Console\Commands;

use App\Models\SumberAir;
use App\Models\Zona;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Impor titik logistik (embung, sumber air, pos jaga, titik kumpul) dari CSV.
 *
 * Aman dijalankan berulang: titik dengan nama dan jenis yang sama diperbarui,
 * bukan digandakan. Tidak ada data yang dihapus kecuali dengan --hapus-contoh.
 */
class ImportLogistik extends Command
{
    protected $signature = 'app:import-logistik
        {file? : Lokasi file CSV (bawaan: storage/app/datasets/logistik_bromo.csv)}
        {--dry-run : Hanya periksa file, tidak menyimpan apa pun}
        {--hapus-contoh : Setelah impor tanpa galat, hapus titik data contoh lama}';

    protected $description = 'Impor titik logistik Bromo (air, pos jaga, titik kumpul) dari file CSV';

    private const JENIS = ['embung', 'sumber_air', 'pos_jaga', 'titik_kumpul'];

    public function handle(): int
    {
        $path = $this->argument('file') ?: storage_path('app/datasets/logistik_bromo.csv');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");
            $this->line('Salin template storage/app/datasets/template_logistik_bromo.csv, isi, lalu simpan sebagai logistik_bromo.csv.');

            return self::FAILURE;
        }

        if (Zona::desa()->count() === 0) {
            $this->error('Data 30 desa belum ada. Jalankan dulu: php artisan app:import-dataset-desa');

            return self::FAILURE;
        }

        [$batas, $baris] = $this->bacaCsv($path);
        if ($baris === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $ok = 0;
        $galat = 0;
        $peringatan = 0;
        $dilewati = 0;

        foreach ($baris as $nomor => $row) {
            $nama = trim((string) ($row['nama'] ?? ''));

            if ($nama === '' || str_starts_with(strtoupper($nama), 'CONTOH')) {
                $dilewati++;

                continue;
            }

            $jenis = strtolower(trim((string) ($row['jenis'] ?? '')));
            if (! in_array($jenis, self::JENIS, true)) {
                $this->error("Baris {$nomor} ({$nama}): jenis '{$jenis}' tidak dikenal. Pilih: ".implode(', ', self::JENIS));
                $galat++;

                continue;
            }

            $lat = $this->angka($row['latitude'] ?? null);
            $lng = $this->angka($row['longitude'] ?? null);
            if ($lat === null || $lng === null) {
                $this->error("Baris {$nomor} ({$nama}): latitude/longitude kosong atau bukan angka.");
                $galat++;

                continue;
            }

            if (! $this->dalamKawasan($lat, $lng)) {
                $this->error("Baris {$nomor} ({$nama}): koordinat ({$lat}, {$lng}) di luar kawasan Bromo. Periksa tanda minus atau tertukar lat/lng (lat sekitar -7,9; lng sekitar 112,9).");
                $galat++;

                continue;
            }

            $sumber = trim((string) ($row['sumber_data'] ?? ''));
            if ($sumber === '' || strtolower($sumber) === 'contoh') {
                $this->error("Baris {$nomor} ({$nama}): kolom sumber_data wajib diisi (mis. 'BBTNBTS', 'BPBD Kab. Malang', 'Survei lapangan').");
                $galat++;

                continue;
            }

            $tanggal = null;
            if (trim((string) ($row['tanggal_verifikasi'] ?? '')) !== '') {
                try {
                    $tanggal = Carbon::parse(trim($row['tanggal_verifikasi']))->toDateString();
                } catch (\Throwable) {
                    $this->error("Baris {$nomor} ({$nama}): tanggal_verifikasi tidak valid. Pakai format 2026-11-20.");
                    $galat++;

                    continue;
                }
            }

            $desa = Zona::cariDesaDi($lat, $lng);
            if (! Zona::titikDalamGeometri([$lng, $lat], $desa?->poligon_geojson)) {
                $this->warn("Baris {$nomor} ({$nama}): titik tepat di luar batas desa, dikaitkan ke desa terdekat ({$desa->nama}).");
                $peringatan++;
            }

            if (! $dryRun) {
                SumberAir::updateOrCreate(
                    ['nama' => $nama, 'jenis' => $jenis],
                    [
                        'zona_id' => $desa->id,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'debit_air' => trim((string) ($row['kapasitas'] ?? '')) ?: null,
                        'status_siaga' => trim((string) ($row['status'] ?? '')) ?: 'Tersedia',
                        'sumber_data' => $sumber,
                        'tanggal_verifikasi' => $tanggal,
                        'kontak' => trim((string) ($row['kontak'] ?? '')) ?: null,
                    ]
                );
            }

            $this->line("  ✓ {$nama} → {$desa->nama}");
            $ok++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY-RUN, tidak disimpan] ' : '')."Baris valid: {$ok} · galat: {$galat} · peringatan: {$peringatan} · dilewati: {$dilewati}");

        if ($galat > 0) {
            $this->warn($dryRun
                ? 'Perbaiki baris bergalat, lalu jalankan lagi tanpa --dry-run.'
                : 'Perbaiki baris bergalat lalu jalankan lagi. Baris yang valid sudah tersimpan.');

            return self::FAILURE;
        }

        if ($this->option('hapus-contoh') && ! $dryRun && $ok > 0) {
            $hapus = SumberAir::where(fn ($q) => $q->whereNull('sumber_data')->orWhere('sumber_data', '')->orWhere('sumber_data', 'contoh'))->delete();
            $this->info("Titik data contoh dihapus: {$hapus}");
        }

        return self::SUCCESS;
    }

    /**
     * Baca CSV (dukung titik koma ala Excel Indonesia dan BOM).
     *
     * @return array{0: string, 1: array<int, array<string, string>>|null}
     */
    private function bacaCsv(string $path): array
    {
        $isi = file_get_contents($path);
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        $baris = preg_split('/\r\n|\r|\n/', trim($isi));
        $header = $baris[0] ?? '';
        $batas = substr_count($header, ';') > substr_count($header, ',') ? ';' : ',';

        $kolom = array_map(fn ($k) => strtolower(trim($k)), str_getcsv($header, $batas));
        foreach (['nama', 'jenis', 'latitude', 'longitude', 'sumber_data'] as $wajib) {
            if (! in_array($wajib, $kolom, true)) {
                $this->error("Kolom wajib '{$wajib}' tidak ada di baris pertama. Gunakan template yang disediakan.");

                return [$batas, null];
            }
        }

        $hasil = [];
        foreach (array_slice($baris, 1) as $i => $teks) {
            if (trim($teks) === '' || trim($teks, ";, \t") === '') {
                continue;
            }
            $nilai = str_getcsv($teks, $batas);
            $hasil[$i + 2] = array_combine($kolom, array_pad(array_slice($nilai, 0, count($kolom)), count($kolom), ''));
        }

        return [$batas, $hasil];
    }

    private function angka(mixed $nilai): ?float
    {
        $teks = str_replace(',', '.', trim((string) $nilai));

        return is_numeric($teks) ? (float) $teks : null;
    }

    /**
     * Koordinat harus berada di sekitar 30 desa (kotak pembatas + margin ±0,05°).
     */
    private function dalamKawasan(float $lat, float $lng): bool
    {
        static $kotak = null;

        if ($kotak === null) {
            $kotak = [90.0, -90.0, 180.0, -180.0];
            foreach (Zona::desa()->get() as $d) {
                $kotak[0] = min($kotak[0], $d->titik_tengah_lat);
                $kotak[1] = max($kotak[1], $d->titik_tengah_lat);
                $kotak[2] = min($kotak[2], $d->titik_tengah_lng);
                $kotak[3] = max($kotak[3], $d->titik_tengah_lng);
            }
        }

        $m = 0.12; // margin dari titik tengah desa terluar

        return $lat >= $kotak[0] - $m && $lat <= $kotak[1] + $m && $lng >= $kotak[2] - $m && $lng <= $kotak[3] + $m;
    }
}
