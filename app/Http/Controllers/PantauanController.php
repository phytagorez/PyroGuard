<?php

namespace App\Http\Controllers;

use App\Models\LaporanAsap;
use App\Models\SumberAir;
use App\Models\TitikPanas;
use App\Models\Zona;
use App\Services\LayananCuacaOpenMeteo;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class PantauanController extends Controller
{
    /**
     * Tampilkan halaman utama Pantauan Risiko Kawasan Bromo (§7.1 design.md).
     */
    public function __invoke(LayananCuacaOpenMeteo $layananCuaca): View
    {
        // 1. Ambil 6 zona pemantauan beserta relasi lengkap
        $zonas = Zona::with([
            'risikoTerbaru',
            'siagaAktif',
            'sumberAir',
            'cuacaHarian' => fn ($q) => $q->where('sumber', 'aktual')->orderBy('tanggal', 'desc')->take(30),
        ])->get();

        // Urutkan zona berdasarkan tingkat keparahan risiko (FDI tertinggi di atas)
        $zonasUrut = $zonas->sortByDesc(function (Zona $z) {
            $fdi = $z->risikoTerbaru?->indeks_bahaya_api ?? 0;
            $isSiaga = in_array($z->risikoTerbaru?->status_siaga, ['SIAGA_1', 'SIAGA_OTOMATIS']) ? 1000 : 0;

            return $isSiaga + $fdi;
        })->values();

        // Zona dengan risiko paling mendesak di kawasan
        $zonaTertinggi = $zonasUrut->first();

        // 2. Status kesegaran data cuaca
        $kesegaran = $zonaTertinggi ? $layananCuaca->cekKesegaranData($zonaTertinggi) : [
            'status' => 'segar',
            'label' => 'DIPERBARUI 07:00 WIB · SUMBER OPEN-METEO',
            'durasi_jam' => 0,
        ];

        // 3. Titik panas satelit FIRMS (72 jam terakhir)
        $batasWaktu = Carbon::now()->subHours(72);
        $hotspots = TitikPanas::with('zona:id,kode,nama')
            ->where('terdeteksi_pada', '>=', $batasWaktu)
            ->orderBy('terdeteksi_pada', 'desc')
            ->get();

        // 4. Laporan terverifikasi warga
        $laporanTerverifikasi = LaporanAsap::with('zona:id,kode,nama')
            ->where('hasil_verifikasi', 'api_nyata')
            ->orderBy('diverifikasi_pada', 'desc')
            ->get();

        // 5. Sumber air & titik kumpul
        $sumberAirs = SumberAir::with('zona:id,kode,nama')->get();

        return view('pantauan.index', [
            'zonas' => $zonasUrut,
            'zonaTertinggi' => $zonaTertinggi,
            'kesegaran' => $kesegaran,
            'hotspots' => $hotspots,
            'laporanTerverifikasi' => $laporanTerverifikasi,
            'sumberAirs' => $sumberAirs,
        ]);
    }
}
