<?php

namespace App\Console\Commands;

use App\Models\Zona;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:import-dataset-desa')]
#[Description('Import dataset analisis PyroGuard per desa')]
class ImportDatasetDesa extends Command
{
    public function handle()
    {
        $path = storage_path(
            'app/datasets/Desa_PyroGuard_Skor.geojson'
        );

        if (!file_exists($path)) {
            $this->error('File GeoJSON tidak ditemukan.');
            return self::FAILURE;
        }

        $geojson = json_decode(
            file_get_contents($path),
            true
        );

        if (
            !isset($geojson['features']) ||
            !is_array($geojson['features'])
        ) {
            $this->error('Format GeoJSON tidak valid.');
            return self::FAILURE;
        }

        $berhasil = 0;

        foreach ($geojson['features'] as $feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            if (!$geometry || empty($properties['Desa'])) {
                $this->warn('Feature dilewati karena tidak lengkap.');
                continue;
            }

            $desa = $properties['Desa'];
            $kecamatan = $properties['Kecamatan'] ?? '';
            $kabupaten = $properties['Kabupaten'] ?? '';

            // Kode unik dan stabil untuk setiap desa
            $kode = 'DS-' . strtoupper(substr(
                hash('sha256', "$kabupaten|$kecamatan|$desa"),
                0,
                12
            ));

            // Mengambil seluruh koordinat polygon
            $koordinat = [];

            $ambilKoordinat = function ($data) use (
                &$koordinat,
                &$ambilKoordinat
            ) {
                if (
                    isset($data[0], $data[1]) &&
                    is_numeric($data[0]) &&
                    is_numeric($data[1])
                ) {
                    $koordinat[] = $data;
                    return;
                }

                foreach ($data as $item) {
                    if (is_array($item)) {
                        $ambilKoordinat($item);
                    }
                }
            };

            $ambilKoordinat($geometry['coordinates']);

            if (empty($koordinat)) {
                $this->warn("Polygon desa $desa tidak memiliki koordinat.");
                continue;
            }

            // Titik tengah perkiraan berdasarkan bounding box
            $lng = array_column($koordinat, 0);
            $lat = array_column($koordinat, 1);

            $tengahLng = (min($lng) + max($lng)) / 2;
            $tengahLat = (min($lat) + max($lat)) / 2;

            Zona::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $desa,
                    'deskripsi_lokasi' => $properties['Label']
                        ?? "$desa, $kecamatan, $kabupaten",
                    'jenis_lahan' => null,
                    'akses_wisata' => null,
                    'poligon_geojson' => $geometry,
                    'data_analisis' => $properties,
                    'titik_tengah_lat' => $tengahLat,
                    'titik_tengah_lng' => $tengahLng,
                ]
            );

            $berhasil++;
            $this->line("Desa diproses: $desa");
        }

        $this->info("Import selesai: $berhasil desa.");

        return self::SUCCESS;
    }
}
