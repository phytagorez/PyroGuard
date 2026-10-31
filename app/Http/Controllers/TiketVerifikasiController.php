<?php

namespace App\Http\Controllers;

use App\Models\LaporanAsap;
use App\Models\LogSiaran;
use App\Services\LayananLaporan;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TiketVerifikasiController extends Controller
{
    /**
     * Tampilkan antrean tiket verifikasi petugas lapangan TNBTS (§6.9 & §7.7 design.md).
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'semua');
        $tiketNomor = $request->query('tiket');

        // Koordinat acuan petugas (default: Pos Jaga Cemoro Lawang TNBTS)
        $petugasLat = (float) $request->query('lat', -7.9210000);
        $petugasLng = (float) $request->query('lng', 112.9600000);

        // Ambil tiket beserta relasi lengkap
        $query = LaporanAsap::with([
            'zona:id,kode,nama',
            'pelapor:id,nama,nomor_telepon,skor_kepercayaan',
            'verifikator:id,nama',
            'tiketInduk:id,nomor_tiket',
        ]);

        if ($statusFilter !== 'semua') {
            $query->where('status_tiket', $statusFilter);
        }

        $semuaTiket = $query->get();

        // Hitung jarak Haversine (km) dari koordinat petugas ke setiap laporan
        $semuaTiket->each(function (LaporanAsap $tiket) use ($petugasLat, $petugasLng) {
            $tiket->jarak_km = $this->hitungJarakHaversine(
                $petugasLat,
                $petugasLng,
                $tiket->latitude,
                $tiket->longitude
            );
        });

        // Urutkan berdasarkan jarak terdekat dari posisi petugas (§7.7)
        $tiketTerurut = $semuaTiket->sortBy('jarak_km')->values();

        // Tentukan tiket aktif yang dibuka di panel detail 1 layar
        $tiketAktif = null;
        if ($tiketNomor) {
            $tiketAktif = $tiketTerurut->firstWhere('nomor_tiket', $tiketNomor);
        }

        if (! $tiketAktif && $tiketTerurut->isNotEmpty()) {
            $tiketAktif = $tiketTerurut->first();
        }

        // Tiket aktif lain untuk opsi penggabungan (ganda)
        $tiketLainAktif = LaporanAsap::where('status_tiket', '!=', 'selesai')
            ->where('id', '!=', $tiketAktif?->id ?? 0)
            ->select('id', 'nomor_tiket', 'jenis_temuan')
            ->get();

        // Penghitung jumlah tiket per status untuk badge chip filter
        $jumlahPerStatus = [
            'semua' => LaporanAsap::count(),
            'baru' => LaporanAsap::where('status_tiket', 'baru')->count(),
            'menuju_lokasi' => LaporanAsap::where('status_tiket', 'menuju_lokasi')->count(),
            'terverifikasi' => LaporanAsap::where('status_tiket', 'terverifikasi')->count(),
            'ditangani' => LaporanAsap::where('status_tiket', 'ditangani')->count(),
        ];

        return view('petugas.index', [
            'tikets' => $tiketTerurut,
            'tiketAktif' => $tiketAktif,
            'tiketLainAktif' => $tiketLainAktif,
            'statusFilter' => $statusFilter,
            'jumlahPerStatus' => $jumlahPerStatus,
            'petugasLat' => $petugasLat,
            'petugasLng' => $petugasLng,
        ]);
    }

    /**
     * Ubah status tiket menjadi 'menuju_lokasi' (§7.7).
     */
    public function menujuLokasi(LaporanAsap $laporan, LayananLaporan $layananLaporan): RedirectResponse
    {
        $layananLaporan->perbaruiStatusTiket($laporan, 'menuju_lokasi', auth()->id());

        return redirect()->route('petugas.index', ['tiket' => $laporan->nomor_tiket])
            ->with('pesan', "Status diperbarui: Regu menuju lokasi laporan {$laporan->nomor_tiket}.");
    }

    /**
     * Proses hasil verifikasi lapangan 3 tombol (§6.9 & §7.7).
     */
    public function verifikasi(Request $request, LaporanAsap $laporan, LayananLaporan $layananLaporan): RedirectResponse
    {
        $validated = $request->validate([
            'hasil_verifikasi' => ['required', 'in:api_nyata,bukan_api,ganda'],
            'tiket_induk_id' => ['nullable', 'exists:laporan_asap,id'],
            'foto_verifikasi' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,heic', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $laporanDiperbarui = $layananLaporan->verifikasiLaporan($laporan, $validated, auth()->id());

        // Jika terverifikasi Api Nyata, catat siaran radius 3 km (§7.7 & §14)
        if ($validated['hasil_verifikasi'] === 'api_nyata') {
            LogSiaran::create([
                'jenis_siaran' => 'titik_api',
                'zona_id' => $laporan->zona_id,
                'kanal' => 'grup_petugas',
                'judul_pesan' => "TITIK API TERVERIFIKASI — {$laporan->zona?->kode}",
                'isi_pesan' => "Asap terverifikasi pada koordinat {$laporan->latitude}, {$laporan->longitude}. Regu pemadam TNBTS di lokasi. Warga dan wisatawan dalam radius 3 km diimbau menjauh.",
                'disetujui_oleh' => auth()->id(),
                'jumlah_penerima' => 24,
                'adalah_simulasi' => false,
                'dikirim_pada' => Carbon::now(),
            ]);
        }

        $pesanHasil = match ($validated['hasil_verifikasi']) {
            'api_nyata' => "Tiket {$laporan->nomor_tiket} diverifikasi sebagai API NYATA. Titik tampil di peta & siaran darurat disiapkan.",
            'bukan_api' => "Tiket {$laporan->nomor_tiket} ditandai BUKAN API. Laporan ditutup tanpa siaran.",
            'ganda' => "Tiket {$laporan->nomor_tiket} digabungkan ke tiket induk.",
            default => 'Verifikasi berhasil disimpan.',
        };

        return redirect()->route('petugas.index', ['tiket' => $laporan->nomor_tiket])
            ->with('pesan', $pesanHasil);
    }

    /**
     * Tandai penanganan api di lapangan (Terverifikasi -> Ditangani -> Selesai).
     */
    public function tandaiDitangani(LaporanAsap $laporan, LayananLaporan $layananLaporan): RedirectResponse
    {
        $statusBaru = match ($laporan->status_tiket) {
            'terverifikasi' => 'ditangani',
            'ditangani' => 'selesai',
            default => 'selesai',
        };

        $layananLaporan->perbaruiStatusTiket($laporan, $statusBaru, auth()->id());

        return redirect()->route('petugas.index', ['tiket' => $laporan->nomor_tiket])
            ->with('pesan', "Status tiket {$laporan->nomor_tiket} kini: ".strtoupper($statusBaru));
    }

    /**
     * Hitung jarak jarak garis lurus bola bumi (Haversine formula) dalam kilometer.
     */
    protected function hitungJarakHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }
}
