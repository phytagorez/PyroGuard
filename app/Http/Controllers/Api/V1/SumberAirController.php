<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SumberAir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SumberAirController extends Controller
{
    /**
     * Dapatkan titik sumber air, embung pemadam, pos jaga, dan jalur evakuasi.
     *
     * Endpoint: GET /api/v1/water-points
     */
    public function index(Request $request): JsonResponse
    {
        $query = SumberAir::with('zona:id,kode,nama')->orderBy('nama');

        if ($request->has('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }

        if ($request->has('zona')) {
            $query->whereHas('zona', fn ($q) => $q->where('kode', strtoupper($request->query('zona'))));
        }

        $items = $query->get();

        return response()->json([
            'status' => 'sukses',
            'total' => $items->count(),
            'data' => $items->map(fn (SumberAir $sa) => [
                'id' => $sa->id,
                'zona' => $sa->zona ? [
                    'id' => $sa->zona->id,
                    'kode' => $sa->zona->kode,
                    'nama' => $sa->zona->nama,
                ] : null,
                'nama' => $sa->nama,
                'jenis' => $sa->jenis,
                'latitude' => $sa->latitude,
                'longitude' => $sa->longitude,
                'debit_air' => $sa->debit_air,
                'status_siaga' => $sa->status_siaga,
                'jalur_evakuasi_geojson' => $sa->jalur_evakuasi_geojson,
            ]),
        ]);
    }
}
