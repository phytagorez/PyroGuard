# design.md — Desain Produk & Sistem Desain PyroGuard

**Sistem Peringatan Dini Kebakaran Lahan Kawasan Bromo**
Versi dokumen: 1.0 · Turunan dari PRD PyroGuard v1.0 (draf lomba)
Kategori: *Digital Innovation: Crafting Creative Solutions for a Better Society* — subtema **Green Environment & Sustainability**
Platform: PWA mobile-first · Wilayah: Bromo Tengger Semeru (Jawa Timur)

> Dokumen ini adalah **spesifikasi desain yang siap dieksekusi**: token, komponen, layar, state, copy, dan kontrak data. Setiap keputusan desain ditautkan ke kebutuhan fungsional PRD (contoh: `FR-MAP-02`) agar bisa ditelusuri saat pembangunan dan penjurian.

---

## 0. Cara membaca dokumen ini

| Bagian | Untuk siapa | Yang didapat |
| --- | --- | --- |
| §1–§4 | semua | Narasi produk, prinsip desain, persona, peta navigasi |
| §5 | desainer + developer | Token warna, tipografi, spasi, radius, elevasi, gerak |
| §6 | developer | Anatomi komponen, ukuran, state, dan aturan pakai |
| §7 | desainer + juri | Spesifikasi layar mobile-first dengan prioritas informasi |
| §8–§10 | developer data/peta | Peta risiko, state sistem, gerak & umpan balik |
| §11–§14 | semua | Aksesibilitas, performa, copy, notifikasi |
| §15–§17 | developer + admin | Mode demo, kontrak UI↔API↔mesin risiko |
| §18–§21 | tim + juri | Rencana uji, backlog, risiko, istilah |

Konvensi penulisan: `kode` untuk nama token/endpoint/tabel, **tebal** untuk keputusan yang mengikat, dan `P1/P2/P3` mengikuti prioritas PRD. Semua angka, koordinat, dan ambang di dokumen ini adalah **ilustrasi draf** sampai divalidasi (lihat §20).

---

## 1. Ringkasan desain

PyroGuard harus bisa dipahami **dalam 10 detik pertama** oleh tiga orang yang sangat berbeda: Rina (wisatawan, sinyal lemah, sedang berjalan di lautan pasir), Pak Agus (petugas TNBTS, satu tangan memegang ponsel, satu tangan memegang HT), dan Ibu Sri (admin BPBD, memutuskan Siaga untuk empat kabupaten).

Karena itu desainnya bertumpu pada tiga lapis:

1. **Lapis jawaban (10 detik):** banner status zona berisiko tertinggi + satu instruksi larangan yang jelas. Tanpa perlu scroll, tanpa perlu login.
2. **Lapis penjelasan (1 menit):** panel zona dengan CDD, FDI, RH, angin, jam rentan, dan "Mengapa status ini" — semua angka disertai satuan dan label aktual/prakiraan.
3. **Lapis tindakan (alur):** Lapor asap → tiket → verifikasi lapangan → titik api di peta + pesan radius 3 km; serta Bahaya → tinjauan Admin → Siaga 1 → siaran bertingkat.

Nilai desain yang dijual ke juri bukan jumlah fitur, melainkan **kejelasan rantai keputusan**: warga melihat risiko, petugas menerima tiket, otoritas menyetujui siaga, dan semuanya tercatat.

---

## 2. Prinsip desain

**P1 — Urgensi lebih dulu, dekorasi belakangan.**
Baris pertama setiap layar menjawab "apakah saya aman sekarang?". Peta, grafik, dan metrik turun ke bawah. Jika sebuah elemen tidak membantu satu keputusan, ia bukan P1.

**P2 — Warna tidak pernah sendiri.**
Merah/kuning/hijau selalu berpasangan dengan teks status (`BAHAYA`), ikon, dan bentuk/tekstur. Ini melindungi pengguna dengan buta warna, layar yang terkena matahari, dan tangkapan layar hitam-putih yang beredar di grup WhatsApp.

**P3 — Setiap angka punya arti dan waktu.**
`CDD 19` selalu ditulis "19 hari tanpa hujan" dengan label `Aktual` atau `Prakiraan +48 jam` dan cap waktu pembaruan. Angka yang tidak dijelaskan lebih berbahaya daripada tidak ada angka.

**P4 — Desain untuk kondisi terburuk.**
Sinyal 1 bar, tangan berkeringat, layar 360 px, matahari langsung, pengguna panik. Target sentuh 44 px, kontras tinggi, teks 16 px minimum, data terakhir selalu tersimpan (`PWA cache`) dengan status kedaluwarsa.

**P5 — Aksi lebih penting daripada informasi.**
Tombol menyebut kata kerja dan akibatnya: "Lapor asap", "Menuju lokasi", "Setujui Siaga 1", "Kirim siaran (12 penerima)". Tidak ada tombol tanpa akibat yang jelas.

**P6 — Otoritas terlihat, bukan diasumsikan.**
Setiap status Siaga menyebut siapa yang menyetujui dan kapan. PyroGuard diposisikan sebagai **alat bantu keputusan**, dan desain harus terbaca begitu — termasuk label "prototipe" dan tautan Metode & keterbatasan.

**P7 — Satu tangan, satu alur.**
Aksi utama selalu berada di zona jempol bawah layar pada mobile; lembar (sheet) menutup dengan sapuan ke bawah; tidak ada modal bertingkat.

---

## 3. Persona dan implikasi desain

| Persona | Konteks pakai | Implikasi desain langsung |
| --- | --- | --- |
| **Rina**, wisatawan 26 th | Tautan dari e-tiket, 4G lemah, berjalan di tangga Penanjakan | Tanpa login, muat < 3 s, banner status besar, larangan api terbuka & flare, tombol Lapor asap sticky, simpan status terakhir |
| **Pak Agus**, petugas TNBTS 41 th | Telegram, sarung tangan, matahari terik | Tiket dengan tombol inline, halaman verifikasi 1 layar tanpa scroll, tombol hasil 56 px, unggah foto langsung kamera |
| **Ibu Sri**, admin BPBD 38 th | Desktop kantor, rapat koordinasi | Antrean tinjauan prediksi, pratinjau pesan persis seperti yang akan diterima warga, persetujuan 2 langkah dengan catatan penyetuju |
| **Bayu**, relawan/RT 24 th | Ponsel Android kelas menengah, kuota terbatas | Daftar tiket radius 5 km, status tiket jelas, tanpa grafik berat |
| **Pengembang/IT** (super admin, P3) | Laptop | Kesehatan integrasi, bobot rumus, log siaran |

