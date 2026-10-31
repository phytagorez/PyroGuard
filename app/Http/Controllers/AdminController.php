<?php

namespace App\Http\Controllers;

use App\Models\LaporanAsap;
use App\Models\LogSiaran;
use App\Models\StatusSiaga;
use App\Models\TitikPanas;
use App\Models\Zona;
use App\Services\LayananBotTelegram;
use App\Services\LayananNotifikasiPush;
use App\Services\LayananSiaga;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /**
     * Layar 1: Tinjauan Prediksi 6 Zona Bromo (§7.8 design.md).
     */
    public function tinjauan(Request $request): View
    {
        $hanyaPerluTinjauan = $request->boolean('perlu_tinjauan');

        $zonas = Zona::with([
            'risikoTerbaru',
            'siagaAktif',
            'sumberAir',
        ])->get();

        if ($hanyaPerluTinjauan) {
            $zonas = $zonas->filter(function ($z) {
                $r = $z->risikoTerbaru;

                return $r?->status_siaga === 'SIAGA_OTOMATIS' || $r?->kategori_risiko === 'BAHAYA';
            });
        }

        return view('admin.tinjauan', [
            'zonas' => $zonas,
            'hanyaPerluTinjauan' => $hanyaPerluTinjauan,
        ]);
    }

    /**
     * Layar 2: Persetujuan Status Siaga 1 & ApprovalCard (§6.10 & §7.8).
     */
    public function siaga(LayananBotTelegram $telegram): View
    {
        $zonas = Zona::with(['risikoTerbaru', 'siagaAktif'])->get();

        // Cari zona prioritas yang berstatus SIAGA_OTOMATIS atau BAHAYA tertinggi
        $zonaSiaga = $zonas->first(fn ($z) => $z->risikoTerbaru?->status_siaga === 'SIAGA_OTOMATIS')
            ?: $zonas->sortByDesc(fn ($z) => $z->risikoTerbaru?->indeks_bahaya_api ?? 0)->first();

        // Kumpulkan bukti lapangan untuk ApprovalCard (§6.10)
        $bukti = [
            'titik_panas_7_hari' => TitikPanas::where('zona_id', $zonaSiaga?->id)
                ->where('terdeteksi_pada', '>=', Carbon::now()->subDays(7))
                ->count(),
            'laporan_terverifikasi' => LaporanAsap::where('zona_id', $zonaSiaga?->id)
                ->where('hasil_verifikasi', 'api_nyata')
                ->count(),
            'estimasi_penerima' => 128,
        ];

        // Format pratinjau pesan baku (§6.11 & §14.1)
        $risiko = $zonaSiaga?->risikoTerbaru;
        $pratinjauPesan = $telegram->formatPesan('siaga_1', $zonaSiaga, [
            'cdd' => $risiko?->hari_tanpa_hujan ?? 19,
            'fdi' => $risiko?->indeks_bahaya_api ?? 86,
            'angin' => round($risiko?->kecepatan_angin_kmj ?? 28),
        ]);

        $pratinjauPencabutan = $telegram->formatPesan('pencabutan', $zonaSiaga, [
            'fdi' => 41,
        ]);

        // Riwayat audit status siaga 10 terakhir
        $riwayatSiaga = StatusSiaga::with(['zona', 'penyetuju', 'pencabut'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.siaga', [
            'zonas' => $zonas,
            'zonaSiaga' => $zonaSiaga,
            'bukti' => $bukti,
            'pratinjauPesan' => $pratinjauPesan,
            'pratinjauPencabutan' => $pratinjauPencabutan,
            'riwayatSiaga' => $riwayatSiaga,
        ]);
    }

    /**
     * Konfirmasi 2 Langkah: Setujui Status Siaga 1 (§6.10).
     */
    public function setujuiSiaga(
        Request $request,
        Zona $zona,
        LayananSiaga $layananSiaga,
        LayananBotTelegram $telegram,
        LayananNotifikasiPush $push
    ): RedirectResponse {
        $catatan = $request->input('catatan_persetujuan');

        $layananSiaga->prosesStatusSiaga($zona, 'setujui', $catatan, auth()->id());

        // Siarkan ke kanal publik Telegram & Web-Push
        $risiko = $zona->risikoTerbaru;
        $pesan = $telegram->formatPesan('siaga_1', $zona, [
            'cdd' => $risiko?->hari_tanpa_hujan ?? 19,
            'fdi' => $risiko?->indeks_bahaya_api ?? 86,
            'angin' => round($risiko?->kecepatan_angin_kmj ?? 28),
        ]);

        $telegram->kirimSiaranKanal($pesan);
        $push->kirimSiaranPush(
            "SIAGA 1 — {$zona->nama} ({$zona->kode})",
            $pesan,
            $zona->id,
            auth()->id(),
            'siaga_1'
        );

        $waktu = Carbon::now()->format('H:i');
        $namaAdmin = auth()->user()->nama;

        return redirect()->route('admin.siaga')->with(
            'pesan',
            "Status SIAGA 1 resmi disetujui oleh {$namaAdmin} pukul {$waktu} WIB. Pesan telah disiarkan ke seluruh kanal."
        );
    }

    /**
     * Konfirmasi 2 Langkah: Cabut Status Siaga 1 (§6.10).
     */
    public function cabutSiaga(
        Request $request,
        Zona $zona,
        LayananSiaga $layananSiaga,
        LayananBotTelegram $telegram,
        LayananNotifikasiPush $push
    ): RedirectResponse {
        $catatan = $request->input('catatan_pencabutan');

        $layananSiaga->prosesStatusSiaga($zona, 'cabut', $catatan, auth()->id());

        $pesan = $telegram->formatPesan('pencabutan', $zona, ['fdi' => 41]);
        $telegram->kirimSiaranKanal($pesan);
        $push->kirimSiaranPush(
            "SIAGA 1 DICABUT — {$zona->nama}",
            $pesan,
            $zona->id,
            auth()->id(),
            'pencabutan'
        );

        return redirect()->route('admin.siaga')->with(
            'pesan',
            "Status Siaga 1 untuk {$zona->nama} resmi dicabut. Notifikasi pemulihan telah dikirim."
        );
    }

    /**
     * Layar 3: Editor Siaran Berbasis Templat (§7.8).
     */
    public function siaran(): View
    {
        $zonas = Zona::all();

        return view('admin.siaran', [
            'zonas' => $zonas,
        ]);
    }

    /**
     * Kirim siaran darurat baru dari formulir editor.
     */
    public function kirimSiaran(
        Request $request,
        LayananBotTelegram $telegram,
        LayananNotifikasiPush $push
    ): RedirectResponse {
        $validated = $request->validate([
            'jenis_siaran' => ['required', 'in:waspada,siaga_1,titik_api,pencabutan,info_terbuka'],
            'zona_id' => ['nullable', 'exists:zona,id'],
            'kanal' => ['required', 'in:telegram_publik,grup_petugas,web_push'],
            'judul_pesan' => ['required', 'string', 'max:200'],
            'isi_pesan' => ['required', 'string', 'max:1000'],
        ]);

        $zona = $validated['zona_id'] ? Zona::find($validated['zona_id']) : null;
        $isSimulasi = session('mode_demo', false);

        if ($validated['kanal'] === 'web_push') {
            $penerima = $push->kirimSiaranPush(
                $validated['judul_pesan'],
                $validated['isi_pesan'],
                $validated['zona_id'],
                auth()->id(),
                $validated['jenis_siaran']
            );
        } else {
            // Kanal Telegram
            $telegram->kirimSiaranKanal($validated['isi_pesan']);
            $penerima = $validated['kanal'] === 'grup_petugas' ? 24 : 128;

            LogSiaran::create([
                'jenis_siaran' => $validated['jenis_siaran'],
                'zona_id' => $validated['zona_id'],
                'kanal' => $validated['kanal'],
                'judul_pesan' => ($isSimulasi ? '[SIMULASI] ' : '').$validated['judul_pesan'],
                'isi_pesan' => $validated['isi_pesan'],
                'disetujui_oleh' => auth()->id(),
                'jumlah_penerima' => $penerima,
                'adalah_simulasi' => $isSimulasi,
                'dikirim_pada' => Carbon::now(),
            ]);
        }

        return redirect()->route('admin.log-siaran')->with(
            'pesan',
            "Siaran berhasil dikirim ke kanal {$validated['kanal']} ({$penerima} penerima)."
        );
    }

    /**
     * Layar 4: Log Siaran & Jejak Audit (§7.8).
     */
    public function logSiaran(Request $request): View
    {
        $query = LogSiaran::with(['zona', 'penyetuju'])->orderBy('dikirim_pada', 'desc');

        if ($request->filled('kanal')) {
            $query->where('kanal', $request->query('kanal'));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_siaran', $request->query('jenis'));
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.log-siaran', [
            'logs' => $logs,
        ]);
    }

    /**
     * Ekspor CSV Riwayat Log Siaran (FR-NOT-08).
     */
    public function eksporLogSiaranCsv(): StreamedResponse
    {
        $namaBerkas = 'log_siaran_pyroguard_'.Carbon::now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // Header BOM UTF-8 untuk kompatibilitas Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Kolom CSV
            fputcsv($handle, [
                'ID',
                'Jenis Siaran',
                'Kode Zona',
                'Nama Zona',
                'Kanal Pengiriman',
                'Judul Pesan',
                'Isi Pesan',
                'Disetujui Oleh',
                'Jumlah Penerima',
                'Status Simulasi',
                'Waktu Pengiriman',
            ]);

            LogSiaran::with(['zona', 'penyetuju'])
                ->orderBy('dikirim_pada', 'desc')
                ->chunk(100, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->id,
                            $log->jenis_siaran,
                            $log->zona?->kode ?? 'SEMUA',
                            $log->zona?->nama ?? 'Seluruh Kawasan',
                            $log->kanal,
                            $log->judul_pesan,
                            str_replace(["\r", "\n"], ' ', $log->isi_pesan),
                            $log->penyetuju?->nama ?? 'Sistem Otomatis',
                            $log->jumlah_penerima,
                            $log->adalah_simulasi ? 'YA (SIMULASI)' : 'TIDAK (AKTUAL)',
                            $log->dikirim_pada?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$namaBerkas}\"",
        ]);
    }

    /**
     * Layar 5: Manajemen Laporan Warga Lengkap (§7.8).
     */
    public function laporan(Request $request): View
    {
        $query = LaporanAsap::with(['zona', 'pelapor', 'verifikator', 'tiketInduk'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status_tiket', $request->query('status'));
        }

        $laporans = $query->paginate(15)->withQueryString();

        return view('admin.laporan', [
            'laporans' => $laporans,
        ]);
    }

    /**
     * Sakelar Global Mode Demo (FR-DEM-01).
     */
    public function toggleDemo(Request $request): RedirectResponse
    {
        $statusSekarang = session('mode_demo', false);
        session(['mode_demo' => ! $statusSekarang]);

        $pesan = ! $statusSekarang
            ? 'MODE DEMO AKTIF: Seluruh pesan dan simulasi akan berlabel [SIMULASI].'
            : 'MODE DEMO NONAKTIF: Sistem kembali menggunakan data operasional aktual.';

        return back()->with('pesan', $pesan);
    }
}
