<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Layanan Kompresi dan Optimasi Foto Laporan Lapangan (§12 design.md)
 * Memastikan resolusi foto maksimal 1200 px pada sisi terpanjang dengan kompresi berkualitas tinggi.
 */
class LayananKompresiFoto
{
    /**
     * Kompres dan simpan foto ke disk publik.
     */
    public function kompresDanSimpan(mixed $file, string $folder = 'laporan', int $maxDimensi = 1200, int $kualitas = 82): ?string
    {
        if (! $file instanceof UploadedFile) {
            if (is_string($file) && ! empty($file)) {
                return $file;
            }

            return null;
        }

        $namaFile = Str::random(32).'.jpg';
        $pathRelatif = $folder.'/'.$namaFile;

        // Jika ekstensi GD tersedia, lakukan kompresi cerdas
        if (extension_loaded('gd')) {
            $konten = file_get_contents($file->getRealPath());
            $gambarSumber = @imagecreatefromstring($konten);

            if ($gambarSumber !== false) {
                $lebarAsli = imagesx($gambarSumber);
                $tinggiAsli = imagesy($gambarSumber);

                // Hitung dimensi baru proporsional maksimal 1200 px
                if ($lebarAsli > $maxDimensi || $tinggiAsli > $maxDimensi) {
                    if ($lebarAsli >= $tinggiAsli) {
                        $lebarBaru = $maxDimensi;
                        $tinggiBaru = (int) round(($tinggiAsli / $lebarAsli) * $maxDimensi);
                    } else {
                        $tinggiBaru = $maxDimensi;
                        $lebarBaru = (int) round(($lebarAsli / $tinggiAsli) * $maxDimensi);
                    }
                } else {
                    $lebarBaru = $lebarAsli;
                    $tinggiBaru = $tinggiAsli;
                }

                // Buat canvas gambar baru
                $gambarTujuan = imagecreatetruecolor($lebarBaru, $tinggiBaru);

                // Pertahankan kejernihan warna
                imagealphablending($gambarTujuan, false);
                imagesavealpha($gambarTujuan, true);

                // Resample gambar
                imagecopyresampled(
                    $gambarTujuan,
                    $gambarSumber,
                    0,
                    0,
                    0,
                    0,
                    $lebarBaru,
                    $tinggiBaru,
                    $lebarAsli,
                    $tinggiAsli
                );

                // Simpan hasil kompresi ke buffer
                ob_start();
                imagejpeg($gambarTujuan, null, $kualitas);
                $outputData = ob_get_clean();

                // Bersihkan memori GD
                imagedestroy($gambarSumber);
                imagedestroy($gambarTujuan);

                // Simpan ke storage disk public
                Storage::disk('public')->put($pathRelatif, $outputData);

                return $pathRelatif;
            }
        }

        // Fallback jika GD tidak dapat memproses berkas (misal file non-standard)
        return $file->store($folder, 'public');
    }
}
