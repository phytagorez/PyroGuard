<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaporanAsapRequest;
use App\Http\Requests\VerifyLaporanAsapRequest;
use App\Models\LaporanAsap;
use App\Services\LayananLaporan;
use Illuminate\Http\JsonResponse;

class LaporanAsapController extends Controller
{
    /**
     * Kirim laporan asap baru dari warga/wisatawan (§6.8 & §7.3 design.md).
     *
     * Endpoint: POST /api/v1/reports
     */
    public function store(StoreLaporanAsapRequest $request, LayananLaporan $layananLaporan): JsonResponse
    {
        $pelaporId = $request->user()?->id;
        $ipPelapor = $request->ip();

        $laporan = $layananLaporan->buatLaporan(
            $request->validated(),
            $pelaporId,
            $ipPelapor
        );

        $laporan->load('zona:id,kode,nama');

        return response()->json([
            'status' => 'sukses',
            'pesan' => "Tiket {$laporan->nomor_tiket} berhasil dibuat. Petugas terdekat segera diberi tahu.",
            'data' => [
                'id' => $laporan->id,
                'nomor_tiket' => $laporan->nomor_tiket,
                'zona' => $laporan->zona ? [
                    'id' => $laporan->zona->id,
                    'kode' => $laporan->zona->kode,
                    'nama' => $laporan->zona->nama,
                ] : null,
                'jenis_temuan' => $laporan->jenis_temuan,
                'status_tiket' => $laporan->status_tiket,
                'latitude' => $laporan->latitude,
                'longitude' => $laporan->longitude,
                'akurasi_gps' => $laporan->akurasi_gps,
                'catatan' => $laporan->catatan,
                'dibuat_pada' => $laporan->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Verifikasi laporan oleh petugas lapangan TNBTS (§6.9 & §7.7 design.md).
     *
     * Endpoint: PATCH /api/v1/reports/{id}/verify
     */
    public function verify(VerifyLaporanAsapRequest $request, string $id, LayananLaporan $layananLaporan): JsonResponse
    {
        $laporan = LaporanAsap::where('id', $id)
            ->orWhere('nomor_tiket', $id)
            ->orWhere('nomor_tiket', '#'.ltrim($id, '#'))
            ->firstOrFail();

        $petugasId = $request->user()?->id;

        $laporanTerverifikasi = $layananLaporan->verifikasiLaporan(
            $laporan,
            $request->validated(),
            $petugasId
        );

        return response()->json([
            'status' => 'sukses',
            'pesan' => "Laporan {$laporanTerverifikasi->nomor_tiket} berhasil diverifikasi sebagai '{$laporanTerverifikasi->hasil_verifikasi}'.",
            'data' => $laporanTerverifikasi,
        ]);
    }
}
