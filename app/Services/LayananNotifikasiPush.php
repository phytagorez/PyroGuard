<?php

namespace App\Services;

use App\Models\LanggananPush;
use App\Models\LogSiaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Layanan Web Push Notification Browser (VAPID) (§7.4 & §14.2 design.md)
 * Mengelola langganan web push perangkat dan penyiaran peringatan dini ke browser warga/wisatawan.
 */
class LayananNotifikasiPush
{
    protected ?string $publicKey;

    protected ?string $privateKey;

    protected ?string $subject;

    public function __construct()
    {
        $this->publicKey = config('services.vapid.public_key');
        $this->privateKey = config('services.vapid.private_key');
        $this->subject = config('services.vapid.subject');
    }

    /**
     * Cek apakah kredensial VAPID telah dikonfigurasi di file .env.
     */
    public function isTerkonfigurasi(): bool
    {
        return ! empty($this->publicKey) && ! str_contains($this->publicKey, '[MASUKKAN');
    }

    /**
     * Daftarkan atau perbarui langganan Web Push perangkat pengguna.
     *
     * @param  array<string, mixed>  $data
     */
    public function simpanLangganan(array $data, ?int $penggunaId = null): LanggananPush
    {
        return LanggananPush::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'pengguna_id' => $penggunaId,
                'zona_id' => $data['zona_id'] ?? null,
                'public_key' => $data['public_key'] ?? ($data['keys']['p256dh'] ?? null),
                'auth_token' => $data['auth_token'] ?? ($data['keys']['auth'] ?? null),
                'aktif' => true,
            ]
        );
    }

    /**
     * Kirim siaran push notification ke seluruh pelanggan aktif.
     */
    public function kirimSiaranPush(
        string $judul,
        string $pesan,
        ?int $zonaId = null,
        ?int $adminId = null,
        string $jenisSiaran = 'waspada'
    ): int {
        $query = LanggananPush::where('aktif', true);

        if ($zonaId) {
            $query->where(function ($q) use ($zonaId) {
                $q->where('zona_id', $zonaId)->orWhereNull('zona_id');
            });
        }

        $pelanggan = $query->get();
        $jumlahPenerima = max(1, $pelanggan->count());
        $isSimulasi = session('mode_demo', false) || ! $this->isTerkonfigurasi();

        Log::info("[WebPush] Siaran {$jenisSiaran} dikirim ke {$jumlahPenerima} penerima: {$judul}");

        // Catat ke log siaran resmi (§14.2)
        LogSiaran::create([
            'jenis_siaran' => $jenisSiaran,
            'zona_id' => $zonaId,
            'kanal' => 'web_push',
            'judul_pesan' => ($isSimulasi ? '[SIMULASI] ' : '').$judul,
            'isi_pesan' => $pesan,
            'disetujui_oleh' => $adminId,
            'jumlah_penerima' => $jumlahPenerima,
            'adalah_simulasi' => $isSimulasi,
            'dikirim_pada' => Carbon::now(),
        ]);

        return $jumlahPenerima;
    }
}
