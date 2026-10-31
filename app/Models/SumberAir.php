<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Sumber Air, Embung, Pos Jaga & Titik Kumpul Evakuasi
 */
class SumberAir extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'sumber_air';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'zona_id',
        'nama',
        'jenis',
        'latitude',
        'longitude',
        'debit_air',
        'status_siaga',
        'jalur_evakuasi_geojson',
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
            'jalur_evakuasi_geojson' => 'array',
        ];
    }

    /**
     * Relasi ke zona lokasi sumber air/pos.
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }
}
