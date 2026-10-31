<?php

namespace App\Console\Commands;

use App\Models\Zona;
use App\Services\LayananCuacaOpenMeteo;
use Illuminate\Console\Command;

class SinkronCuacaCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     *
     * @var string
     */
    protected $signature = 'pyroguard:sinkron-cuaca {--zona= : Kode zona spesifik (misal: BRM-04)}';

    /**
     * Deskripsi perintah konsol.
     *
     * @var string
     */
    protected $description = 'Sinkronkan data cuaca Open-Meteo dan kalkulasi risiko kebakaran kawasan Bromo';

    /**
     * Jalankan perintah konsol.
     */
    public function handle(LayananCuacaOpenMeteo $layananCuaca): int
    {
        $kodeZona = $this->option('zona');

        $this->info('Memulai sinkronisasi cuaca Open-Meteo Kawasan Bromo...');

        if ($kodeZona) {
            $zona = Zona::where('kode', strtoupper($kodeZona))->first();

            if (! $zona) {
                $this->error("Zona dengan kode '{$kodeZona}' tidak ditemukan.");

                return self::FAILURE;
            }

            $this->line("Menghubungi Open-Meteo untuk {$zona->nama} ({$zona->kode})...");
            $hasil = $layananCuaca->sinkronCuacaZona($zona);

            if ($hasil['sukses']) {
                $this->info("✓ {$hasil['pesan']}");
            } else {
                $this->warn("⚠ {$hasil['pesan']}");
            }

            return self::SUCCESS;
        }

        $zonas = Zona::orderBy('kode')->get();
        $this->info("Ditemukan {$zonas->count()} zona pemantauan.");

        $bar = $this->output->createProgressBar($zonas->count());
        $bar->start();

        $ringkasan = [];
        foreach ($zonas as $zona) {
            $hasil = $layananCuaca->sinkronCuacaZona($zona);
            $ringkasan[] = [
                'Kode' => $zona->kode,
                'Nama' => $zona->nama,
                'Status' => $hasil['sukses'] ? 'Sukses' : 'Gagal (Data Cache)',
                'Kesegaran' => $hasil['status_kesegaran'],
                'Keterangan' => $hasil['pesan'],
            ];
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Kode', 'Nama', 'Status', 'Kesegaran', 'Keterangan'], $ringkasan);
        $this->info('Sinkronisasi cuaca dan kalkulasi risiko selesai.');

        return self::SUCCESS;
    }
}
