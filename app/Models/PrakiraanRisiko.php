<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Prakiraan Risiko Kebakaran Hutan & Lahan
 */
class PrakiraanRisiko extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'prakiraan_risiko';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'zona_id',
        'hari_tanpa_hujan',
        'proyeksi_cdd_24j',
        'proyeksi_cdd_48j',
        'proyeksi_cdd_72j',
        'indeks_bahaya_api',
        'kategori_risiko',
        'poin_cdd',
        'poin_kelembapan',
        'poin_angin',
        'jam_rentan',
        'kecepatan_angin_kmj',
        'arah_angin',
        'status_siaga',
        'status_data',
        'sumber',
        'dihitung_pada',
    ];

    /**
     * Pemetaan tipe data atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hari_tanpa_hujan' => 'integer',
            'proyeksi_cdd_24j' => 'integer',
            'proyeksi_cdd_48j' => 'integer',
            'proyeksi_cdd_72j' => 'integer',
            'indeks_bahaya_api' => 'integer',
            'poin_cdd' => 'float',
            'poin_kelembapan' => 'float',
            'poin_angin' => 'float',
            'kecepatan_angin_kmj' => 'float',
            'dihitung_pada' => 'datetime',
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
