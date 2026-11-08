@extends('layouts.publik')

@section('judul', 'Metode & Keterbatasan Formula PyroGuard')

@section('konten')
<div class="max-w-4xl mx-auto space-y-8 pb-16">
    {{-- HEADER --}}
    <div>
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ url('/') }}" class="text-xs font-sans text-ink-muted hover:text-ink transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Kembali ke Pantauan</span>
            </a>
            <span class="text-xs text-ink-muted font-sans">•</span>
            <span class="text-xs font-sans text-forest font-bold tracking-normal">Transparansi Ilmiah</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-display font-bold text-ink tracking-tight">
            Metode Perhitungan & Dataset
        </h1>
        <p class="text-base text-ink-muted mt-2 leading-relaxed">
            PyroGuard dibangun atas dasar prinsip sains terbuka dan transparansi data. Halaman ini menjelaskan sumber geospasial, analisis historis GEE, rumus prototipe, dan batas penggunaan datanya.
        </p>
    </div>

    {{-- KOTAK PERNYATAAN INTEGRITAS & BATAS OTORITAS (§13 design.md) --}}
    <div class="p-5 bg-paper-2 border border-line rounded-lg shadow-none space-y-3">
        <div class="flex items-center gap-2 text-forest font-display font-bold text-base">
            <span class="text-xl"></span>
            <span>PyroGuard adalah Alat Bantu Keputusan (Decision Support Tool)</span>
        </div>
        <p class="text-sm text-ink leading-relaxed">
            Informasi pada platform ini bertujuan untuk membantu kesiapsiagaan pengelola wisata, relawan, dan masyarakat dalam mendeteksi ancaman kebakaran semak dan savana sedini mungkin. <strong>Sistem ini bukan pengganti otoritas resmi</strong>. Dalam keadaan darurat bencana, seluruh tindakan penanganan dan evakuasi tetap tunduk pada instruksi resmi Balai Besar TNBTS, BPBD Provinsi Jawa Timur, dan BMKG.
        </p>
    </div>

    <section id="dataset" class="dataset-reference space-y-5">
        <div class="section-heading"><div><h2>Sumber & status dataset</h2><p>Bedakan poligon wilayah, lokasi kejadian, dan hasil analisis historis.</p></div></div>
        <div class="overflow-x-auto border border-line rounded-lg"><table class="text-sm text-left"><thead><tr><th class="p-4">Lapisan</th><th class="p-4">Asal / periode</th><th class="p-4">Status</th></tr></thead><tbody class="divide-y divide-line">
            <tr><td class="p-4">Batas administrasi sekitar Bromo</td><td class="p-4">Bromo.zip · atribut Lapak GIS - 2024 · 10 fitur</td><td class="p-4">Tersedia. Belum diverifikasi terhadap penerbit resmi; bukan batas TNBTS.</td></tr>
            <tr><td class="p-4">Poligon wilayah analisis</td><td class="p-4">GEE: projects/pyroguard-513617/assets/Wilayah</td><td class="p-4">Ditampilkan setelah GeoJSON aset diimpor oleh pengelola.</td></tr>
            <tr><td class="p-4">Titik referensi kebakaran</td><td class="p-4">Bantengan · 112.9530, -7.9425 · tanggal parameter 29 Agustus 2023</td><td class="p-4">Parameter pengguna, belum diverifikasi independen. Point ini bukan poligon area terbakar.</td></tr>
            <tr><td class="p-4">Enam zona operasional</td><td class="p-4">Cemoro Lawang / Ngadisari; Wonokitri / Penanjakan; Lautan Pasir / kaldera; Savana / Jemplang; Ngadas / Poncokusumo; Ranu Pani / Senduro</td><td class="p-4">Titik referensi. Batas enam zona tidak boleh disimpulkan dari batas kabupaten.</td></tr>
            <tr><td class="p-4">Cuaca, FDI, titik panas, logistik</td><td class="p-4">Catatan basis data aplikasi</td><td class="p-4">Asal observasi belum terverifikasi. Nilai contoh / tersimpan bukan kondisi saat ini.</td></tr>
        </tbody></table></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="border border-line rounded-lg p-5 space-y-4"><h3 class="text-base font-medium">Analisis lingkungan GEE</h3><p class="text-sm text-ink-muted">Sentinel-2: NDVI, NDMI, NBR sebelum dan sesudah; CHIRPS: hujan harian dan tujuh hari; MODIS: suhu permukaan; VIIRS: jumlah deteksi piksel api.</p><p class="text-sm text-ink-muted">Tanggal akhir GEE bersifat eksklusif: sebelum 30 Juli–28 Agustus 2023, sesudah 30 Agustus–28 September 2023. VIIRS mencakup 27–31 Agustus. Deteksi piksel berulang pada beberapa hari bukan jumlah kejadian atau luas terbakar.</p><p class="text-sm text-ink-muted">Rata-rata mencakup seluruh aset Wilayah, belum dipecah ke enam zona. Sampling 100 m tidak meningkatkan resolusi CHIRPS atau MODIS. Sel tanpa observasi ditampilkan sebagai kosong.</p><a class="outline-button" href="{{ asset('data/pyroguard-gee.js') }}" download>Unduh skrip ekspor GEE ↓</a></div><div class="border border-line rounded-lg p-5 space-y-4"><h3 class="text-base font-medium">Paket QGIS yang bisa dipindahkan</h3><p class="text-sm text-ink-muted">Paket memuat proyek QGIS, Bromo.shp, .shx, .dbf, dan .prj. Path lokal diperbaiki menjadi relatif. Layer BatasWilayah, Batas Kabupaten Jawa Timur, dan JawaTimur tidak tersedia pada lampiran dan tidak direkonstruksi.</p><p class="text-sm text-ink-muted">Peta web memakai geometri MultiPolygon WGS 84 dari file lampiran, termasuk ring bagian dalam. Batas asli dipertahankan; enam poligon kotak prototipe tidak digunakan di peta baru.</p><div class="flex flex-wrap gap-3"><a class="outline-button" href="{{ asset('data/bromo-qgis-portabel.zip') }}" download>Unduh QGIS ↓</a><a class="outline-button" href="{{ route('api.dataset.batas') }}" download="bromo-administrasi.geojson">Unduh GeoJSON ↓</a></div><a class="block text-sm underline" href="{{ route('api.dataset.index') }}">Lihat metadata API ↗</a></div></div>
    </section>

    {{-- 1. APA ITU CDD DAN FDI --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            1. Definisi Indikator Utama
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-5 bg-paper-2 border border-line rounded-md shadow-none space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-sans text-xs font-bold text-forest tracking-normal">Metrik Kekeringan</span>
                    <span class="font-sans text-xs text-ink-muted">Definisi pada prototipe</span>
                </div>
                <h3 class="font-display font-bold text-lg text-ink">CDD (Consecutive Dry Days)</h3>
                <p class="text-sm text-ink-muted leading-relaxed">
                    Jumlah hari berturut-turut tanpa hujan efektif (didefinisikan sebagai curah hujan harian <strong>&lt; 1,0 mm</strong>). Di kawasan savana Bromo, vegetasi rumput malela mengering drastis setelah 8–10 hari berturut-turut tanpa presipitasi.
                </p>
            </div>

            <div class="p-5 bg-paper-2 border border-line rounded-md shadow-none space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-sans text-xs font-bold text-danger tracking-normal">Indeks Bahaya Kebakaran</span>
                    <span class="font-sans text-xs text-ink-muted">Skala 0 – 100</span>
                </div>
                <h3 class="font-display font-bold text-lg text-ink">FDI (Fire Danger Index)</h3>
                <p class="text-sm text-ink-muted leading-relaxed">
                    Skor komposit yang menggabungkan faktor akumulasi kekeringan bahan bakar rumput kering (CDD), defisit kelembapan udara relatif harian (RH minimum), dan tiupan angin perambat api.
                </p>
            </div>
        </div>
    </section>

    {{-- 2. FORMULA MATEMATIS DENGAN NOTASI JELAS --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            2. Rumus Matematis & Bobot Kalibrasi
        </h2>
        <p class="text-sm text-ink-muted leading-relaxed">
            Mesin risiko saat ini memakai rumus berbobot untuk prototipe. Bobot berikut belum disertai bukti kalibrasi atau validasi ilmiah untuk Bromo:
        </p>

        <div class="p-5 bg-paper-2 border border-line rounded-lg space-y-4 font-sans text-xs sm:text-sm">
            <div class="p-3 bg-paper rounded-md border border-line text-ink overflow-x-auto">
                <span class="text-ink-muted block text-xs mb-1 font-sans font-semibold">Formula Komposit FDI:</span>
                <strong class="text-base text-forest">FDI = min(100, round(Poin_CDD + Poin_Kelembapan + Poin_Angin))</strong>
            </div>

            <div class="space-y-3 pt-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-line">
                    <span class="text-ink font-semibold">Poin Akumulasi Kekeringan (CDD):</span>
                    <code class="text-forest font-bold tabular-nums">Poin_CDD = round(CDD × 2.85)</code>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-line">
                    <span class="text-ink font-semibold">Poin Defisit Kelembapan Udara:</span>
                    <code class="text-forest font-bold tabular-nums">Poin_RH = round((100 - RH_min) × 0.35)</code>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-ink font-semibold">Poin Pengaruh Tiupan Angin:</span>
                    <code class="text-forest font-bold tabular-nums">Poin_Angin = round(Angin_maks × 0.30)</code>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. TABEL AMBANG BATAS RISIKO --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            3. Tabel Klasifikasi Ambang Batas
        </h2>
        <div class="overflow-x-auto rounded-lg border border-line bg-paper-2 shadow-none">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-paper border-b border-line font-sans text-xs text-ink-muted">
                    <tr>
                        <th class="p-3 sm:p-4">Kategori</th>
                        <th class="p-3 sm:p-4">Ambang CDD</th>
                        <th class="p-3 sm:p-4">Ambang FDI</th>
                        <th class="p-3 sm:p-4">Instruksi Lapangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-sans">
                    <tr class="hover:bg-paper-3 transition">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="AMAN" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4 tabular-nums">≤ 7 hari</td>
                        <td class="p-3 sm:p-4 tabular-nums">0 – 40</td>
                        <td class="p-3 sm:p-4 font-sans text-ink">Aktivitas normal. Tetap waspada dan jangan buang puntung rokok sembarangan.</td>
                    </tr>
                    <tr class="hover:bg-paper-3 transition">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="WASPADA" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4 tabular-nums">8 – 14 hari</td>
                        <td class="p-3 sm:p-4 tabular-nums">41 – 79</td>
                        <td class="p-3 sm:p-4 font-sans text-ink">Larangan membakar semak. Periksa kesiapan tandon & embung air pemadam.</td>
                    </tr>
                    <tr class="hover:bg-paper-3 transition">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="BAHAYA" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4 tabular-nums">&gt; 14 hari</td>
                        <td class="p-3 sm:p-4 tabular-nums">≥ 80</td>
                        <td class="p-3 sm:p-4 font-sans text-danger font-semibold">Dilarang membuat api terbuka, sampah, atau flare. Siaga regu pemadam TNBTS.</td>
                    </tr>
                    <tr class="hover:bg-paper-3 transition bg-danger/5">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="SIAGA_1" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4 tabular-nums">&gt; 14 hari</td>
                        <td class="p-3 sm:p-4 tabular-nums">≥ 80 (Disetujui)</td>
                        <td class="p-3 sm:p-4 font-sans text-danger font-bold">Status darurat aktif disetujui BPBD. Pembatasan akses wisata ke area rawan.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-xl">Validasi model</h2>
        <div class="border border-line rounded-lg p-5 space-y-3"><p class="text-sm">Hasil evaluasi belum tersedia. Tidak ada berkas evaluasi terverifikasi untuk menghitung tingkat deteksi, alarm palsu, jumlah insiden uji, atau waktu peringatan awal.</p><p class="text-sm text-ink-muted">Angka akurasi yang sebelumnya ditulis tetap di antarmuka dihapus. Dataset historis GEE dapat menjadi bahan analisis lanjutan; satu kejadian tidak cukup untuk membuktikan akurasi model.</p></div>
    </section>

    {{-- 5. KETERBATASAN TEKNIS SISTEM (§7.6 design.md) --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            5. Keterbatasan Teknis Model
        </h2>
        <div class="p-5 bg-paper-2 border border-line rounded-lg space-y-3 text-sm text-ink-muted leading-relaxed">
            <ul class="list-disc pl-5 space-y-2">
                <li>
                    <strong class="text-ink">Resolusi Spasial Cuaca:</strong> Resolusi cuaca Open-Meteo bergantung pada model yang dipakai; data grid tidak mewakili pengukuran sensor di setiap titik. Pada topografi pegunungan terjal Bromo dengan variasi elevasi 2.000–2.700 mdpl, mikroklimat lokal di dasar kaldera dapat memiliki fluktuasi suhu dan angin yang lebih ekstrem dibandingkan rata-rata grid.
                </li>
                <li>
                    <strong class="text-ink">Ketiadaan Sensor Kadar Air Biomassa (NDVI):</strong> CSV historis GEE memuat NDVI, NDMI, dan NBR Sentinel-2. Nilai ini belum menjadi input mesin FDI dan bukan pengukuran sensor kadar air di lapangan.
                </li>
                <li>
                    <strong class="text-ink">Pemicu Non-Alam (Aktivitas Manusia):</strong> FDI memprediksi kerentanan lahan untuk terbakar dan merambat. Model tidak dapat memprediksi kelalaian manusia seketika (seperti penggunaan flare, puntung rokok sembarangan, atau pembakaran ladang tanpa pengawasan).
                </li>
            </ul>
        </div>
    </section>

    {{-- 6. KONTAK DAN SUMBER RESMI ALTERNATIF --}}
    <section class="p-5 bg-forest text-white rounded-lg shadow-none space-y-3">
        <h3 class="font-display font-bold text-lg">Kanal Informasi Darurat Resmi Kawasan Bromo</h3>
        <p class="text-xs sm:text-sm text-white/90 leading-relaxed">
            Bila Anda melihat tanda-tanda kebakaran atau membutuhkan konfirmasi keadaan darurat, hubungi instansi resmi terkait:
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs font-sans">
            <div class="p-3 bg-white/10 rounded-md border border-white/10">
                <span class="block opacity-80 text-xs">PANGGILAN DARURAT</span>
                <strong class="text-base text-warn-fill tabular-nums">Call Center 112</strong>
                <span class="block opacity-80 mt-1">Bebas Pulsa 24 Jam</span>
            </div>
            <div class="p-3 bg-white/10 rounded-md border border-white/10">
                <span class="block opacity-80 text-xs">BALAI BESAR TNBTS</span>
                <strong class="text-base text-white tabular-nums">0341-491828</strong>
                <span class="block opacity-80 mt-1">Kantor Pengelola Kawasan</span>
            </div>
            <div class="p-3 bg-white/10 rounded-md border border-white/10">
                <span class="block opacity-80 text-xs">BMKG JUANDA</span>
                <strong class="text-base text-white">bmkg.go.id</strong>
                <span class="block opacity-80 mt-1">Prakiraan Cuaca Resmi</span>
            </div>
        </div>
    </section>
</div>
@endsection