Implikasi lintas persona: **satu bahasa visual**, dua kepadatan. Lapisan jawaban memakai tipografi besar dan warna penuh; lapisan penjelasan memakai angka tabular dan label mono. Pengunjung melihat versi ringkas, petugas/admin melihat versi padat dari komponen yang sama (`ZonePanel`, `RiskBadge`).

---

## 4. Arsitektur informasi & navigasi

```
Pantauan (/)                        ← layar utama, selalu jadi halaman awal
├── Banner status kawasan            (FR-MAP-02)
├── Peta zona 5 lapisan              (FR-MAP-01, FR-MAP-05)
├── Ringkasan zona (kartu)           (FR-MAP-08)
├── Panel zona  → tab Ringkasan | Mengapa | Tren | Jam rentan
├── Jalur evakuasi & embung          (P2)
├── Lapor asap  → formulir → tiket   (FR-REP-01..03)
├── Notifikasi  (langganan web-push) (FR-NOT-01)
├── Metode & keterbatasan            (P2, wajib untuk kredibilitas)
└── Masuk
    ├── Verifikator: Tiket (daftar + detail + verifikasi)
    └── Admin: Tinjauan prediksi · Status Siaga · Siaran · Laporan · Log

Mode demo: sakelar global non-persisten di header admin/demo (FR-DEM-01)
```

Aturan navigasi:

- **Mobile:** bilah bawah 4 item — `Pantauan`, `Lapor`, `Notifikasi`, `Lainnya`. Tombol `Lapor asap` juga hadir sebagai FAB besar di atas bilah bawah saat berada di Pantauan.
- **Desktop (≥1024 px):** navigasi kiri 240 px, peta 8 kolom, panel zona 4 kolom tetap terbuka (tidak lagi berupa sheet).
- Halaman admin memakai shell berbeda (sidebar gelap, konten padat) namun token dan komponen status identik.
- Tidak ada layar yang membutuhkan lebih dari **2 ketukan** untuk mencapai tombol Lapor asap atau status zona.

---

## 5. Token desain

Semua token didefinisikan sebagai CSS variable di `globals.css` dan dipakai lewat utilitas Tailwind (`bg-paper`, `text-ink`, `border-line`). Nama token tidak boleh dipakai untuk hal lain (mis. hijau **hanya** untuk status aman dan aksen brand, tidak untuk tombol netral).

### 5.1 Warna

**Netral (latar & teks)** — latar abu-hijau hangat agar tidak menyilaukan di luar ruangan, sekaligus membedakan PyroGuard dari dashboard abu-abu generik.

| Token | Hex | Pakai | Kontras |
| --- | --- | --- | --- |
| `--paper` | `#F5F1E8` | latar aplikasi | teks `ink` **14,89:1** |
| `--paper-2` | `#EDE7DA` | kartu, sheet, baris tabel | teks `ink` **13,62:1** |
| `--paper-3` | `#E3DBC9` | divider tebal, skeleton | — |
| `--line` | `#D8D0BE` | garis 1 px | — |
| `--ink` | `#14201A` | teks utama | — |
| `--ink-muted` | `#4C5A52` | label, satuan | pada paper **6,44:1** |
| `--forest` | `#173D2C` | header, tombol primer, admin shell | putih **12,07:1** |
| `--moss` | `#2F7A4F` | aksen brand, tautan sukses | pada paper **4,64:1** |

**Warna status risiko (hanya untuk risiko).**

| Token | Hex | Teks di atasnya | Pakai |
| --- | --- | --- | --- |
| `--safe-fill` | `#CFE6D6` | `#14532D` | isi poligon zona aman, latar kartu |
| `--safe` | `#1F6B45` | `#FFFFFF` (6,47:1) | badge solid, garis poligon |
| `--warn-fill` | `#FBE3A6` | `#5C3D06` | isi poligon waspada |
| `--warn` | `#A86412` | `#FFFFFF` (4,67:1) | badge solid, garis poligon |
| `--warn-tag` | `#F2B23E` | `#14201A` (8,96:1) | tag di dalam peta (teks gelap) |
| `--danger-fill` | `#D9544A` | `#FFFFFF` | isi poligon bahaya |
| `--danger` | `#9E2B22` | `#FFFFFF` (7,44:1) | badge solid, garis, header siaga |
| `--siaga` | `#7F1D1D` | `#FFFFFF` (10,02:1) | Siaga 1, pola garis diagonal |
| `--ember` | `#D9541F` | — | aksen api/aksi lapor, **hanya dekoratif atau ≥24 px** (3,56:1 pada paper) |

**Warna fungsional non-risiko.**

| Token | Hex | Pakai |
| --- | --- | --- |
| `--water` | `#2B6CB0` | **khusus** titik air, embung, sumber air (putih 5,42:1) |
| `--evac` | `#5B4B8A` | jalur evakuasi (ungu, agar tidak tertukar dengan zona maupun air) |
| `--hotspot` | `#B3261E` | titik panas satelit (bentuk belah ketupat, bukan lingkaran) |
| `--report` | `#0F766E` | laporan warga terverifikasi (bentuk segitiga) |
| `--focus` | `#1D4ED8` | cincin fokus keyboard 3 px, offset 2 px |

> **Uji cepat warna:** jadikan tangkapan layar hitam-putih. Status harus tetap terbaca dari pola: `AMAN` = poligon isi penuh + ikon perisai, `WASPADA` = isi + titik-titik halus, `BAHAYA` = isi + garis diagonal tipis 45°, `SIAGA 1` = garis diagonal rapat + pita "SIAGA 1" di tepi atas.

### 5.2 Tipografi

| Peran | Keluarga | Ukuran / line-height | Bobot | Catatan |
| --- | --- | --- | --- | --- |
| Display status | Space Grotesk (fallback system-ui) | 28–44 px / 1,05 | 700 | huruf kapital, `letter-spacing -0.01em` |
| Judul layar | Space Grotesk | 22 px / 1,2 | 600 | — |
| Judul kartu | Space Grotesk | 16–18 px / 1,35 | 600 | — |
| Teks isi | Inter (fallback system-ui) | 16 px / 1,55 | 400 | minimum absolut 16 px di mobile |
| Teks pendukung | Inter | 14 px / 1,5 | 400–500 | hanya untuk informasi sekunder |
| Label / metadata | JetBrains Mono (fallback ui-monospace) | 11–12 px / 1,3 | 500 | `UPPERCASE`, `0.08em`, token & waktu |
| Angka metrik | Space Grotesk + `font-variant-numeric: tabular-nums` | 24–32 px / 1 | 600 | satuan selalu mono 12 px |

Skala spasi: `4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 56` px. Radius: `sm 8`, `md 12`, `lg 18`, `xl 26`, `pill 999`. Elevasi: hanya dua — `shadow-sheet: 0 -12px 40px rgba(20,32,26,.14)` dan `shadow-card: 0 2px 0 rgba(20,32,26,.04), 0 8px 24px rgba(20,32,26,.06)`. Tidak ada glow, tidak ada gradasi warna status.

