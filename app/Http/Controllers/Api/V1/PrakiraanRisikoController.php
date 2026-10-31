<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller Prakiraan Risiko Kebakaran Zona Bromo
 * Mengembalikan kontrak JSON persis sesuai spesifikasi §16.2 design.md.
 */
class PrakiraanRisikoController extends Controller
{
    /**
     * Dapatkan detail prakiraan risiko kebakaran untuk satu zona.
     *
     * Endpoint: GET /api/v1/forecast/risk-zone?zone=BRM-04
     */
    public function show(Request $request): JsonResponse
    {
        $kodeZona = $request->query('zone', 'BRM-04');

        $zona = Zona::where('kode', strtoupper($kodeZona))
            ->with(['risikoTerbaru'])
            ->first();

        if (! $zona) {
            return response()->json([
                'pesan' => "Zona dengan kode '{$kodeZona}' tidak ditemukan.",
            ], 404);
        }

        $risiko = $zona->risikoTerbaru;

        if (! $risiko) {
            return response()->json([
                'pesan' => "Data kalkulasi risiko untuk zona '{$zona->kode}' belum tersedia.",
            ], 404);
        }

        // Kontrak Data Mengikat §16.2 design.md
        return response()->json([
            'zone_id' => $zona->kode,
            'name' => $zona->nama,
            'consecutive_dry_days' => (int) $risiko->hari_tanpa_hujan,
            'projection' => [
                'h24' => (int) $risiko->proyeksi_cdd_24j,
                'h48' => (int) $risiko->proyeksi_cdd_48j,
                'h72' => (int) $risiko->proyeksi_cdd_72j,
            ],
            'fire_danger_index' => (int) $risiko->indeks_bahaya_api,
            'risk_category' => $risiko->kategori_risiko,
            'factor_contribution' => [
                'cdd' => (int) round($risiko->poin_cdd),
                'humidity' => (int) round($risiko->poin_kelembapan),
                'wind' => (int) round($risiko->poin_angin),
            ],
            'peak_risk_window' => $risiko->jam_rentan,
            'wind' => [
                'speed_kmh' => (int) round($risiko->kecepatan_angin_kmj),
                'from' => $risiko->arah_angin,
            ],
            'alert_status' => $risiko->status_siaga,
            'data_state' => $risiko->status_data === 'aktual' ? 'actual' : 'forecast',
            'computed_at' => Carbon::parse($risiko->dihitung_pada)->toIso8601String(),
        ]);
    }
}
