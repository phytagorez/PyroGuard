<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Jejak Audit Status Siaga Zona
 */
class StatusSiaga extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'status_siaga';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'zona_id',
        'status',
        'disetujui_oleh',
        'catatan_persetujuan',
        'disetujui_pada',
        'dicabut_oleh',
        'dicabut_pada',
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
            'disetujui_pada' => 'datetime',
            'dicabut_pada' => 'datetime',
        ];
    }

    /**
     * Relasi ke zona terkait.
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    /**
     * Relasi ke admin/otoritas yang menyetujui siaga.
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'disetujui_oleh');
    }

    /**
     * Relasi ke admin/otoritas yang mencabut siaga.
     */
    public function pencabut(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dicabut_oleh');
    }
}