### 5.3 Gerak

| Token | Durasi | Kurva | Dipakai untuk |
| --- | --- | --- | --- |
| `--motion-fast` | 120 ms | ease-out | state hover/press, badge |
| `--motion-base` | 220 ms | cubic-bezier(.2,.8,.2,1) | sheet, panel, perluasan kartu |
| `--motion-slow` | 420 ms | ease-in-out | transisi warna poligon risiko |
| denyut peringatan | 1800 ms | ease-in-out | titik api baru, **maks 3 denyut lalu diam** |

Semua gerak dihapus pada `prefers-reduced-motion: reduce`; denyut diganti garis luar statis 3 px.

---

## 6. Pustaka komponen

Setiap komponen di bawah ini punya **satu sumber kebenaran**: dipakai di prototipe, di desain final, dan di halaman dokumen ini sebagai spesimen hidup. Nama komponen memakai PascalCase dan dipetakan ke berkas `src/components/`.

### 6.1 `StatusBanner` — jawaban 10 detik ⭐ P1
Anatomi (mobile):
```
┌──────────────────────────────────────────────┐
│ LABEL ZONA · KODE          [PEMBARUAN 07:00] │  ← mono 11 px, ink-muted
│ BAHAYA                                       │  ← display 34 px, putih
│ Savana & Jemplang · 19 hari tanpa hujan      │  ← 14 px, opasitas 90%
│ FDI 86/100 · angin 28 km/j dari tenggara     │  ← 12 px mono
│ [ Lihat tindakan yang harus dilakukan → ]    │  ← tombol outline putih
└──────────────────────────────────────────────┘
```
Aturan: latar mengikuti status tertinggi di kawasan (`danger` → `siaga` bila disetujui), sudut 18 px, padding 20 px, selalu di posisi teratas, **tidak pernah di bawah peta**. Status ditulis dalam teks; warna hanya penguat. Bila data > 6 jam, tambahkan pita `DATA TERAKHIR 6 JAM LALU` (kuning) dan tombol `Muat ulang`.

### 6.2 `RiskBadge` — P1
Pil 24 px tinggi, `padding 0 10px`, radius pill, label mono 11 px uppercase + titik 6 px (bentuk berbeda per status: perisai/isi/diagonal). Varian `solid` (di atas peta/header) dan `soft` (di dalam kartu, latar `*-fill`). Ukuran `sm` 20 px untuk tabel admin, `md` untuk kartu zona. Teks selalu persis salah satu dari: `AMAN`, `WASPADA`, `BAHAYA`, `SIAGA 1`, `DICABUT`. Jangan menulis "Sedang", "Tinggi", dsb.

### 6.3 `ZonePanel` / `ZoneSheet` — P1, P2
| Tab | Isi | Sumber FR |
| --- | --- | --- |
| Ringkasan | CDD aktual, proyeksi +24/+48/+72 (chip kecil), FDI meter, RH minimum, angin + arah, jam rentan, status Siaga + penyetuju, atribut zona (jenis lahan, akses wisata) | FR-MAP-03 |
| Mengapa | Tiga batang kontribusi faktor (CDD 54, kelembapan 24, angin 8) + kalimat penjelas + tautan Metode | FR-MAP-04 |
| Tren | Garis CDD 30 hari (Chart.js), garis putus-putus untuk prakiraan, penanda hari hujan | FR-MAP-06 |
| Jam rentan | Sparkline RH & angin 24 jam, pita jendela rentan disorot (`12:00–15:30 WIB`) | FR-MAP-07 |

Mobile: bottom sheet tinggi 85vh, pegangan 40×4 px, judul zona terlihat saat tertutup. Desktop: kolom kanan sticky. Panel selalu menutup dengan `Esc`, sapuan ke bawah, atau tombol `Tutup`.

### 6.4 `FdiMeter` — P1
Bilah horizontal 8 px, radius pill, segmen skala 0–100 dengan tiga penanda ambang (0–40 aman, 41–79 waspada, 80–100 bahaya), nilai numerik di kanan (`86/100`, angka tabular). Tidak memakai jarum animasi saat halaman dimuat.

### 6.5 `FactorBars` — P2
Tiga baris: label faktor, nilai poin (`54 poin`), batang dengan panjang proporsional terhadap total FDI, warna netral `forest` (bukan warna status, agar tidak bersaing dengan banner). Baris dengan kontribusi terbesar diberi label `Penyumbang utama`.

### 6.6 `FreshnessStamp` — P1
`DIPERBARUI 07:00 WIB · SUMBER OPEN-METEO` dalam mono 11 px. Tiga keadaan: segar (<2 jam), perhatian (2–6 jam, warna `warn`), kedaluwarsa (>6 jam, `danger` + kalimat "Data cuaca terakhir tidak dapat diperbarui. Ikuti instruksi resmi TNBTS/BPBD."). Selalu terlihat tanpa perlu membuka menu.

### 6.7 `ReportFab` dan `EmergencyBar` — P1
`ReportFab`: pil `ember` lebar penuh, tinggi 56 px, ikon asap + teks **"Lapor asap"**, menempel 12 px di atas bilah navigasi, aman terhadap `safe-area-inset-bottom`. `EmergencyBar` muncul pada layar detail tiket dan Jalur evakuasi: dua tombol berbagi lebar — `Telepon 112` (outline) dan `Bagikan lokasi` (solid).

### 6.8 `ReportForm` — P1, FR-REP-01
Langkah tunggal, dikelompokkan:
1. **Lokasi** — otomatis dari GPS (`Akurasi ±12 m`, tombol `Perbarui`), fallback unggah titik manual di peta; bila izin ditolak tampilkan `Pilih lokasi di peta`.
2. **Jenis** — chip radio 4 pilihan: `Asap tipis`, `Asap tebal`, `Api terlihat`, `Bekas terbakar` (target 44 px).
3. **Foto** — maksimal 2, sumber `Kamera`/`Galeri`, pratinjau 1:1 dengan cap waktu & koordinat kecil; unggahan maks 5 MB per berkas, tipe `image/jpeg|png|heic`.
4. **Catatan** — textarea 3 baris, placeholder "Contoh: asap dari arah bukit, angin ke barat".
5. **Privasi** — kalimat "Lokasi Anda hanya terlihat petugas. Laporan tamu tanpa riwayat." + tautan kebijakan.
6. Tombol `Kirim laporan` (solid, 56 px) + status pembatasan laju: "Anda dapat mengirim 3 laporan per 15 menit."
Setelah sukses: `TicketSuccess` — nomor tiket besar (`#LP-0231`), status `Baru`, estimasi "Petugas terdekat diberi tahu", tombol `Simpan nomor tiket` dan `Lapor lagi` (nonaktif 60 detik).

