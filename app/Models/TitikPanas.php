<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Titik Panas Satelit (NASA FIRMS MODIS / VIIRS)
 */
class TitikPanas extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'titik_panas';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'zona_id',
        'latitude',
        'longitude',
        'kecerahan',
        'sumber_satelit',
        'terdeteksi_pada',
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
            'kecerahan' => 'float',
            'terdeteksi_pada' => 'datetime',
        ];
    }

    /**
     * Relasi ke zona pemantauan.
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }
}
