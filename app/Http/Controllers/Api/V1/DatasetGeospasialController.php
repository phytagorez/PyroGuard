<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LayananDatasetGeospasial;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatasetGeospasialController extends Controller
{
    public function index(LayananDatasetGeospasial $dataset): JsonResponse
    {
        $tersimpan = $dataset->tersimpan();

        return response()->json([
            'status' => 'sukses',
            'data' => $dataset->metadata(),
            'analisis' => $tersimpan['analisis'] ?? null,
            'tautan' => [
                'geojson' => route('api.dataset.batas'),
                'qgis' => asset('data/bromo-qgis-portabel.zip'),
                'wilayah' => isset($tersimpan['wilayah']) ? route('api.dataset.wilayah') : null,
            ],
        ]);
    }

    public function wilayah(LayananDatasetGeospasial $dataset): JsonResponse
    {
        $tersimpan = $dataset->tersimpan();
        abort_unless(isset($tersimpan['wilayah']), 404, 'Poligon wilayah analisis GEE belum diimpor.');

        return response()->json($tersimpan['wilayah'], headers: ['Content-Type' => 'application/geo+json; charset=UTF-8']);
    }

    public function batas(): BinaryFileResponse
    {
        return response()->file(public_path('data/bromo-administrasi.geojson'), [
            'Content-Type' => 'application/geo+json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
