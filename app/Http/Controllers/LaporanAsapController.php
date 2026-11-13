<?php

namespace App\Http\Controllers;

use App\Models\Zona;
use App\Services\LayananLaporan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LaporanAsapController extends Controller
{
    /**
     * Tampilkan formulir Lapor Asap (§6.8 & §7.3 design.md).
     */
    public function create(Request $request, LayananLaporan $layananLaporan): View
    {
        $zonas = Zona::desa()->select('id', 'kode', 'nama', 'titik_tengah_lat', 'titik_tengah_lng', 'poligon_geojson', 'data_analisis')->orderBy('nama')->get();

        $batasLaju = $layananLaporan->hitungSisaBatasLaju(
            auth()->id(),
            $request->ip()
        );

        $zonaPilihan = null;
        if ($request->filled('zona')) {
            $zonaPilihan = $zonas->firstWhere('kode', strtoupper($request->query('zona')));
        }

        return view('lapor.index', [
            'statusUrl' => session('status_url'),
            'zonas' => $zonas,
            'batasLaju' => $batasLaju,
            'zonaPilihan' => $zonaPilihan,
        ]);
    }

    /**
     * Simpan laporan asap baru dari warga/wisatawan.
     */
    public function store(Request $request, LayananLaporan $layananLaporan): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'akurasi_gps' => 'nullable|numeric|min:0',
            'jenis_temuan' => 'required|in:asap_tipis,asap_tebal,api_terlihat,bekas_terbakar',
            'foto_1' => 'nullable|file|mimes:jpeg,jpg,png,webp,heic|max:5120',
            'foto_2' => 'nullable|file|mimes:jpeg,jpg,png,webp,heic|max:5120',
            'catatan' => 'nullable|string|max:500',
            'zona_id' => 'nullable|exists:zona,id',
        ], [
            'latitude.required' => 'Koordinat lintang lokasi wajib ditentukan.',
            'longitude.required' => 'Koordinat bujur lokasi wajib ditentukan.',
            'jenis_temuan.required' => 'Pilih salah satu jenis temuan asap atau api.',
            'foto_1.max' => 'Ukuran foto maksimal 5 MB.',
            'foto_2.max' => 'Ukuran foto maksimal 5 MB.',
            'foto_1.mimes' => 'Format foto harus berupa JPG, PNG, WEBP, atau HEIC.',
            'foto_2.mimes' => 'Format foto harus berupa JPG, PNG, WEBP, atau HEIC.',
        ]);

        try {
            $laporan = $layananLaporan->buatLaporan(
                $validated,
                auth()->id(),
                $request->ip()
            );

            $laporan->load('zona:id,kode,nama,data_analisis');

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'pesan' => "Tiket {$laporan->nomor_tiket} dibuat.".($laporan->petugas_diberi_tahu ? ' Petugas sudah diberi tahu.' : ' Laporan masuk antrean petugas.'),
                    'data' => $laporan,
                ], 201);
            }

            return redirect()->route('lapor.index')
                ->with('tiket_berhasil', $laporan)
                ->with('status_url', route('lapor.status', $laporan->kode_pantau));
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Halaman status publik laporan. Hanya menampilkan status dan desa,
     * tanpa koordinat, foto, atau data pelapor.
     */
    public function status(string $kode)
    {
        $laporan = \App\Models\LaporanAsap::with('zona:id,nama,data_analisis')
            ->where('kode_pantau', $kode)
            ->firstOrFail();

        return view('lapor.status', ['laporan' => $laporan]);
    }
}
