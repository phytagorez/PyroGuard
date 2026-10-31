<?php

namespace App\Services;

use App\Models\LogSiaran;
use App\Models\PrakiraanRisiko;
use App\Models\StatusSiaga;
use App\Models\Zona;
use Carbon\Carbon;

/**
 * Layanan Otoritas Persetujuan & Pencabutan Status Siaga 1 oleh Admin BPBD
 * Mengimplementasikan alur dua langkah & jejak audit sesuai §6.10 & §7.8 design.md.
 */
class LayananSiaga
{
    /**
     * Proses persetujuan atau pencabutan status siaga satu zona.
     *
     * @param  string  $aksi  'setujui' | 'cabut'
     */
    public function prosesStatusSiaga(Zona $zona, string $aksi, ?string $catatan = null, ?int $adminId = null): StatusSiaga
    {
        $now = Carbon::now();

        if ($aksi === 'setujui') {
            $status = StatusSiaga::create([
                'zona_id' => $zona->id,
                'status' => 'SIAGA_1',
                'disetujui_oleh' => $adminId,
                'catatan_persetujuan' => $catatan ?? "Siaga 1 disetujui otoritas BPBD untuk kawasan {$zona->nama}.",
                'disetujui_pada' => $now,
                'sumber' => 'aktual',
            ]);

            // Sinkronkan ke prakiraan_risiko zona
            PrakiraanRisiko::where('zona_id', $zona->id)
                ->where('sumber', 'aktual')
                ->update(['status_siaga' => 'SIAGA_1']);

            // Catat log siaran resmi (§14.1 design.md)
            $risiko = $zona->risikoTerbaru;
            $cdd = $risiko?->hari_tanpa_hujan ?? 19;
            $fdi = $risiko?->indeks_bahaya_api ?? 86;
            $angin = $risiko?->kecepatan_angin_kmj ?? 28;

            $pesanSiaga = "SIAGA 1 — {$zona->nama} ({$zona->kode})\n"
                ."Hari ke-{$cdd} tanpa hujan. FDI {$fdi}/100. Angin {$angin} km/j.\n"
                ."Dilarang keras membuat api terbuka sampai status dicabut.\n"
                ."Siapkan masker dan ketahui jalur keluar.\n"
                .'Darurat: 112 · Info: pyrog.uard/'.strtolower(str_replace('-', '', $zona->kode));

            LogSiaran::create([
                'jenis_siaran' => 'siaga_1',
                'zona_id' => $zona->id,
                'kanal' => 'telegram_publik',
                'judul_pesan' => "SIAGA 1 — {$zona->nama} ({$zona->kode})",
                'isi_pesan' => $pesanSiaga,
                'disetujui_oleh' => $adminId,
                'jumlah_penerima' => 128,
                'adalah_simulasi' => false,
                'dikirim_pada' => $now,
            ]);

            return $status;
        }

        // Aksi 'cabut' status siaga
        $status = StatusSiaga::create([
            'zona_id' => $zona->id,
            'status' => 'DICABUT',
            'dicabut_oleh' => $adminId,
            'catatan_persetujuan' => $catatan ?? "Status Siaga 1 resmi dicabut untuk kawasan {$zona->nama}.",
            'dicabut_pada' => $now,
            'sumber' => 'aktual',
        ]);

        PrakiraanRisiko::where('zona_id', $zona->id)
            ->where('sumber', 'aktual')
            ->update(['status_siaga' => 'DICABUT']);

        $pesanCabut = "SIAGA 1 DICABUT — {$zona->nama}\n"
            ."Indeks bahaya kebakaran lahan telah menurun.\n"
            ."Tetap jaga kebersihan lahan dan jangan membakar sampah.\n"
            .'Darurat: 112 · Pantau: pyrog.uard';

        LogSiaran::create([
            'jenis_siaran' => 'pencabutan',
            'zona_id' => $zona->id,
            'kanal' => 'telegram_publik',
            'judul_pesan' => "SIAGA 1 DICABUT — {$zona->nama}",
            'isi_pesan' => $pesanCabut,
            'disetujui_oleh' => $adminId,
            'jumlah_penerima' => 128,
            'adalah_simulasi' => false,
            'dikirim_pada' => $now,
        ]);

        return $status;
    }
}
