<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model Zona Pemantauan Bromo (BRM-01 s/d BRM-06)
 */
class Zona extends Model
{
    use HasFactory;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'zona';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    
protected $fillable = [
    'kode',
    'nama',
    'deskripsi_lokasi',
    'jenis_lahan',
    'akses_wisata',
    'poligon_geojson',
    'titik_tengah_lat',
    'titik_tengah_lng',
    'data_analisis',
];


    /**
     * Pemetaan tipe data atribut.
     *
     * @return array<string, string>
     */
protected function casts(): array
{
    return [
        'poligon_geojson' => 'array',
        'data_analisis' => 'array',
        'titik_tengah_lat' => 'decimal:7',
        'titik_tengah_lng' => 'decimal:7',
    ];
}


    /**
     * Relasi riwayat cuaca harian zona.
     */
    public function cuacaHarian(): HasMany
    {
        return $this->hasMany(CuacaHarian::class, 'zona_id');
    }

    /**
     * Relasi kalkulasi prakiraan risiko zona.
     */
    public function prakiraanRisiko(): HasMany
    {
        return $this->hasMany(PrakiraanRisiko::class, 'zona_id');
    }

    /**
     * Relasi prakiraan risiko paling baru (aktual).
     */
    public function risikoTerbaru(): HasOne
    {
        return $this->hasOne(PrakiraanRisiko::class, 'zona_id')
            ->ofMany([
                'id' => 'max',
            ], function ($query) {
                $query->where('sumber', 'aktual');
            });
    }

    /**
     * Relasi riwayat status siaga zona.
     */
    public function statusSiaga(): HasMany
    {
        return $this->hasMany(StatusSiaga::class, 'zona_id');
    }

    /**
     * Relasi status siaga aktif paling baru.
     */
    public function siagaAktif(): HasOne
    {
        return $this->hasOne(StatusSiaga::class, 'zona_id')
            ->ofMany([
                'id' => 'max',
            ], function ($query) {
                $query->where('sumber', 'aktual');
            });
    }

    /**
     * Relasi laporan asap di zona ini.
     */
    public function laporanAsap(): HasMany
    {
        return $this->hasMany(LaporanAsap::class, 'zona_id');
    }

    /**
     * Relasi titik panas satelit FIRMS.
     */
    public function titikPanas(): HasMany
    {
        return $this->hasMany(TitikPanas::class, 'zona_id');
    }

    /**
     * Relasi sumber air, embung, dan pos di zona ini.
     */
    public function sumberAir(): HasMany
    {
        return $this->hasMany(SumberAir::class, 'zona_id');
    }

    /**
     * Relasi log siaran untuk zona ini.
     */
    public function logSiaran(): HasMany
    {
        return $this->hasMany(LogSiaran::class, 'zona_id');
    }

    /**
     * Relasi langganan web push untuk zona ini.
     */
    public function langgananPush(): HasMany
    {
        return $this->hasMany(LanggananPush::class, 'zona_id');
    }
}