### 6.9 `TicketCard` & `TicketTimeline` — P1, P2
Kartu tiket: nomor mono, `RiskBadge` zona, jarak (`1,8 km dari Anda`), waktu relatif (`4 menit lalu`), jenis + thumbnail foto, status. Untuk petugas: tombol aksi inline `Menuju lokasi` (buka peta), `Api nyata`, `Bukan api`, `Ganda` — ukuran ≥ 48 px dan berjeda 8 px agar tidak salah tekan. `TicketTimeline` vertikal 6 status (Baru → Ditugaskan → Menuju lokasi → Terverifikasi → Ditangani → Selesai) dengan waktu pada tiap simpul.

### 6.10 `ApprovalCard` — P1, FR-NOT-02
Kartu satu zona berisi ringkasan bukti (CDD, FDI, proyeksi, titik panas 7 hari, laporan terverifikasi), **pratinjau pesan** yang akan disiarkan (persis isi Telegram & web-push), daftar penerima (`±128 pelanggan · 3 grup petugas`), lalu aksi `Setujui Siaga 1` (danger) dan `Tunda`. Persetujuan meminta konfirmasi kedua dengan input catatan opsional; setelah itu tampil `Disetujui oleh Ibu Sri (BPBD) · 09:14 WIB` dan tombol `Cabut Siaga` (outline) yang juga menampilkan pratinjau pesan pencabutan.

### 6.11 `MessagePreview` — P1, FR-NOT-09
Kotak gaya kanal (Telegram hijau muda, web-push netral, WhatsApp abu) yang menampilkan pesan nyata dengan placeholder terisi dari zona aktif. Dilarang menampilkan pesan "lorem". Admin dapat menyalin isi pesan; mode demo menambahkan pita `SIMULASI · tidak dikirim`.

### 6.12 `LayerSwitch` — P2, FR-MAP-05
Tiga sakelar 44 px: `Zonasi risiko`, `Titik panas & laporan`, `Logistik & angin`. Ikon + teks + status aktif. Maksimal 3 lapisan agar peta tidak menjadi "peta kematian" penuh warna. Legenda berubah sesuai lapisan aktif.

### 6.13 `DemoDial` — P2, FR-DEM-02
Penggeser hari ke-0…ke-20 kemarau dengan penanda CDD simulasi, tombol `Hari ini` untuk kembali, dan pita tetap `MODE DEMO`. Warna peta berubah dengan transisi 420 ms dan grafik nilai berjalan. Setiap pesan dari mode ini diberi awalan `[SIMULASI]`.

### 6.14 `Toast` dan `InlineAlert` — P1
Toast maksimal 1 baris, 4 detik, di atas FAB; tidak dipakai untuk status risiko. `InlineAlert` varian `info` (biru air), `warn`, `danger`, `offline` untuk state sistem (§9). Semua `role="status"` atau `role="alert"` dengan teks yang bisa dibaca pembaca layar.

### 6.15 `EmptyStateOffline` — P1/P2
Ikon garis, judul "Anda sedang luring", salinan status & peta terakhir dengan waktu, nomor darurat, tombol `Coba lagi`.

---

## 7. Spesifikasi layar (mobile-first)

Setiap layar ditulis dengan urutan baca nyata pada layar 360×800 px. Desain desktop = penataan ulang grid yang sama, bukan layar terpisah.

### 7.1 Pantauan — halaman utama (`/`)
| Urutan | Elemen | Catatan |
| --- | --- | --- |
| 1 | Header ringkas: kunci PyroGuard + chip `BROMO` + ikon lonceng | tinggi 52 px, sticky, latar `paper` dengan garis bawah |
| 2 | `StatusBanner` zona tertinggi | FR-MAP-02, selalu terlihat sebelum peta |
| 3 | Peta 16:10 dengan 6 poligon | Leaflet + OSM; label zona selalu tampil; legenda ringkas 1 baris |
| 4 | `LayerSwitch` | 3 sakelar, P2 |
| 5 | Ringkasan zona: daftar kartu terurut risiko | kartu 72 px: nama, `RiskBadge`, CDD, FDI, chevron |
| 6 | Dua tombol berjajar `Jalur evakuasi` dan `Sumber air terdekat` | ikon + teks, 48 px |
| 7 | Kartu "Bukti lapangan": titik panas 72 jam + laporan terverifikasi | FR-MAP-05 |
| 8 | `FreshnessStamp` + tautan `Metode & keterbatasan` | kecil, tidak mencolok |
| — | FAB `Lapor asap` sticky | di atas bilah navigasi |

State kosong/gagal: skeleton peta 3 baris + `InlineAlert` "Data cuaca tidak dapat dimuat" + data terakhir. Tidak pernah menampilkan peta kosong tanpa penjelasan.

### 7.2 Panel zona
Sheet 85vh dari daftar/peta. Header: nama zona + kode + `RiskBadge` + `FreshnessStamp`. Tab: Ringkasan · Mengapa · Tren · Jam rentan. Footer sticky: `Laporkan asap di zona ini` (mengisi zona pada formulir) dan `Lihat jalur evakuasi`.

Isi Ringkasan, urutan: (1) dua metrik besar `CDD 19 hari` & `FDI 86/100`; (2) proyeksi tiga chip `+24 jam 20`, `+48 jam 21`, `+72 jam 22` dengan label `prakiraan`; (3) `FdiMeter`; (4) baris kelembapan, angin, arah, suhu; (5) `Jam rentan 12:00–15:30 WIB` dengan alasan; (6) status Siaga + penyetuju/waktu; (7) atribut zona ("Savana, akses wisata tinggi").

### 7.3 Lapor asap (`/lapor`)
Header "Lapor asap" + subjudul "Laporan Anda diverifikasi petugas sebelum dipakai." Formulir §6.8. Setelah sukses → `TicketSuccess`. Tamu melihat catatan "Anda melapor sebagai tamu".

### 7.4 Notifikasi (`/notifikasi`)
Penjelasan singkat 2 kalimat tentang apa yang dikirim (Waspada sekali sehari per zona, Siaga 1 setelah disetujui admin, titik api radius 3 km). Tombol `Aktifkan notifikasi` 56 px, lalu pemilihan zona (`Zona saya` otomatis + daftar 6 zona) dan tombol `Hubungkan Telegram` (menampilkan kode sekali pakai `t.me/PyroGuardBot?start=KODE`). Status per kanal: `Aktif`, `Diblokir browser`, `Belum terpasang (iOS perlu tambah ke layar utama)` — dengan instruksi pemasangan.

