<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Laporan Asap / Titik Api dari Warga & Tiket Petugas
 */
class LaporanAsap extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'laporan_asap';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_tiket',
        'zona_id',
        'pelapor_id',
        'ip_pelapor',
        'latitude',
        'longitude',
        'akurasi_gps',
        'jenis_temuan',
        'foto_1',
        'foto_2',
        'catatan',
        'status_tiket',
        'hasil_verifikasi',
        'tiket_induk_id',
        'foto_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'sumber',
    ];

    /**
     * Pemetaan tipe data atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'akurasi_gps' => 'float',
            'diverifikasi_pada' => 'datetime',
        ];
    }

    /**
     * Relasi ke zona tempat laporan dibuat.
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    /**
     * Relasi ke pengguna yang melapor (jika login).
     */
    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pelapor_id');
    }

    /**
     * Relasi ke petugas yang memverifikasi laporan.
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diverifikasi_oleh');
    }

    /**
     * Relasi ke tiket induk jika laporan ini ditandai sebagai duplikat/ganda.
     */
    public function tiketInduk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'tiket_induk_id');
    }

    /**
     * Relasi ke laporan turunan yang digabung ke tiket ini.
     */
    public function laporanTergabung(): HasMany
    {
        return $this->hasMany(self::class, 'tiket_induk_id');
    }
}
