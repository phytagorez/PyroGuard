<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model Pengguna (User) PyroGuard
 * Mendukung autentikasi multi-peran (publik, petugas, admin) dan integrasi Telegram/Google
 */
class Pengguna extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Nama tabel di basis data.
     *
     * @var string
     */
    protected $table = 'pengguna';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'email',
        'nomor_telepon',
        'password',
        'peran',
        'google_id',
        'telegram_chat_id',
        'kode_tautan_telegram',
        'skor_kepercayaan',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Pemetaan tipe data atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'skor_kepercayaan' => 'integer',
        ];
    }

    /**
     * Relasi ke laporan asap yang dibuat oleh pengguna ini.
     */
    public function laporanAsap(): HasMany
    {
        return $this->hasMany(LaporanAsap::class, 'pelapor_id');
    }

    /**
     * Relasi ke laporan yang diverifikasi oleh petugas ini.
     */
    public function laporanDiverifikasi(): HasMany
    {
        return $this->hasMany(LaporanAsap::class, 'diverifikasi_oleh');
    }

    /**
     * Relasi status siaga yang disetujui oleh admin ini.
     */
    public function persetujuanSiaga(): HasMany
    {
        return $this->hasMany(StatusSiaga::class, 'disetujui_oleh');
    }

    /**
     * Relasi status siaga yang dicabut oleh admin ini.
     */
    public function pencabutanSiaga(): HasMany
    {
        return $this->hasMany(StatusSiaga::class, 'dicabut_oleh');
    }

    /**
     * Relasi log siaran yang disetujui.
     */
    public function siaranDisetujui(): HasMany
    {
        return $this->hasMany(LogSiaran::class, 'disetujui_oleh');
    }

    /**
     * Relasi langganan web push pengguna.
     */
    public function langgananPush(): HasMany
    {
        return $this->hasMany(LanggananPush::class, 'pengguna_id');
    }

    /**
     * Cek apakah pengguna adalah admin (mis. BPBD).
     */
    public function isAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    /**
     * Cek apakah pengguna adalah petugas lapangan (mis. TNBTS).
     */
    public function isPetugas(): bool
    {
        return $this->peran === 'petugas';
    }

    /**
     * Cek apakah pengguna adalah masyarakat umum/wisatawan.
     */
    public function isPublik(): bool
    {
        return $this->peran === 'publik';
    }
}
