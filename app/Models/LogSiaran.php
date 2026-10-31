<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Jejak Log Siaran Peringatan Dini Multi-Kanal
 */
class LogSiaran extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'log_siaran';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'jenis_siaran',
        'zona_id',
        'kanal',
        'judul_pesan',
        'isi_pesan',
        'disetujui_oleh',
        'jumlah_penerima',
        'adalah_simulasi',
        'dikirim_pada',
    ];

    /**
     * Pemetaan tipe data atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_penerima' => 'integer',
            'adalah_simulasi' => 'boolean',
            'dikirim_pada' => 'datetime',
        ];
    }

    /**
     * Relasi ke zona siaran (jika spesifik zona).
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    /**
     * Relasi ke admin penyetuju siaran.
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'disetujui_oleh');
    }
}
