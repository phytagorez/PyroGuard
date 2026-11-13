<?php

namespace App\Services;

use App\Models\LaporanAsap;
use App\Models\SumberAir;
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
                ."Darurat: 112 · Pantau: {$this->urlPortal()}",

            'siaga_1' => $prefix."SIAGA 1 — {$zona->nama} ({$zona->kode})\n"
                .'Hari ke-'.($params['cdd'] ?? 19).' tanpa hujan. FDI '.($params['fdi'] ?? 86).'/100. Angin '.($params['angin'] ?? 28)." km/j.\n"
                ."Dilarang keras membuat api terbuka sampai status dicabut.\n"
                ."Siapkan masker dan ketahui jalur keluar.\n"
                ."Darurat: 112 · Info: {$this->urlPortal()}",

            'titik_api' => $prefix.'Asap terverifikasi '.($params['jarak'] ?? '1,8').' km arah '.($params['arah'] ?? 'barat daya').".\n"
                .'Regu menuju lokasi. Angin ke '.($params['arah_angin'] ?? 'barat laut').".\n"
                ."Jauhi area {$zona->nama}. Ikuti petugas.",

            'pencabutan' => $prefix."SIAGA 1 DICABUT — {$zona->nama}\n"
                .'FDI turun ke '.($params['fdi'] ?? 41)."/100 setelah hujan.\n"
                ."Tetap jaga kebersihan lahan dan jangan membakar.\n"
                .'Darurat: 112 · Pantau: '.$this->urlPortal(),

            default => $prefix."PEMBERITAHUAN — {$zona->nama} ({$zona->kode})\n".($params['catatan'] ?? 'Pantau informasi berkala melalui portal PyroGuard.'),
        };
    }

    /**
     * Peringatan ke grup petugas saat warga mengirim laporan asap baru.
     * Berisi desa, kelas risiko, koordinat, tautan peta, dan tombol aksi petugas.
     *
     * @return array{success: bool, simulasi: bool, data: mixed}
     */
    public function kirimPeringatanLaporanBaru(LaporanAsap $laporan): array
    {
        $zona = $laporan->zona;
        $e = fn ($teks) => htmlspecialchars((string) $teks, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $jenis = [
            'asap_tipis' => 'Asap tipis',
            'asap_tebal' => 'Asap tebal',
            'api_terlihat' => 'Api terlihat',
            'bekas_terbakar' => 'Bekas terbakar',
        ][$laporan->jenis_temuan] ?? $laporan->jenis_temuan;

        $kelas = $zona?->kelas_risiko;
        $ikon = match ($kelas) {
            'Tinggi' => '🔴',
            'Sedang' => '🟡',
            'Rendah' => '🟢',
            default => '⚪',
        };
        $lokasi = $zona
            ? $e($zona->data_analisis['Label'] ?? $zona->nama)
            : 'Lokasi belum terpetakan';
        $peta = 'https://www.google.com/maps?q='.$laporan->latitude.','.$laporan->longitude;

        $pesan = "🚨 <b>LAPORAN ASAP BARU {$e($laporan->nomor_tiket)}</b>\n"
            ."Temuan: <b>{$e($jenis)}</b>\n"
            ."Desa: <b>{$lokasi}</b>".($kelas ? " ({$ikon} risiko ".strtolower($kelas).')' : '')."\n"
            ."Koordinat: {$laporan->latitude}, {$laporan->longitude}\n"
            .'Peta: '.$peta."\n"
            .($laporan->catatan ? "Catatan: {$e($laporan->catatan)}\n" : '')
            .$this->barisLogistikTerdekat($laporan, $e)
            ."\nMohon verifikasi lapangan secepatnya.";

        $tombol = ['inline_keyboard' => [[
            ['text' => '🚒 Menuju lokasi', 'callback_data' => "tiket:menuju:{$laporan->nomor_tiket}"],
            ['text' => '🔥 Api nyata', 'callback_data' => "tiket:api_nyata:{$laporan->nomor_tiket}"],
            ['text' => '✖ Bukan api', 'callback_data' => "tiket:bukan_api:{$laporan->nomor_tiket}"],
        ]]];

        if (empty($this->officerGroupId)) {
            Log::info('[Telegram] TELEGRAM_OFFICER_GROUP_ID kosong, peringatan laporan hanya dicatat: '.$pesan);

            return ['success' => false, 'simulasi' => true, 'data' => 'Grup petugas belum diatur di .env'];
        }

        return $this->kirimPesan((string) $this->officerGroupId, $pesan, $tombol);
    }

    /**
     * Alamat portal untuk tautan di pesan (dari APP_URL, bukan teks tetap).
     */
    protected function urlPortal(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Baris "air terdekat" dan "pos terdekat" untuk pesan petugas.
     * Titik yang masih data contoh ditandai agar tidak dianggap resmi.
     */
    protected function barisLogistikTerdekat(LaporanAsap $laporan, callable $e): string
    {
        $baris = '';
        $lat = (float) $laporan->latitude;
        $lng = (float) $laporan->longitude;

        foreach ([['Air terdekat', ['embung', 'sumber_air']], ['Pos terdekat', ['pos_jaga']]] as [$label, $jenis]) {
            $hasil = SumberAir::terdekat($lat, $lng, $jenis);
            if (! $hasil) {
                continue;
            }

            $tanda = $hasil['titik']->adalah_contoh ? ' <i>(data contoh)</i>' : '';
            $baris .= "{$label}: {$e($hasil['titik']->nama)} ± ".number_format($hasil['jarak_km'], 1, ',', '.')." km{$tanda}\n";
        }

        return $baris;
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
                ."• /status — Ringkasan risiko kawasan saat ini\n"
                ."• /zona — Daftar risiko 30 desa di kawasan Bromo\n"
                ."• /berhenti — Berhenti menerima notifikasi\n\n"
                .'Buka portal lengkap: '.$this->urlPortal();

            $this->kirimPesan($chatId, $balasan);

            return ['status' => 'welcome_sent'];
        }

        // Perintah /status
        if ($text === '/status') {
            $desas = Zona::desa()->get();

            if ($desas->isNotEmpty()) {
                $hitung = $desas->countBy(fn ($z) => $z->kelas_risiko ?? 'Belum ada');
                $teratas = $desas->sortByDesc(fn ($z) => $z->data_analisis['Skor_Gabungan'] ?? 0)->take(3);

                $statusText = "📊 <b>STATUS RISIKO KEBAKARAN KAWASAN BROMO</b>\n"
                    ."🔴 Tinggi: <b>".($hitung['Tinggi'] ?? 0)." desa</b> · 🟡 Sedang: <b>".($hitung['Sedang'] ?? 0)." desa</b> · 🟢 Rendah: <b>".($hitung['Rendah'] ?? 0)." desa</b>\n\n"
                    ."Desa yang perlu paling diwaspadai:\n";

                foreach ($teratas as $z) {
                    $label = htmlspecialchars($z->data_analisis['Label'] ?? $z->nama, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $statusText .= "• {$label} — risiko ".strtolower($z->kelas_risiko ?? 'belum diketahui')."\n";
                }

                $statusText .= "\n⚠️ <i>Dilarang membuat api unggun, membakar sampah, atau membuang puntung rokok sembarangan.</i>\n"
                    .'Darurat: 112 · Peta lengkap: '.$this->urlPortal();
            } else {
                $statusText = "📊 Data risiko desa belum tersedia.\nDarurat: 112 · Info: ".$this->urlPortal();
            }

            $this->kirimPesan($chatId, $statusText);

            return ['status' => 'status_sent'];
        }

        // Perintah /zona (daftar desa menurut kelas risiko)
        if ($text === '/zona') {
            $desas = Zona::desa()->get()->sortByDesc(fn ($z) => $z->data_analisis['Skor_Gabungan'] ?? 0);
            $msg = "🗺️ <b>RISIKO KEBAKARAN PER DESA</b>\n\n";

            foreach ($desas as $z) {
                $icon = match ($z->kelas_risiko) {
                    'Tinggi' => '🔴',
                    'Sedang' => '🟡',
                    'Rendah' => '🟢',
                    default => '⚪',
                };
                $label = htmlspecialchars($z->data_analisis['Label'] ?? $z->nama, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $msg .= "{$icon} {$label}\n";
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
