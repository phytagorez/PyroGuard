<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveSiagaRequest;
use App\Models\Pengguna;
use App\Models\Zona;
use App\Services\LayananSiaga;
use Illuminate\Http\JsonResponse;

class StatusSiagaController extends Controller
{
    /**
     * Persetujuan (Siaga 1) atau pencabutan status siaga satu zona oleh Admin BPBD (§6.10 & §7.8 design.md).
     *
     * Endpoint: POST /api/v1/alerts/{zone}/approve
     */
    public function approve(ApproveSiagaRequest $request, string $zone, LayananSiaga $layananSiaga): JsonResponse
    {
        $zona = Zona::where('kode', strtoupper($zone))
            ->orWhere('id', $zone)
            ->firstOrFail();

        // Ambil admin pengguna aktif, atau fallback akun admin default jika dalam konteks prototipe
        $admin = $request->user() ?? Pengguna::where('peran', 'admin')->first();
        $adminId = $admin?->id;

        $tindakan = $request->validated('tindakan');
        $catatan = $request->validated('catatan');

        $statusSiaga = $layananSiaga->prosesStatusSiaga($zona, $tindakan, $catatan, $adminId);

        $statusSiaga->load(['penyetuju:id,nama,email', 'pencabut:id,nama,email']);

        $labelAksi = $tindakan === 'setujui' ? 'Siaga 1 resmi disetujui' : 'Status Siaga resmi dicabut';

        return response()->json([
            'status' => 'sukses',
            'pesan' => "{$labelAksi} untuk zona {$zona->nama} ({$zona->kode}). Siaran darurat telah dicatat di log siaran.",
            'data' => [
                'zona' => [
                    'kode' => $zona->kode,
                    'nama' => $zona->nama,
                ],
                'status_siaga' => $statusSiaga->status,
                'catatan' => $statusSiaga->catatan_persetujuan,
                'disetujui_oleh' => $statusSiaga->penyetuju?->nama,
                'disetujui_pada' => $statusSiaga->disetujui_pada?->toIso8601String(),
                'dicabut_oleh' => $statusSiaga->pencabut?->nama,
                'dicabut_pada' => $statusSiaga->dicabut_pada?->toIso8601String(),
            ],
        ]);
    }
}
