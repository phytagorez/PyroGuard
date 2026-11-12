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
     * Hanya desa hasil analisis satelit (punya data_analisis).
     * Zona BRM lama tidak ikut tampil di peta dan pelaporan publik.
     */
    public function scopeDesa($query)
    {
        return $query->whereNotNull('data_analisis');
    }

    /**
     * Kelas risiko desa (Tinggi/Sedang/Rendah) dari analisis satelit.
     */
    public function getKelasRisikoAttribute(): ?string
    {
        $kelas = $this->data_analisis['Kelas'] ?? null;

        return in_array($kelas, ['Tinggi', 'Sedang', 'Rendah'], true) ? $kelas : null;
    }

    /**
     * Cari desa yang memuat koordinat. Bila titik berada di luar semua poligon
     * (mis. GPS meleset di batas desa), pakai desa dengan titik tengah terdekat.
     */
    public static function cariDesaDi(float $lat, float $lng): ?self
    {
        $desas = static::desa()->get();

        foreach ($desas as $desa) {
            if (self::titikDalamGeometri([$lng, $lat], $desa->poligon_geojson)) {
                return $desa;
            }
        }

        return $desas->sortBy(function (self $desa) use ($lat, $lng) {
            return (($desa->titik_tengah_lat - $lat) ** 2) + (($desa->titik_tengah_lng - $lng) ** 2);
        })->first();
    }

    /**
     * Uji titik-dalam-poligon (ray casting) untuk Polygon dan MultiPolygon GeoJSON.
     *
     * @param  array{0: float, 1: float}  $titik  [lng, lat]
     */
    public static function titikDalamGeometri(array $titik, ?array $geometri): bool
    {
        if (! $geometri || empty($geometri['coordinates'])) {
            return false;
        }

        $polygons = ($geometri['type'] ?? '') === 'MultiPolygon'
            ? $geometri['coordinates']
            : [$geometri['coordinates']];

        foreach ($polygons as $polygon) {
            if (! self::titikDalamRing($titik, $polygon[0] ?? [])) {
                continue;
            }

            $dalamLubang = false;
            foreach (array_slice($polygon, 1) as $lubang) {
                if (self::titikDalamRing($titik, $lubang)) {
                    $dalamLubang = true;
                    break;
                }
            }

            if (! $dalamLubang) {
                return true;
            }
        }

        return false;
    }

    protected static function titikDalamRing(array $titik, array $ring): bool
    {
        [$x, $y] = $titik;
        $dalam = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                $dalam = ! $dalam;
            }
        }

        return $dalam;
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
