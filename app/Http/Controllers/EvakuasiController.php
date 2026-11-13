<?php

namespace App\Http\Controllers;

use App\Models\SumberAir;
use App\Models\Zona;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EvakuasiController extends Controller
{
    /**
     * Tampilkan halaman Jalur Evakuasi, Sumber Air & Embung Bromo (§7.5 design.md).
     */
    public function __invoke(Request $request): View
    {
        // 1. Ambil seluruh data logistik air, embung, dan titik kumpul
        $sumberAirs = SumberAir::with('zona:id,kode,nama')->get();

        // 2. Ambil data zona untuk konteks risiko dan angin kawasan
        $zonas = Zona::with('risikoTerbaru')->get();

        // 3. Ekstrak data jalur evakuasi berformat GeoJSON
        $jalurEvakuasi = $sumberAirs->filter(fn ($item) => ! empty($item->jalur_evakuasi_geojson))
            ->map(fn ($item) => [
                'nama' => $item->nama,
                'zona' => $item->zona?->nama,
                'geojson' => $item->jalur_evakuasi_geojson,
            ])
            ->values();

        // 4. Cari angin dominan dari zona dengan risiko tertinggi
        $zonaBahaya = $zonas->sortByDesc(fn ($z) => $z->risikoTerbaru?->indeks_bahaya_api ?? 0)->first();
        $arahAngin = $zonaBahaya?->risikoTerbaru?->arah_angin;
        $kecepatanAngin = $zonaBahaya?->risikoTerbaru?->kecepatan_angin_kmj;
        $kecepatanAngin = $kecepatanAngin === null ? null : round($kecepatanAngin);

        return view('evakuasi.index', [
            'sumberAirs' => $sumberAirs,
            'zonas' => $zonas,
            'jalurEvakuasi' => $jalurEvakuasi,
            'arahAngin' => $arahAngin,
            'kecepatanAngin' => $kecepatanAngin,
            'zonaBahaya' => $zonaBahaya,
            'tabPilihan' => $request->query('tab', 'semua'),
            'adaDataContoh' => $sumberAirs->contains(fn ($s) => $s->adalah_contoh),
            'semuaContoh' => $sumberAirs->isNotEmpty() && $sumberAirs->every(fn ($s) => $s->adalah_contoh),
        ]);
    }
}
