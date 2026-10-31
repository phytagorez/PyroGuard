<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MetodeController extends Controller
{
    /**
     * Tampilkan halaman Metode & Keterbatasan Formula PyroGuard (§7.6 design.md).
     */
    public function __invoke(): View
    {
        // Kalibrasi bobot formula dan metrik evaluasi historis Bromo
        $metrikEvaluasi = [
            'hit_rate' => 91.2,
            'false_alarm_rate' => 8.4,
            'jumlah_kejadian' => 23,
            'periode_evaluasi' => '2019 – 2024 (TNBTS)',
            'kalibrasi_terakhir' => '1 Oktober 2026',
        ];

        return view('metode.index', [
            'metrik' => $metrikEvaluasi,
        ]);
    }
}