### 7.5 Jalur evakuasi & embung (`/evakuasi`, P2)
Peta penuh dengan lapisan `water` dan `evac`, daftar sumber air (nama, jenis, debit, jarak, status siaga), daftar titik kumpul + arah angin dominan. Tombol `Telepon 112` dan `Bagikan lokasi saya` selalu ada.

### 7.6 Metode & keterbatasan (`/metode`, P2 — wajib)
Struktur: apa itu CDD dan FDI, formula dengan notasi yang mudah dibaca, ambang yang dipakai beserta tanggal kalibrasi, **hasil backtesting apa adanya** (hit rate, false alarm rate, jumlah kejadian), keterbatasan (resolusi Open-Meteo di pegunungan, belum ada NDVI/stasiun lokal, bukan sistem resmi), dan cara alternatif (BMKG, TNBTS, BPBD). Nada: jujur, tanpa klaim berlebihan.

### 7.7 Alur petugas — daftar & detail tiket (`/petugas`)
Daftar: filter status (chip), urutan jarak, kartu tiket dengan tombol verifikasi. Detail: peta kecil + jarak + foto pelapor + riwayat status. Aksi `Menuju lokasi`, lalu tiga tombol hasil verifikasi berukuran besar dengan konsekuensi tertulis:
- `Api nyata` → "Titik api tampil di peta dan pesan dikirim ke pengguna dalam 3 km."
- `Bukan api` → "Laporan ditutup, tidak ada siaran."
- `Ganda` → "Digabung ke tiket #LP-0231."
Unggah foto verifikasi opsional. Selesai → status `Terverifikasi` + permintaan `Tandai ditangani` (P1).

### 7.8 Admin (`/admin`)
| Layar | Elemen kunci | FR |
| --- | --- | --- |
| Tinjauan prediksi | Tabel 6 zona: CDD, proyeksi, FDI, kategori, perubahan terakhir; filter "perlu tinjauan" | FR-ENG-05 |
| Persetujuan Siaga | `ApprovalCard` per zona berstatus `SIAGA_OTOMATIS`, riwayat penyetuju | FR-NOT-02, FR-USR-04 |
| Siaran | Editor dari templat (judul, fakta, instruksi, kontak), pratinjau multi-kanal, penghitung penerima, log siaran | FR-NOT-08/09 |
| Laporan | Tabel laporan + status verifikasi + skor kepercayaan pelapor; koordinat hanya di sini & petugas | FR-REP-07/09 |
| Log siaran | `jenis · zona · kanal · penyetuju · penerima · waktu`; ekspor CSV | FR-NOT-08 |

Persetujuan memakai pola **dua langkah + jejak audit**: tekan `Setujui` → dialog bernarasi "Siaran akan dikirim ke 128 pelanggan dan kanal publik Telegram" → konfirmasi. Tombol tidak pernah langsung mengirim pada ketukan pertama.

### 7.9 Masuk & peran
Satu layar, masuk dengan email/nomor + kata sandi, tautan `Daftar sebagai petugas` (memerlukan verifikasi peran). Penjaga rute menampilkan 403 ramah: "Halaman ini hanya untuk petugas. Anda masih bisa melihat status kawasan." Tidak ada data sensitif di layar masuk.

### 7.10 Mode demo (`/demo`, P2)
Banner `MODE DEMO` tidak bisa ditutup, `DemoDial` hari 0–20, tabel zona yang ikut berubah, tiga skenario siap pakai: `Awal kemarau (hari 0)`, `Kebakaran September 2023 (hari 15)`, `Hujan pertama (hari 20)`. Semua pesan berlabel `[SIMULASI]`. Tombol `Kembali ke data aktual` selalu terlihat.

---

## 8. Peta dan visualisasi risiko

**Dasar teknis:** Leaflet + OpenStreetMap, GeoJSON poligon zona, tanpa API key. Peta adalah elemen **P1**, tetapi tidak boleh menjadi satu-satunya cara memahami status — daftar kartu di bawahnya adalah padanan teks untuk pembaca layar dan untuk sinyal lemah.

**Poligon zona:**

| Kode | Zona | Atribut isi | Warna dasar |
| --- | --- | --- | --- |
| BRM-01 | Cemoro Lawang & Ngadisari | tepi kaldera, permukiman + wisata | mengikuti kategori |
| BRM-02 | Wonokitri & Penanjakan | lereng, titik pandang | mengikuti kategori |
| BRM-03 | Lautan Pasir & kaldera | pasir, vegetasi jarang | mengikuti kategori |
| BRM-04 | Savana & Jemplang | padang rumput, risiko tertinggi | mengikuti kategori |
| BRM-05 | Ngadas & Poncokusumo | permukiman Tengger, ladang | mengikuti kategori |
| BRM-06 | Ranu Pani & Senduro | hutan pegunungan | mengikuti kategori |

Aturan visual poligon: isi `*-fill` dengan opasitas 0,55 di atas peta agar jalan/label tetap terbaca; garis `2 px` warna `*-solid`; label zona selalu tampil (bukan hanya saat hover) berupa label pil putih 11 px mono dengan kode + nama pendek; poligon terpilih bergaris `3 px` + cincin luar. Titik tengah zona tidak ditampilkan sebagai pin biasa — pin hanya untuk titik panas, laporan, dan sumber air.

**Lapisan P2:**
- `Titik panas & laporan`: FIRMS = belah ketupat `--hotspot` (72 jam terakhir, ukuran dari `brightness`), laporan terverifikasi = segitiga `--report`, laporan baru belum terverifikasi tidak tampil di peta publik (hanya di tampilan petugas, bentuk segitiga berongga).
- `Logistik & angin`: sumber air = lingkaran `--water` + ikon tetes, pos jaga = persegi `--forest`, jalur evakuasi = garis putus `--evac`, panah angin (arah dari) di centroid dengan panjang setara kecepatan.
- Legenda selalu terlihat, maksimal 5 entri, diletakkan di bawah peta (bukan overlay menutupi peta).

**Aksesibilitas peta:** setiap poligon dapat difokuskan dengan keyboard (`tabindex=0`, `role="button"`, `aria-label="Zona Savana & Jemplang, status bahaya, 19 hari tanpa hujan"`); `Enter` membuka panel. Tersedia `Tampilan daftar` yang setara penuh tanpa peta. Peta menyimpan tile terakhir di cache PWA; bila tile gagal, tampilkan latar `paper-3` + poligon (status tetap terbaca).

**Warna hanya untuk risiko:** biru hanya air, ungu hanya evakuasi, hijau/amber/merah hanya status. Hindari ikon api berwarna merah untuk dekorasi agar tidak bersaing dengan status.

---

## 9. State sistem (wajib untuk semua layar data)

