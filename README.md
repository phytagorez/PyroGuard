# PyroGuard

**Sistem pencegahan kebakaran lahan berbasis satelit untuk kawasan Bromo.**
PyroGuard memetakan risiko kebakaran di 30 desa sekitar Bromo, memberi arahan keselamatan kepada pengunjung, menerima laporan asap dari warga, dan menyambungkannya ke petugas lewat Telegram agar api kecil bisa ditangani sebelum meluas.

Dibuat untuk tema **Green Environment & Sustainability**.

---

## Daftar isi

- [Latar belakang](#latar-belakang)
- [Fitur](#fitur)
- [Cara kerja analisis risiko](#cara-kerja-analisis-risiko)
- [Teknologi](#teknologi)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Konfigurasi `.env`](#konfigurasi-env)
- [Mengisi data](#mengisi-data)
- [Menyiapkan Telegram](#menyiapkan-telegram)
- [Menjalankan aplikasi](#menjalankan-aplikasi)
- [Mode demo](#mode-demo)
- [Pengujian](#pengujian)
- [Peran pengguna](#peran-pengguna)
- [Struktur proyek](#struktur-proyek)
- [Keterbatasan yang perlu diketahui](#keterbatasan-yang-perlu-diketahui)
- [Keamanan](#keamanan)
- [Rencana pengembangan](#rencana-pengembangan)

---

## Latar belakang

Kebakaran lahan di kawasan Bromo berulang di musim kemarau dan sering bermula dari kelalaian kecil: api unggun, puntung rokok, atau percikan api. PyroGuard dibuat untuk menutup tiga celah:

1. **Pengunjung tidak tahu** bahwa tempat yang mereka datangi sedang rawan.
2. **Asap kecil terlambat dilaporkan**, sehingga api sempat membesar.
3. **Petugas kekurangan gambaran** tentang desa mana yang perlu dijaga lebih dulu.

## Fitur

| Fitur | Fungsi |
|---|---|
| **Dashboard peta risiko** | Peta 30 desa berwarna menurut kelas risiko (tinggi, sedang, rendah), daftar desa berurutan skor, dan penjelasan faktor penyebab tiap desa. |
| **Cek lokasi saya** | Mengenali desa tempat pengguna berada, menampilkan kelas risikonya, dan memberi arahan keselamatan (api unggun, rokok, sampah). Lokasi diproses di perangkat dan tidak dikirim ke server. |
| **Logistik & evakuasi** | Peta dan daftar embung, sumber air, pos jaga, dan titik kumpul, dengan filter kategori dan pencarian titik terdekat. Titik yang belum diverifikasi diberi label "Data contoh". |
| **Lapor asap** | Warga melaporkan asap atau api lewat formulir bergaris peta dan foto. Laporan dipetakan ke desa lewat poligon, mendapat nomor tiket, dan tautan status publik berkode acak. |
| **Peringatan Telegram untuk petugas** | Setiap laporan baru dikirim ke grup petugas berisi desa, kelas risiko, koordinat, tautan peta, serta sumber air dan pos terdekat, beserta tombol aksi (menuju lokasi, api nyata, bukan api). |
| **Terima peringatan** | Ringkasan kondisi kawasan, peringatan resmi terbaru dari kanal publik, panduan per desa ("Desaku"), tombol bagikan ke WhatsApp, daftar periksa pengunjung, dan tautan kanal Telegram. |
| **Panel petugas** | Daftar tiket laporan dan aksi: menuju lokasi, verifikasi, tandai ditangani. |
| **Panel admin** | Tinjauan risiko, persetujuan dan pencabutan siaga, penulisan siaran ke kanal atau grup, riwayat siaran (dapat diekspor CSV), daftar laporan, dan impor dataset geospasial. |
| **Mode demo** | Simulasi hari kemarau untuk menguji skenario tanpa mengubah data nyata. |
| **Metode & sumber data** | Halaman terbuka tentang sumber data, cara menghitung, dan keterbatasannya. |

## Cara kerja analisis risiko

Setiap desa mendapat **skor gabungan** dari analisis citra dan data iklim historis yang disusun di luar aplikasi, lalu diimpor ke PyroGuard.

- **Sumber data analisis:** Sentinel-2 (vegetasi dan kelembapan), CHIRPS (curah hujan), MODIS (suhu permukaan), dan VIIRS (titik panas), dengan kejadian kebakaran 2023 sebagai acuan.
- **Kelas risiko:** *Tinggi* bila skor 0,66 ke atas, *Sedang* untuk 0,40 sampai 0,65, dan *Rendah* di bawah 0,40.
- **Sifatnya relatif.** Kelas membandingkan desa satu sama lain. Kelas rendah bukan berarti aman, dan hasilnya adalah alat bantu menentukan prioritas pengecekan, **bukan prediksi kebakaran**.
- **Faktor penyebab** (rumput, kelembapan vegetasi, hujan, suhu permukaan, lereng, titik panas) dibandingkan antar desa. Hanya faktor yang menonjol yang ditampilkan.

Aplikasi juga memiliki mesin cuaca dan indeks bahaya api (Open-Meteo) untuk enam zona pemantauan awal. Mesin ini dipakai di panel admin untuk siaga otomatis dan Mode demo.

## Teknologi

- **Backend:** Laravel 13, PHP 8.4+
- **Basis data:** MySQL (pengembangan), SQLite dalam memori (tes)
- **Frontend:** Blade, Alpine.js 3, Tailwind CSS 4, Leaflet 1.9, dibangun dengan Vite 8
- **Autentikasi petugas dan admin:** akun lokal dan Google OAuth (Laravel Socialite)
- **Integrasi:** Telegram Bot API, Open-Meteo, NASA FIRMS (titik panas)
- **Pengujian:** Pest 5

## Persyaratan

- PHP 8.4 atau lebih baru dengan ekstensi: `mbstring`, `xml`, `curl`, `zip`, `intl`, `bcmath`, `gd` (dipakai kompresi foto), `pdo_mysql`
- Composer 2
- Node.js 20 atau lebih baru dan npm
- MySQL 8 atau MariaDB 10.6+

## Instalasi

```bash
# 1. Pasang dependensi
composer install
npm install

# 2. Siapkan konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Atur koneksi database di .env, lalu migrasi dan isi data awal
php artisan migrate --seed

# 4. Impor 30 desa hasil analisis satelit
php artisan app:import-dataset-desa

# 5. Bangun aset
npm run build
```

Untuk pengembangan, jalankan `npm run dev` di terminal terpisah agar perubahan tampilan langsung terlihat.

## Konfigurasi `.env`

Nilai berikut **harus diisi sendiri** dan tidak boleh dimasukkan ke repositori.

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_DATABASE=pyroguard
DB_USERNAME=root
DB_PASSWORD=

# Telegram
TELEGRAM_BOT_TOKEN=            # token dari @BotFather
TELEGRAM_BOT_USERNAME=         # tanpa tanda @
TELEGRAM_OFFICER_GROUP_ID=     # ID grup petugas (angka negatif)
TELEGRAM_PUBLIC_CHANNEL_ID=    # mis. @NamaKanal (kanal publik)
TELEGRAM_WEBHOOK_SECRET=       # teks acak buatanmu sendiri
TELEGRAM_BOT_INTERAKTIF=false  # ubah true setelah webhook terdaftar

# Data eksternal
OPEN_METEO_BASE_URL=https://api.open-meteo.com/v1/forecast
NASA_FIRMS_API_KEY=

# Login Google (opsional)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Setelah mengubah `.env`, jalankan `php artisan optimize:clear` lalu mulai ulang server.

## Mengisi data

### 30 desa (wajib)

```bash
php artisan app:import-dataset-desa
```

Membaca `storage/app/datasets/Desa_PyroGuard_Skor.geojson`. Aman dijalankan ulang: desa yang sudah ada diperbarui, bukan digandakan.

### Titik logistik (air, pos jaga, titik kumpul)

Isi `storage/app/datasets/template_logistik_bromo.csv`, simpan sebagai `logistik_bromo.csv`, lalu:

```bash
php artisan app:import-logistik --dry-run   # periksa dulu tanpa menyimpan
php artisan app:import-logistik             # simpan
```

Kolom CSV: `nama`, `jenis` (`embung`, `sumber_air`, `pos_jaga`, `titik_kumpul`), `latitude`, `longitude`, `kapasitas`, `status`, `sumber_data` (wajib), `tanggal_verifikasi`, `kontak`. Format titik koma dan koma desimal ala Excel Indonesia didukung. Baris yang salah ditolak dengan nomor baris dan alasannya.

Untuk demo tanpa data resmi:

```bash
php artisan app:import-logistik --contoh
```

Perintah ini memuat 15 titik berlabel **"Data contoh"**. Setelah data nyata masuk, hapus titik contoh dengan `php artisan app:import-logistik <file.csv> --hapus-contoh`.

### Cuaca

```bash
php artisan pyroguard:sinkron-cuaca
```

Menyinkronkan data Open-Meteo dan menghitung indeks bahaya api. Perintah ini **belum dijadwalkan otomatis**. Jalankan manual atau tambahkan ke scheduler Laravel.

## Menyiapkan Telegram

1. **Buat bot** lewat @BotFather (`/newbot`) dan simpan token di `.env`.
2. **Buat grup petugas**, tambahkan bot lewat profil bot (**Add to Group**), lalu ambil ID grup (angka negatif) dan isi `TELEGRAM_OFFICER_GROUP_ID`.
3. **Buat kanal publik** (jenis Public, punya username), jadikan bot sebagai **administrator** dengan izin Post Messages, lalu isi `TELEGRAM_PUBLIC_CHANNEL_ID=@usernameKanal`.
4. **Buat `TELEGRAM_WEBHOOK_SECRET`**, yaitu teks acak 1 sampai 256 karakter (huruf, angka, `_`, `-`):
   ```bash
   php -r "echo bin2hex(random_bytes(24));"
   ```
5. **Uji pengiriman.** Kirim satu laporan asap dari halaman Lapor. Pesan "LAPORAN ASAP BARU" harus muncul di grup petugas.

**Webhook.** Tombol aksi di grup dan perintah bot (`/status`, `/zona`) hanya bekerja bila Telegram dapat menghubungi aplikasi lewat alamat **HTTPS publik**. Di komputer lokal, gunakan tunnel (misalnya ngrok atau cloudflared), lalu daftarkan webhook ke jalur `/api/v1/telegram/webhook` dengan parameter `secret_token`. Periksa jalurnya dengan `php artisan route:list --path=telegram`. Setelah webhook aktif, ubah `TELEGRAM_BOT_INTERAKTIF=true` agar tombol bot tampil di halaman publik. Pengiriman pesan keluar (peringatan ke grup dan kanal) **tidak** memerlukan webhook.

## Menjalankan aplikasi

```bash
php artisan serve
```

Buka `http://localhost:8000`. Halaman publik:

| Alamat | Halaman |
|---|---|
| `/` | Dashboard peta risiko |
| `/evakuasi` | Logistik & evakuasi |
| `/lapor` | Lapor asap |
| `/lapor/status/{kode}` | Status laporan (kode acak, tanpa koordinat) |
| `/notifikasi` | Terima peringatan |
| `/demo` | Mode demo |
| `/metode` | Metode & sumber data |
| `/masuk` | Masuk petugas dan admin |

## Mode demo

Halaman `/demo` mensimulasikan hari kemarau dari hari 0 sampai hari 20 dan menampilkan bagaimana status kawasan naik menjadi waspada dan siaga. Semua angka di mode ini adalah **simulasi, bukan observasi lapangan**.

> Saat Mode demo aktif, pengiriman Telegram **hanya disimulasikan** dan tidak benar-benar terkirim. Tekan **Kembali ke Data Aktual** sebelum menguji pengiriman nyata.

## Pengujian

```bash
php artisan test
```

Tes memakai SQLite dalam memori sehingga tidak menyentuh database aslimu. Cakupan: halaman publik, pelaporan dan pemetaan desa, impor logistik, peringatan Telegram, status laporan, serta mesin risiko. Tes kompresi foto memerlukan ekstensi PHP `gd`.

## Peran pengguna

| Peran | Akses |
|---|---|
| **Publik** | Dashboard, logistik, lapor asap, status laporan, terima peringatan, mode demo |
| **Petugas** | Panel tiket di `/petugas`: menuju lokasi, verifikasi, tandai ditangani |
| **Admin** | Semua akses petugas, ditambah `/admin`: tinjauan risiko, siaga, siaran, riwayat siaran, laporan, dataset |

## Struktur proyek

```
app/
  Console/Commands/   impor desa, impor logistik, sinkron cuaca
  Http/Controllers/   halaman publik, petugas, admin, API v1
  Models/             Zona (desa), LaporanAsap, SumberAir, LogSiaran, ...
  Observers/          pemberitahuan perubahan tiket (bila terpasang)
  Services/           laporan, bot Telegram, mesin risiko, siaga, dataset
resources/
  views/              Blade (pantauan, evakuasi, lapor, notifikasi, admin, demo)
  js/                 pantauan.js, laporAsap.js, app.js
storage/app/datasets/ GeoJSON desa dan template CSV logistik
public/data/          dataset pendukung (CSV, metadata)
tests/Feature/        tes Pest
```

## Keterbatasan yang perlu diketahui

- **Bukan prediksi.** Kelas risiko bersifat relatif antar desa dan berbasis analisis historis.
- **Data kejadian terbatas.** Analisis historis yang dimuat baru mencakup kejadian 2023. Pola musiman multi-tahun belum dapat ditampilkan.
- **Data logistik masih contoh** sampai data resmi dari TNBTS atau BPBD diimpor.
- **Web Push belum aktif.** Satu-satunya jalur peringatan yang bekerja saat ini adalah Telegram. Halaman "Terima peringatan" sengaja berpusat pada kanal Telegram.
- **Mesin cuaca dan siaga otomatis** bekerja pada enam zona pemantauan awal, bukan pada 30 desa.
- **Sinkron cuaca belum terjadwal otomatis.**
- **Status tiket dari Telegram** memerlukan webhook ke alamat HTTPS publik.

## Keamanan

- Jangan memasukkan `.env`, token bot, atau kunci API ke repositori atau video demo. Bila token pernah terlihat publik, cabut di @BotFather (`/mybots`, pilih bot, **API Token**, **Revoke current token**).
- Tautan status laporan memakai **kode acak**, bukan nomor tiket berurutan, dan tidak menampilkan koordinat, foto, atau data pelapor.
- Lokasi pengguna pada fitur "Cek lokasi saya" diproses di perangkat.
- **Catatan penting:** versi ini belum memeriksa header rahasia webhook (`X-Telegram-Bot-Api-Secret-Token`) dan belum membatasi tombol aksi hanya untuk grup petugas. Selesaikan dua hal ini **sebelum** mendaftarkan webhook ke alamat publik.

## Rencana pengembangan

- Pemeriksaan rahasia webhook dan otorisasi tombol aksi Telegram.
- Peringatan Telegram otomatis untuk siaga otomatis, perubahan status tiket, dan perubahan kelas risiko desa.
- Langganan peringatan per desa lewat bot.
- Penambahan data kejadian kebakaran tahun lain untuk pola musiman.
- Pengisian data logistik resmi bersama TNBTS dan BPBD.
- Penjadwalan sinkron cuaca dan pembaruan analisis satelit berkala.

## Kredit

Dibuat oleh **[nama tim]** untuk **[nama lomba]**.
Peta dasar © kontributor OpenStreetMap. Data satelit: Copernicus Sentinel-2, CHIRPS, MODIS, dan VIIRS.

**Lisensi:** [isi lisensi proyek].
