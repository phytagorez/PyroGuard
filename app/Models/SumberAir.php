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
        'sumber_data',
        'tanggal_verifikasi',
        'kontak',
    ];

    /**
     * Atribut tambahan yang ikut dalam JSON.
     *
     * @var array<int, string>
     */
    protected $appends = ['adalah_contoh'];

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
            'tanggal_verifikasi' => 'date:Y-m-d',
        ];
    }

    /**
     * Titik tanpa sumber data dianggap data contoh (belum diverifikasi).
     */
    public function getAdalahContohAttribute(): bool
    {
        return blank($this->sumber_data) || strtolower((string) $this->sumber_data) === 'contoh';
    }

    /**
     * Jarak garis lurus (km) dari koordinat ke titik ini (rumus haversine).
     */
    public function jarakKm(float $lat, float $lng): float
    {
        $r = 6371.0;
        $dLat = deg2rad($this->latitude - $lat);
        $dLng = deg2rad($this->longitude - $lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad($this->latitude)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Titik terdekat dari koordinat untuk jenis tertentu.
     *
     * @param  array<int, string>  $jenis
     * @return array{titik: self, jarak_km: float}|null
     */
    public static function terdekat(float $lat, float $lng, array $jenis): ?array
    {
        $hasil = static::whereIn('jenis', $jenis)->get()
            ->map(fn (self $t) => ['titik' => $t, 'jarak_km' => $t->jarakKm($lat, $lng)])
            ->sortBy('jarak_km')
            ->first();

        return $hasil ?: null;
    }

    /**
     * Relasi ke zona lokasi sumber air/pos.
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }
}