| State | Tampilan | Teks contoh |
| --- | --- | --- |
| Memuat | skeleton kartu + peta berbingkai, tanpa spinner penuh layar | "Memuat data zona…" |
| Sukses segar | banner + cap waktu biasa | "Diperbarui 07:00 WIB" |
| Sukses tetapi data lama (2–6 jam) | pita `warn` di bawah banner | "Data 3 jam lalu, pengambilan berikutnya sedang dicoba." |
| Kedaluwarsa (>6 jam) | pita `danger` | "Data tidak dapat diperbarui. Ikuti instruksi resmi TNBTS/BPBD." |
| Gangguan API cuaca | pakai data tersimpan + beri tahu admin | "Sumber cuaca tidak merespons. Menampilkan data terakhir." |
| Luring | `EmptyStateOffline` + status/peta terakhir | "Anda sedang luring. Status terakhir 06:00 WIB." |
| Tanpa zona terdeteksi | pilih manual + sarankan zona terdekat | "Pilih zona kawasan Anda." |
| Laporan dibatasi | tombol nonaktif + hitung mundur | "Terlalu banyak laporan. Coba lagi dalam 7 menit." |
| Laporan terkirim | `TicketSuccess` | "Tiket #LP-0231 dibuat. Petugas terdekat diberi tahu." |
| Tidak berwenang | halaman 403 ramah + tautan kembali ke Pantauan | "Halaman ini hanya untuk petugas." |
| Mode demo | pita tetap `MODE DEMO · [SIMULASI]` | "Data contoh, bukan cuaca hari ini." |

Semua status risiko di layar mana pun harus dapat dijawab dengan pertanyaan: **aktual atau prakiraan, dihitung kapan, disetujui siapa**.

---

## 10. Gerak dan umpan balik

- Transisi warna poligon 420 ms saat kategori berubah — perubahan status terasa, tidak berkedip.
- Sheet: masuk 220 ms dari bawah dengan kurva `ease-out`; latar belakang meredup 40%, tidak ada blur (biaya render di ponsel kelas menengah).
- Denyut titik api baru: 3 denyut 1800 ms, berhenti otomatis.
- Tombol: `active` turun 1 px + `brightness .96`, tanpa efek riak.
- Umpan balik sukses = perubahan isi (nomor tiket muncul), bukan hanya toast.
- Tidak ada animasi pada angka metrik yang bisa disalahartikan sebagai pembacaan baru.

---

## 11. Aksesibilitas (target Lighthouse ≥ 90)

| Area | Aturan konkret |
| --- | --- |
| Kontras | semua teks utama ≥ 4,5:1 (lihat §5.1). `ember` hanya ≥ 24 px atau dekoratif |
| Fokus | cincin 3 px `--focus`, offset 2 px, terlihat di latar terang dan gelap |
| Target sentuh | minimal 44×44 px; aksi verifikasi 48 px dengan jarak 8 px |
| Struktur | satu `<h1>` per layar, heading berurutan, landmark `header/nav/main/footer`, `aria-current` pada navigasi |
| Peta | padanan daftar, `aria-label` per zona, tidak ada informasi hanya-warna |
| Formulir | `<label>` nyata (bukan placeholder), `aria-describedby` untuk bantuan, pesan galat di dekat kolom dan di ringkasan atas, `aria-live="polite"` |
| Status dinamis | perubahan status & hasil verifikasi diumumkan lewat `role="status"` |
| Gerak | hormati `prefers-reduced-motion`; tidak ada carousel otomatis |
| Bahasa | `lang="id"` pada dokumen; istilah asing diberi penjelasan; angka memakai format Indonesia (`.` ribuan, `,` desimal) |
| Cahaya terik | hindari latar putih murni; sediakan "mode terang tinggi" (latar `#FFFFFF`, teks `#0B140F`, garis 2 px) di Pengaturan |
| Uji | keyboard penuh, TalkBack/VoiceOver, zoom 200%, Lighthouse, uji 5–10 pengguna |

---

## 12. Responsif dan anggaran performa

| Breakpoint | Tata letak |
| --- | --- |
| < 480 px | 1 kolom; peta 16:10; FAB; sheet 85vh |
| 480–1023 px | 1 kolom lebar, kartu zona 2 kolom, panel sebagai sheet lebar |
| ≥ 1024 px | nav kiri 240 px, peta + panel kanan sticky, tabel admin penuh |

Anggaran: LCP < 2,5 s (4G), total JS awal < 180 KB gzip, tile peta lambat dimuat (`loading="lazy"`), Chart.js hanya di tab Tren (dynamic import), gambar laporan dikompresi ke maks 1200 px sisi terpanjang, font sistem sebagai fallback instan. Peta & banner dirender dari **hasil tersimpan** (`risk_forecast`), bukan perhitungan ulang di klien (FR-ENG-06).

---

## 13. Copy dan tone of voice

**Prinsip:** kalimat pendek, kata kerja perintah, tanpa istilah teknis di lapisan jawaban. Maksimal 12 kata per baris instruksi. Bahasa Indonesia sederhana; "Anda" untuk warga/wisatawan, "Bapak/Ibu" untuk otoritas di siaran resmi. Hindari "mohon" berulang, hindari "segera" tanpa tindakan.

| Konteks | Tulis | Jangan tulis |
| --- | --- | --- |
| Status | `BAHAYA` + "Jangan membuat api terbuka di zona ini." | "Risiko tinggi terdeteksi" |
| Metrik | "19 hari tanpa hujan (aktual)" | "CDD: 19" |
| Prakiraan | "Prakiraan +48 jam: 21 hari tanpa hujan" | "CDD 48 jam = 21" |
| Tombol | "Lapor asap" | "Submit" |
| Konfirmasi | "Tiket #LP-0231 dibuat. Petugas terdekat diberi tahu." | "Berhasil!" |
| Gangguan | "Sumber cuaca tidak merespons. Menampilkan data terakhir 06:00 WIB." | "Error 500" |
| Otoritas | "Siaga 1 disetujui Ibu Sri (BPBD) pukul 09:14 WIB." | "Status: aktif" |
| Keterbatasan | "Ini alat bantu keputusan, bukan pengganti peringatan resmi." | "Akurasi 95%" |

**Larangan copy:** klaim akurasi yang belum diukur, kata "AI" tanpa penjelasan, menakut-nakuti ("kebakaran pasti terjadi"), dan singkatan yang tidak dijelaskan pada pengunjung.

---

## 14. Notifikasi dan pesan

### 14.1 Struktur pesan (FR-NOT-09)
`Judul status` → `Fakta singkat (alasan)` → `Instruksi kata kerja` → `Kontak darurat` → `Tautan peta`.

