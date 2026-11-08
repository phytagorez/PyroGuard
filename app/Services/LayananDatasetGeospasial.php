<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LayananDatasetGeospasial
{
    private const string Path = 'geospatial/dataset.json';

    /** @return array<string, mixed> */
    public function tersimpan(): array
    {
        $disk = Storage::disk('local');

        return $disk->exists(self::Path)
            ? json_decode($disk->get(self::Path), true, flags: JSON_THROW_ON_ERROR)
            : [];
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return json_decode(file_get_contents(public_path('data/bromo-metadata.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public function impor(?UploadedFile $csv, ?UploadedFile $wilayah): void
    {
        $pembaruan = [];

        if ($csv !== null) {
            $pembaruan['analisis'] = [
                'baris' => $this->bacaCsv($csv),
                'diimpor_pada' => now()->toIso8601String(),
                'sha256' => hash_file('sha256', $csv->getRealPath()),
                'sumber' => 'Unggahan pengelola, ekspor Google Earth Engine',
                'status_verifikasi' => 'Belum diverifikasi independen',
            ];
        }

        if ($wilayah !== null) {
            $pembaruan['wilayah'] = $this->bacaWilayah($wilayah);
            $pembaruan['wilayah_diimpor_pada'] = now()->toIso8601String();
            $pembaruan['wilayah_sha256'] = hash_file('sha256', $wilayah->getRealPath());
        }

        $dataset = array_replace($this->tersimpan(), $pembaruan);

        if (! Storage::disk('local')->put(self::Path, json_encode($dataset, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))) {
            throw new \RuntimeException('Dataset tidak dapat disimpan. Periksa penyimpanan aplikasi.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function bacaCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $indeks = ['NDVI_sebelum', 'NDVI_sesudah', 'NDMI_sebelum', 'NDMI_sesudah', 'NBR_sebelum', 'NBR_sesudah'];
        $numerik = [...$indeks, 'Hujan_Harian', 'Hujan_7_Hari', 'Suhu_Permukaan', 'Jumlah_Hotspot_Piksel', 'Longitude', 'Latitude'];
        $wajib = [...$numerik, 'Tanggal_Kebakaran', 'Lokasi'];

        try {
            $header = fgetcsv($handle, escape: '');

            if ($header === false) {
                $this->gagal('dataset_csv', 'CSV kosong. Unggah hasil ekspor tabel dari GEE.');
            }

            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
            $header = array_map('trim', $header);

            if (count(array_unique($header)) !== count($header) || array_diff($wajib, $header) !== []) {
                $this->gagal('dataset_csv', 'Kolom CSV tidak sesuai. Gunakan ekspor Dataset_PyroGuard_Bromo dari skrip GEE yang disediakan.');
            }

            $hasil = [];
            while (($nilai = fgetcsv($handle, escape: '')) !== false) {
                if ($nilai === [null]) {
                    continue;
                }
                if (count($nilai) !== count($header) || count($hasil) >= 1000) {
                    $this->gagal('dataset_csv', 'CSV harus memiliki kolom konsisten dan maksimal 1.000 baris.');
                }

                $baris = array_combine($header, $nilai);
                $data = [];
                foreach ($wajib as $kolom) {
                    $isi = trim($baris[$kolom]);
                    if (in_array($kolom, $numerik, true)) {
                        if ($isi === '' && ! in_array($kolom, ['Longitude', 'Latitude'], true)) {
                            $data[$kolom] = null;

                            continue;
                        }
                        if (! is_numeric($isi) || ! is_finite((float) $isi)) {
                            $this->gagal('dataset_csv', "Nilai {$kolom} harus berupa angka atau kosong jika tidak ada observasi.");
                        }
                        $angka = (float) $isi;
                        if ((in_array($kolom, $indeks, true) && ($angka < -1 || $angka > 1))
                            || (in_array($kolom, ['Hujan_Harian', 'Hujan_7_Hari', 'Jumlah_Hotspot_Piksel'], true) && $angka < 0)
                            || ($kolom === 'Jumlah_Hotspot_Piksel' && floor($angka) !== $angka)
                            || ($kolom === 'Latitude' && abs($angka) > 90)
                            || ($kolom === 'Longitude' && abs($angka) > 180)) {
                            $this->gagal('dataset_csv', "Nilai {$kolom} di luar rentang yang diperbolehkan.");
                        }
                        $data[$kolom] = $angka;
                    } else {
                        if ($isi === '' || mb_strlen($isi) > 200 || ! mb_check_encoding($isi, 'UTF-8')) {
                            $this->gagal('dataset_csv', "Nilai {$kolom} wajib diisi dengan teks UTF-8 maksimal 200 karakter.");
                        }
                        if ($kolom === 'Tanggal_Kebakaran') {
                            $tanggal = \DateTimeImmutable::createFromFormat('!Y-m-d', $isi);
                            if ($tanggal === false || $tanggal->format('Y-m-d') !== $isi) {
                                $this->gagal('dataset_csv', 'Tanggal_Kebakaran harus berupa tanggal valid YYYY-MM-DD.');
                            }
                        }
                        $data[$kolom] = $isi;
                    }
                }
                $hasil[] = $data;
            }

            if ($hasil === []) {
                $this->gagal('dataset_csv', 'CSV harus berisi minimal satu baris hasil analisis.');
            }

            return $hasil;
        } finally {
            fclose($handle);
        }
    }

    /** @return array<string, mixed> */
    private function bacaWilayah(UploadedFile $file): array
    {
        try {
            $data = json_decode($file->getContent(), true, 128, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->gagal('wilayah_geojson', 'GeoJSON tidak valid. Ekspor Wilayah_PyroGuard dari GEE sebagai GeoJSON.');
        }

        if (! is_array($data) || ($data['type'] ?? null) !== 'FeatureCollection'
            || ! is_array($data['features'] ?? null) || ! array_is_list($data['features'])
            || count($data['features']) < 1 || count($data['features']) > 1000) {
            $this->gagal('wilayah_geojson', 'Wilayah harus berupa FeatureCollection berisi 1–1.000 fitur poligon.');
        }
        if (isset($data['crs']) && ! in_array(data_get($data, 'crs.properties.name'), ['EPSG:4326', 'urn:ogc:def:crs:OGC:1.3:CRS84', 'urn:ogc:def:crs:EPSG::4326'], true)) {
            $this->gagal('wilayah_geojson', 'Gunakan GeoJSON WGS 84 (EPSG:4326), bukan koordinat proyeksi.');
        }

        $fitur = [];
        $jumlahTitik = 0;
        foreach ($data['features'] as $feature) {
            $geometry = $feature['geometry'] ?? null;
            if (($feature['type'] ?? null) !== 'Feature' || ! in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true)) {
                $this->gagal('wilayah_geojson', 'Wilayah hanya boleh berisi Polygon atau MultiPolygon. Lokasi kebakaran disimpan sebagai point terpisah di CSV.');
            }
            $poligons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates'] ?? null] : ($geometry['coordinates'] ?? null);
            if (! is_array($poligons) || ! array_is_list($poligons) || $poligons === []) {
                $this->gagal('wilayah_geojson', 'Koordinat poligon tidak boleh kosong.');
            }
            foreach ($poligons as $polygon) {
                if (! is_array($polygon) || ! array_is_list($polygon) || $polygon === []) {
                    $this->gagal('wilayah_geojson', 'Ring poligon tidak boleh kosong.');
                }
                foreach ($polygon as $ring) {
                    if (! is_array($ring) || ! array_is_list($ring) || count($ring) < 4 || $ring[0] !== $ring[array_key_last($ring)]) {
                        $this->gagal('wilayah_geojson', 'Setiap ring harus tertutup dan memiliki minimal empat koordinat.');
                    }
                    $jumlahTitik += count($ring);
                    if ($jumlahTitik > 250000) {
                        $this->gagal('wilayah_geojson', 'GeoJSON melebihi 250.000 titik. Sederhanakan geometri di QGIS terlebih dahulu.');
                    }
                    foreach ($ring as $point) {
                        if (! is_array($point) || ! array_is_list($point) || count($point) < 2
                            || ! is_numeric($point[0]) || ! is_numeric($point[1])
                            || ! is_finite((float) $point[0]) || ! is_finite((float) $point[1])
                            || abs((float) $point[0]) > 180 || abs((float) $point[1]) > 90) {
                            $this->gagal('wilayah_geojson', 'Koordinat harus berurutan longitude, latitude dalam derajat WGS 84.');
                        }
                    }
                }
            }
            $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
            $nama = $properties['nama'] ?? $properties['WADMKK'] ?? 'Wilayah analisis GEE';
            $fitur[] = ['type' => 'Feature', 'properties' => ['nama' => is_string($nama) ? mb_substr($nama, 0, 200) : 'Wilayah analisis GEE'], 'geometry' => $geometry];
        }

        return ['type' => 'FeatureCollection', 'features' => $fitur];
    }

    private function gagal(string $field, string $pesan): never
    {
        throw ValidationException::withMessages([$field => $pesan]);
    }
}
