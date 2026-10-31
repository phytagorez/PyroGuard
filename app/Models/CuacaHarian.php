<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Cuaca Harian per Zona
 */
class CuacaHarian extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'cuaca_harian';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'zona_id',
        'tanggal',
        'curah_hujan_mm',
        'kelembapan_min',
        'kecepatan_angin_maks',
        'arah_angin',
        'suhu_maks',
        'status_data',
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
            'tanggal' => 'date',
            'curah_hujan_mm' => 'float',
            'kelembapan_min' => 'float',
            'kecepatan_angin_maks' => 'float',
            'suhu_maks' => 'float',
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