Pratinjau **Waspada** (web-push + kanal Telegram):
```
WASPADA — Savana & Jemplang (BRM-04)
Hari ke-11 tanpa hujan. FDI 62/100, angin 19 km/j.
Jangan membakar sampah. Padamkan puntung rokok.
Jangan menyalakan flare atau api terbuka.
Darurat: 112 · Pantau: pyrog.uard/brm04
```
Pratinjau **Siaga 1** (setelah persetujuan Admin):
```
SIAGA 1 — Savana & Jemplang (BRM-04)
Hari ke-19 tanpa hujan. FDI 86/100. Angin 28 km/j.
Dilarang keras membuat api terbuka sampai status dicabut.
Siapkan masker dan ketahui jalur keluar.
Darurat: 112 · Info: pyrog.uard/brm04
```
Pratinjau **Titik api** (bot privat, radius 3 km):
```
Asap terverifikasi 1,8 km arah barat daya.
Regu menuju lokasi. Angin ke barat laut.
Jauhi area savana. Ikuti petugas.
```
Pratinjau **Pencabutan**:
```
SIAGA 1 DICABUT — Savana & Jemplang
FDI turun ke 41/100 setelah hujan.
Tetap jaga kebersihan lahan dan jangan membakar.
```

### 14.2 Aturan kanal dan frekuensi
| Jenis | Kanal | Pemicu | Batas |
| --- | --- | --- | --- |
| Waspada | Web-push + kanal Telegram | otomatis saat kategori naik | 1× per hari per zona kecuali status berubah |
| Siaga 1 | Web-push + kanal + grup petugas | persetujuan Admin tercatat | sekali per kejadian + pencabutan |
| Titik api | Bot privat | verifikasi `Api nyata` | hanya radius 3 km, hanya pengguna berlangganan |
| Tiket | DM/ grup petugas | laporan masuk | per tiket, dengan tombol inline |
| Pencabutan | semua kanal | Admin mencabut | wajib |
Setiap siaran masuk `broadcast_log` (isi, penyetuju, kanal, penerima, waktu). Pengiriman massal lewat antrean dengan jeda (≈30 pesan/detik global, ≈1/detik per chat, ≈20/menit per grup).

### 14.3 Desain kanal Telegram
Kanal publik: pin pesan status terakhir per zona, pesan dengan judul kapital dan tombol `Buka peta`. Bot privat: `/start KODE` menautkan akun, `/zona` memilih zona, `/status` status ringkas, `/berhenti` berhenti berlangganan. Tombol inline tiket: `Menuju lokasi` (URL peta), `Api nyata`, `Bukan api`, `Ganda` (callback). Karena Telegram kurang populer dibanding WhatsApp di Indonesia, **web-push dan kanal publik adalah jalur utama**, dan keterbatasan ini dinyatakan terbuka di halaman Metode.

### 14.4 Izin dan kepercayaan
Permintaan izin notifikasi tidak muncul saat halaman dimuat; pengguna harus menekan `Aktifkan notifikasi` setelah membaca 2 kalimat penjelasan. Jika diblokir, tampilkan cara membuka kembali di pengaturan browser. Web-push iOS hanya setelah PWA dipasang ke layar utama — tampilkan panduan 2 langkah.

---

## 15. Mode demo

Tujuan: memungkinkan juri melihat dinamika sistem tanpa menunggu kemarau dan tanpa mengirim peringatan palsu.

- Sakelar di header admin/demo: `Data aktual` ⇄ `Mode demo`. Default: data aktual.
- `DemoDial` hari 0–20. Setiap perubahan menghitung ulang CDD simulasi dari titik awal 0, mengubah kategori, mewarnai peta, dan memperbarui grafik. Tiga tombol skenario: hari 0 (semua aman), hari 15 (Bahaya di BRM-04, kebakaran September 2023), hari 20 (hujan pertama, status dicabut).
- Data contoh dibekukan dalam basis data (`source = 'demo'`) dan **tidak pernah** memanggil API cuaca atau mengirim ke kanal publik. Semua pesan diberi `[SIMULASI]`.
- Saat demo aktif, header memakai latar `paper-3` bertuliskan `MODE DEMO`; tidak bisa disalahartikan sebagai peringatan nyata.
- Alur yang dapat didemokan: hari 15 → siaga otomatis → setujui → pratinjau siaran → lapor asap → verifikasi petugas → pesan titik api. Total ≤ 3 menit, semua di satu tab.

---

## 16. Handoff teknis: dari data ke layar

### 16.1 Pemetaan model data → komponen
| Tabel | Dipakai komponen | Catatan UI |
| --- | --- | --- |
| `zones` | `StatusBanner`, peta, kartu zona | poligon GeoJSON + titik tengah + `jenis_lahan`, `akses_wisata` masuk pesan & panel |
| `daily_weather` | tab Tren, jam rentan | status `aktual`/`prakiraan` wajib ditampilkan |
| `risk_forecast` | banner, `FdiMeter`, `FactorBars` | satu baris per horizon; UI **hanya membaca** tabel ini |
| `hotspots` | lapisan titik panas | bentuk berbeda per sumber, ukuran = brightness |
| `reports` | `TicketCard`, `TicketTimeline` | koordinat hanya untuk petugas/admin |
| `water_points` | lapisan logistik, `/evakuasi` | biru khusus air |
| `alert_status` | banner, `ApprovalCard`, riwayat | tampilkan penyetuju + waktu |
| `broadcast_log` | admin Log siaran | ekspor CSV |
| `push_subscriptions` | halaman Notifikasi | tidak menampilkan endpoint ke UI |

### 16.2 Kontrak API yang mengikat UI
```http
GET /api/v1/forecast/risk-zone?zone=BRM-04
{
  "zone_id": "BRM-04", "name": "Savana dan Jemplang",
  "consecutive_dry_days": 19,
  "projection": { "h24": 20, "h48": 21, "h72": 22 },
  "fire_danger_index": 86, "risk_category": "BAHAYA",
  "factor_contribution": { "cdd": 54, "humidity": 24, "wind": 8 },
  "peak_risk_window": "12:00 - 15:30 WIB",
  "wind": { "speed_kmh": 28, "from": "Tenggara" },
  "alert_status": "SIAGA_OTOMATIS",
  "data_state": "actual", "computed_at": "2026-08-17T07:00:00+07:00"
}
```
UI wajib menangani field `data_state` (`actual`/`forecast`) dan `computed_at` pada setiap tampilan angka. Endpoint lain: `GET /api/v1/zones`, `GET /api/v1/hotspots`, `GET /api/v1/water-points`, `POST /api/v1/reports`, `PATCH /api/v1/reports/{id}/verify`, `POST /api/v1/alerts/{zone}/approve`, `POST /api/v1/telegram/webhook`. Endpoint prototipe di aplikasi ini memakai awalan `/api/` (lihat README di `src/app/api`).

