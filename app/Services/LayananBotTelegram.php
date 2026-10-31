<?php

namespace App\Services;

use App\Models\LaporanAsap;
use App\Models\Pengguna;
use App\Models\Zona;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layanan Integrasi Bot Telegram & Kanal Peringatan Dini Bromo (§14.1 & §14.3 design.md)
 * Mendukung pengiriman siaran, webhook bot privat, penautan akun, dan tombol aksi inline tiket petugas.
 */
class LayananBotTelegram
{
    protected ?string $botToken;

    protected ?string $botUsername;

    protected ?string $publicChannelId;

    protected ?string $officerGroupId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->botUsername = config('services.telegram.bot_username');
        $this->publicChannelId = config('services.telegram.public_channel_id');
        $this->officerGroupId = config('services.telegram.officer_group_id');
    }

    /**
     * Cek apakah bot Telegram telah dikonfigurasi dengan token nyata di .env.
     */
    public function isTerkonfigurasi(): bool
    {
        return ! empty($this->botToken) && ! str_contains($this->botToken, '[MASUKKAN');
    }

    /**
     * Format templat pesan baku peringatan dini sesuai §14.1 design.md.
     */
    public function formatPesan(string $jenis, Zona $zona, array $params = []): string
    {
        $kodeUrl = strtolower(str_replace('-', '', $zona->kode));
        $isSimulasi = session('mode_demo', false) || ! $this->isTerkonfigurasi();
        $prefix = $isSimulasi ? "[SIMULASI]\n" : '';

        return match ($jenis) {
            'waspada' => $prefix."WASPADA — {$zona->nama} ({$zona->kode})\n"
                .'Hari ke-'.($params['cdd'] ?? 11).' tanpa hujan. FDI '.($params['fdi'] ?? 62).'/100, angin '.($params['angin'] ?? 19)." km/j.\n"
                ."Jangan membakar sampah. Padamkan puntung rokok.\n"
                ."Jangan menyalakan flare atau api terbuka.\n"
                ."Darurat: 112 · Pantau: pyrog.uard/{$kodeUrl}",

            'siaga_1' => $prefix."SIAGA 1 — {$zona->nama} ({$zona->kode})\n"
                .'Hari ke-'.($params['cdd'] ?? 19).' tanpa hujan. FDI '.($params['fdi'] ?? 86).'/100. Angin '.($params['angin'] ?? 28)." km/j.\n"
                ."Dilarang keras membuat api terbuka sampai status dicabut.\n"
                ."Siapkan masker dan ketahui jalur keluar.\n"
                ."Darurat: 112 · Info: pyrog.uard/{$kodeUrl}",

            'titik_api' => $prefix.'Asap terverifikasi '.($params['jarak'] ?? '1,8').' km arah '.($params['arah'] ?? 'barat daya').".\n"
                .'Regu menuju lokasi. Angin ke '.($params['arah_angin'] ?? 'barat laut').".\n"
                ."Jauhi area {$zona->nama}. Ikuti petugas.",

            'pencabutan' => $prefix."SIAGA 1 DICABUT — {$zona->nama}\n"
                .'FDI turun ke '.($params['fdi'] ?? 41)."/100 setelah hujan.\n"
                ."Tetap jaga kebersihan lahan dan jangan membakar.\n"
                .'Darurat: 112 · Pantau: pyrog.uard',

            default => $prefix."PEMBERITAHUAN — {$zona->nama} ({$zona->kode})\n".($params['catatan'] ?? 'Pantau informasi berkala melalui portal PyroGuard.'),
        };
    }

    /**
     * Kirim pesan ke kanal publik Telegram atau grup petugas.
     *
     * @return array{success: bool, simulasi: bool, data: mixed}
     */
    public function kirimSiaranKanal(string $pesan, ?string $channelId = null): array
    {
        $target = $channelId ?: $this->publicChannelId;
        $isSimulasi = session('mode_demo', false) || ! $this->isTerkonfigurasi();

        if ($isSimulasi || empty($target)) {
            Log::info('[Telegram Simulation] Kirim ke Kanal: '.$pesan);

            return [
                'success' => true,
                'simulasi' => true,
                'data' => 'Pesan disimulasikan karena Mode Demo aktif atau kredensial Telegram masih draf.',
            ];
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $target,
                'text' => $pesan,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ]);

            return [
                'success' => $response->successful(),
                'simulasi' => false,
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Telegram send error: '.$e->getMessage());

            return [
                'success' => false,
                'simulasi' => false,
                'data' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim pesan langsung ke chat ID pengguna dengan opsi keyboard inline.
     *
     * @return array{success: bool, simulasi: bool, data: mixed}
     */
    public function kirimPesan(string $chatId, string $pesan, ?array $replyMarkup = null): array
    {
        $isSimulasi = session('mode_demo', false) || ! $this->isTerkonfigurasi();

        if ($isSimulasi) {
            Log::info("[Telegram Simulation] Kirim ke {$chatId}: ".$pesan);

            return ['success' => true, 'simulasi' => true, 'data' => 'Disimulasikan.'];
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $pesan,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload);

            return ['success' => $response->successful(), 'simulasi' => false, 'data' => $response->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'simulasi' => false, 'data' => $e->getMessage()];
        }
    }

    /**
     * Tangani webhook dari server Telegram.
     */
    public function tanganiWebhook(array $update): array
    {
        // 1. Tangani Callback Query (Tombol Inline Petugas Lapangan)
        if (isset($update['callback_query'])) {
            return $this->prosesCallbackQuery($update['callback_query']);
        }

        // 2. Tangani Pesan Teks
        if (isset($update['message'])) {
            return $this->prosesPesanTeks($update['message']);
        }

        return ['status' => 'ignored'];
    }

    /**
     * Proses perintah pesan teks (e.g. /start, /status, /zona).
     */
    protected function prosesPesanTeks(array $message): array
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim($message['text'] ?? '');

        if (empty($chatId) || empty($text)) {
            return ['status' => 'empty_message'];
        }

        // Perintah /start [KODE]
        if (str_starts_with($text, '/start')) {
            $parts = explode(' ', $text);
            $kode = $parts[1] ?? null;

            if ($kode) {
                $pengguna = Pengguna::where('kode_tautan_telegram', $kode)->first();

                if ($pengguna) {
                    $pengguna->update(['telegram_chat_id' => $chatId]);
                    $balasan = "✅ <b>Akun Terhubung!</b>\nHalo <b>{$pengguna->nama}</b>, akun PyroGuard Anda berhasil ditautkan ke Telegram.\nAnda akan menerima siaga dini Bromo secara langsung.";
                    $this->kirimPesan($chatId, $balasan);

                    return ['status' => 'connected', 'user_id' => $pengguna->id];
                }
            }

            $balasan = "🌲 <b>Selamat datang di Bot PyroGuard Bromo</b>\n\n"
                ."Sistem Peringatan Dini Kebakaran Lahan Kawasan Bromo Tengger Semeru.\n\n"
                ."Perintah tersedia:\n"
                ."• /status — Ringkasan kondisi kawasan saat ini\n"
                ."• /zona — Daftar status 6 zona pemantauan\n"
                ."• /berhenti — Berhenti menerima notifikasi\n\n"
                .'Buka portal lengkap: https://pyrog.uard';

            $this->kirimPesan($chatId, $balasan);

            return ['status' => 'welcome_sent'];
        }

        // Perintah /status
        if ($text === '/status') {
            $zonas = Zona::with('risikoTerbaru')->get();
            $zonaTertinggi = $zonas->sortByDesc(fn ($z) => $z->risikoTerbaru?->indeks_bahaya_api ?? 0)->first();
            $r = $zonaTertinggi?->risikoTerbaru;

            $statusText = "📊 <b>STATUS KAWASAN BROMO HARI INI</b>\n"
                ."Zona Tertinggi: <b>{$zonaTertinggi?->nama}</b>\n"
                ."Status: <b>{$r?->kategori_risiko}</b> (FDI {$r?->indeks_bahaya_api}/100)\n"
                ."Hari Tanpa Hujan: <b>{$r?->hari_tanpa_hujan} hari</b>\n"
                ."Angin: {$r?->kecepatan_angin_kmj} km/j dari {$r?->arah_angin}\n\n"
                ."⚠️ <i>Dilarang membuat api terbuka di kawasan savana.</i>\n"
                .'Darurat: 112 · Info: https://pyrog.uard';

            $this->kirimPesan($chatId, $statusText);

            return ['status' => 'status_sent'];
        }

        // Perintah /zona
        if ($text === '/zona') {
            $zonas = Zona::with('risikoTerbaru')->get();
            $msg = "🗺️ <b>6 ZONA PEMANTAUAN BROMO</b>\n\n";

            foreach ($zonas as $z) {
                $kat = $z->risikoTerbaru?->kategori_risiko ?? 'AMAN';
                $cdd = $z->risikoTerbaru?->hari_tanpa_hujan ?? 0;
                $icon = match ($kat) {
                    'BAHAYA', 'SIAGA_1' => '🔴',
                    'WASPADA' => '🟡',
                    default => '🟢',
                };
                $msg .= "{$icon} <b>{$z->kode}</b> · {$z->nama}\n   Status: {$kat} ({$cdd} hari kering)\n\n";
            }

            $this->kirimPesan($chatId, $msg);

            return ['status' => 'zona_sent'];
        }

        // Perintah /berhenti
        if ($text === '/berhenti') {
            Pengguna::where('telegram_chat_id', $chatId)->update(['telegram_chat_id' => null]);
            $this->kirimPesan($chatId, '🔕 Notifikasi Telegram PyroGuard telah dinonaktifkan.');

            return ['status' => 'unsubscribed'];
        }

        return ['status' => 'unknown_command'];
    }

    /**
     * Proses tombol aksi verifikasi inline dari Telegram petugas (§7.7).
     */
    protected function prosesCallbackQuery(array $callbackQuery): array
    {
        $id = $callbackQuery['id'] ?? '';
        $data = $callbackQuery['data'] ?? '';
        $chatId = (string) ($callbackQuery['message']['chat']['id'] ?? '');

        // Format callback: tiket:{aksi}:{nomor_tiket}
        $parts = explode(':', $data);
        if (count($parts) >= 3 && $parts[0] === 'tiket') {
            $aksi = $parts[1];
            $nomorTiket = $parts[2];

            $laporan = LaporanAsap::where('nomor_tiket', $nomorTiket)->first();

            if ($laporan) {
                if ($aksi === 'menuju') {
                    $laporan->update(['status_tiket' => 'menuju_lokasi']);
                    $this->jawabCallback($id, "Petugas tercatat menuju lokasi {$nomorTiket}.");
                } elseif ($aksi === 'api_nyata') {
                    $laporan->update(['status_tiket' => 'terverifikasi', 'hasil_verifikasi' => 'api_nyata']);
                    $this->jawabCallback($id, "Tiket {$nomorTiket} terverifikasi API NYATA!");
                } elseif ($aksi === 'bukan_api') {
                    $laporan->update(['status_tiket' => 'selesai', 'hasil_verifikasi' => 'bukan_api']);
                    $this->jawabCallback($id, "Tiket {$nomorTiket} ditutup (Bukan Api).");
                }
            }
        }

        return ['status' => 'callback_processed'];
    }

    /**
     * Jawab callback query Telegram agar spinner tombol berhenti.
     */
    protected function jawabCallback(string $callbackQueryId, string $teks): void
    {
        if ($this->isTerkonfigurasi()) {
            try {
                Http::post("https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery", [
                    'callback_query_id' => $callbackQueryId,
                    'text' => $teks,
                    'show_alert' => true,
                ]);
            } catch (\Throwable $e) {
                // Ignore silent network failure
            }
        }
    }
}
