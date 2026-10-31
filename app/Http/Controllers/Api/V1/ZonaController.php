<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Services\LayananCuacaOpenMeteo;
use Illuminate\Http\JsonResponse;

class ZonaController extends Controller
{
    /**
     * Dapatkan daftar seluruh zona pemantauan Bromo beserta status risiko dan poligon GeoJSON.
     *
     * Endpoint: GET /api/v1/zones
     */
    public function index(LayananCuacaOpenMeteo $layananCuaca): JsonResponse
    {
        $zonas = Zona::with(['risikoTerbaru', 'siagaAktif'])->orderBy('kode')->get();

        $data = $zonas->map(function (Zona $zona) use ($layananCuaca) {
            $risiko = $zona->risikoTerbaru;
            $kesegaran = $layananCuaca->cekKesegaranData($zona);

            return [
                'id' => $zona->id,
                'kode' => $zona->kode,
                'nama' => $zona->nama,
                'deskripsi_lokasi' => $zona->deskripsi_lokasi,
                'jenis_lahan' => $zona->jenis_lahan,
                'akses_wisata' => $zona->akses_wisata,
                'titik_tengah' => [
                    'lat' => $zona->titik_tengah_lat,
                    'lng' => $zona->titik_tengah_lng,
                ],
                'poligon_geojson' => $zona->poligon_geojson,
                'risiko' => $risiko ? [
                    'cdd' => $risiko->hari_tanpa_hujan,
                    'proyeksi' => [
                        'h24' => $risiko->proyeksi_cdd_24j,
                        'h48' => $risiko->proyeksi_cdd_48j,
                        'h72' => $risiko->proyeksi_cdd_72j,
                    ],
                    'fdi' => $risiko->indeks_bahaya_api,
                    'kategori' => $risiko->kategori_risiko,
                    'status_siaga' => $risiko->status_siaga,
                    'jam_rentan' => $risiko->jam_rentan,
                    'kecepatan_angin_kmj' => (float) $risiko->kecepatan_angin_kmj,
                    'arah_angin' => $risiko->arah_angin,
                    'dihitung_pada' => $risiko->dihitung_pada?->toIso8601String(),
                ] : null,
                'kesegaran_data' => $kesegaran,
            ];
        });

        return response()->json([
            'status' => 'sukses',
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * Dapatkan detail satu zona beserta riwayat cuaca tren.
     *
     * Endpoint: GET /api/v1/zones/{kode}
     */
    public function show(string $kode): JsonResponse
    {
        $zona = Zona::where('kode', strtoupper($kode))
            ->orWhere('id', $kode)
            ->with(['risikoTerbaru', 'siagaAktif', 'sumberAir', 'cuacaHarian' => function ($q) {
                $q->orderBy('tanggal', 'desc')->take(30);
            }])
            ->first();

        if (! $zona) {
            return response()->json([
                'pesan' => "Zona '{$kode}' tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'sukses',
            'data' => $zona,
        ]);
    }
}