### 16.3 Rumus yang tampil di UI
```
CDD_now  = jumlah hari berturut-turut dengan hujan < 1 mm, dihitung mundur
CDD_proy = CDD hari ini + hari prakiraan berurutan dengan hujan < 1 mm
           (hujan ≥ 1 mm mengembalikan CDD ke 0)
FDI      = min(100, CDD×2,85 + (100 − RH_min)×0,35 + angin_maks×0,3)
Kategori = CDD ≤ 7 Aman · 8–14 Waspada · >14 Bahaya
Siaga    = Bahaya DAN FDI > 80 → SIAGA_OTOMATIS (menunggu persetujuan Admin)
```
`Mengapa status ini` menampilkan kontribusi terhitung: `CDD 19×2,85 = 54,2`, `(100−RH 31)×0,35 = 24,2`, `angin 28×0,3 = 8,4` — dibulatkan ke bilangan bulat dengan satuan poin. Setiap perubahan bobot/ambang harus tercatat (tanggal + pelaku) dan muncul di halaman Metode.

---

## 17. Cakupan prototipe dan prioritas

| Prioritas | Yang dibangun pada prototipe lomba | Status di aplikasi ini |
| --- | --- | --- |
| P1 | Banner status, peta zona berwarna, panel zona, lapor asap + tiket, verifikasi petugas, persetujuan Siaga, log siaran, pratinjau pesan | Interaktif dengan basis data |
| P2 | Lapisan titik panas/logistik, tab Mengapa & Tren, mode demo, halaman Metode | Ditampilkan sebagai spesimen/terbatas |
| P3 | WhatsApp/SMS resmi, NDVI, grid 1 km, bahasa Jawa/Tengger, super admin penuh | Dinyatakan sebagai rencana |

Aturan lingkup: **jangan menambah fitur di luar P1/P2 sebelum alur P1 terbukti ujung-ke-ujung.** Bila waktu terbatas, pertahankan fase riset data → mesin risiko → validasi → peta.

---

## 18. Rencana pengujian desain

| Uji | Cara | Kriteria lulus |
| --- | --- | --- |
| Pemahaman status (10 detik) | 5–10 responden melihat banner tanpa panduan | ≥ 80% menyebut status + satu larangan dengan benar |
| Alur lapor | Tugas: laporkan asap di zona Savana | selesai < 60 detik tanpa bantuan |
| Verifikasi petugas | Tugas: verifikasi tiket sebagai api nyata | selesai < 45 detik, satu tangan |
| Persetujuan Siaga | Admin menyetujui + mencabut | jejak audit muncul, pratinjau pesan benar |
| Aksesibilitas | Lighthouse, tab keyboard, TalkBack/VoiceOver | skor ≥ 90, tanpa jebakan fokus |
| Perangkat | Android 360 px, iPhone SE, iPad, desktop 1440 px | tanpa scroll horizontal, FAB tidak menutupi konten |
| Sinyal lemah | throttle Slow 4G + offline | status terakhir & nomor darurat tetap terbaca |
| Data kedaluwarsa | simulasi gagal API | label waktu tampil, admin diberi notifikasi |
| Warna | tangkapan layar grayscale | status tetap terbedakan |

Metrik desain yang dilaporkan jujur: waktu memahami status (<10 s), waktu dari verifikasi ke pesan (<2 menit), muat halaman (<3 s pada 4G). **Jangan mencantumkan akurasi prediksi yang belum diukur lewat backtesting.**

---

## 19. Backlog desain (setelah lomba)

P2 sisa: grafik jam rentan interaktif, skor kepercayaan pelapor di UI, ekspor CSV lanjutan, animasi angin. P3: WhatsApp/SMS resmi, NDVI Sentinel-2 (peta kekeringan vegetasi), grid 1 km, bahasa Jawa/Tengger & Inggris untuk wisatawan mancanegara, integrasi data resmi TNBTS/BPBD, aplikasi petugas khusus, mode gelap untuk pemakaian malam, kartu estimasi dampak lingkungan (luas area kritis, beban bahan bakar, faktor emisi, dilabeli estimasi).

---

## 20. Risiko desain dan pertanyaan terbuka

| Risiko | Dampak ke desain | Mitigasi desain |
| --- | --- | --- |
| Prediksi meleset di savana | kepercayaan turun | label aktual/prakiraan, halaman Metode jujur, backtesting ditampilkan |
| Data cuaca kasar di pegunungan | angka terlihat tidak masuk akal | tampilkan titik sampel zona + keterbatasan resolusi |
| Alarm palsu massal | kelelahan notifikasi | persetujuan Admin, batas 1×/hari/zona, copy yang tidak berlebihan |
| Telegram kurang dipakai | jangkauan terbatas | web-push + kanal publik utama, panduan pemasangan PWA |
| Laporan palsu | beban verifikator | skor kepercayaan, rate limit, status transparan |
| Tanpa kemitraan resmi | status Siaga terlihat tidak sah | label prototipe, alur persetujuan, rencana kerja sama TNBTS/BPBD |
| iOS tanpa PWA | push tidak sampai | instruksi "Tambah ke layar utama", Telegram sebagai cadangan |

**Pertanyaan terbuka:** (1) kontak validasi ambang di TNBTS/BPBD; (2) ketersediaan data riwayat kebakaran resmi; (3) server demo HTTPS atau tunnel; (4) tenggat & format penjurian; (5) izin penggunaan layanan data pihak ketiga (Open-Meteo, FIRMS). Jawaban ini memengaruhi isi halaman Metode, label "prototipe", dan rencana uji pengguna — bukan fondasi desain.

**Catatan validasi:** seluruh angka contoh, koordinat zona, ambang awal, nomor kontak, dan rasio kontras warna di dokumen ini adalah ilustrasi draf sampai divalidasi dengan data lapangan dan sumber resmi.

---

## 21. Glosarium

| Istilah | Arti |
| --- | --- |
| CDD | *Consecutive Dry Days* — jumlah hari berturut-turut dengan hujan < 1 mm |
| FDI | *Fire Danger Index* 0–100 versi PyroGuard |
| KBDI | *Keetch-Byram Drought Index* — pembanding saat backtesting |
| FWI | *Canadian Fire Weather Index* — metode baku bahaya kebakaran |
| FIRMS | Sistem NASA untuk titik panas satelit |
| PWA | *Progressive Web App* — situs yang bisa dipasang seperti aplikasi |
| VAPID | kunci untuk mengautentikasi web-push |
| RBAC | hak akses berbasis peran |
| Geofencing | pengiriman pesan berdasarkan jarak dari lokasi |
| TNBTS | Taman Nasional Bromo Tengger Semeru |

---

### Riwayat dokumen
| Versi | Tanggal | Perubahan |
| --- | --- | --- |
| 1.0 | 2026 | Turunan pertama PRD v1.0: token, komponen, 10 layar, kontrak data, aksesibilitas, copy, mode demo |
