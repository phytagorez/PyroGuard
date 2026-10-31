<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TitikPanas;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TitikPanasController extends Controller
{
    /**
     * Dapatkan titik panas satelit NASA FIRMS (72 jam terakhir).
     *
     * Endpoint: GET /api/v1/hotspots
     */
    public function index(Request $request): JsonResponse
    {
        $jamMundur = (int) $request->query('hours', 72);
        $batasWaktu = Carbon::now()->subHours($jamMundur);

        $query = TitikPanas::with('zona:id,kode,nama')
            ->where('terdeteksi_pada', '>=', $batasWaktu)
            ->orderBy('terdeteksi_pada', 'desc');

        if ($request->has('zona')) {
            $query->whereHas('zona', fn ($q) => $q->where('kode', strtoupper($request->query('zona'))));
        }

        $hotspots = $query->get();

        return response()->json([
            'status' => 'sukses',
            'rentang_waktu_jam' => $jamMundur,
            'total' => $hotspots->count(),
            'data' => $hotspots->map(fn (TitikPanas $tp) => [
                'id' => $tp->id,
                'zona' => $tp->zona ? [
                    'id' => $tp->zona->id,
                    'kode' => $tp->zona->kode,
                    'nama' => $tp->zona->nama,
                ] : null,
                'latitude' => $tp->latitude,
                'longitude' => $tp->longitude,
                'kecerahan_kelvin' => $tp->kecerahan,
                'sumber_satelit' => $tp->sumber_satelit,
                'terdeteksi_pada' => $tp->terdeteksi_pada?->toIso8601String(),
                'waktu_relatif' => $tp->terdeteksi_pada?->diffForHumans(),
            ]),
        ]);
    }
}
