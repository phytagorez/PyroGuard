@extends('layouts.publik')

@section('judul', 'Metode & Keterbatasan Formula PyroGuard')

@section('konten')
<div class="max-w-4xl mx-auto space-y-8 pb-16">
    {{-- HEADER --}}
    <div>
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ url('/') }}" class="text-xs font-mono text-ink-muted hover:text-ink transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Kembali ke Pantauan</span>
            </a>
            <span class="text-xs text-ink-muted font-mono">•</span>
            <span class="text-xs font-mono text-forest font-bold uppercase tracking-wider">Transparansi Ilmiah</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-display font-bold text-ink tracking-tight">
            Metode Perhitungan & Keterbatasan Sistem
        </h1>
        <p class="text-base text-ink-muted mt-2 leading-relaxed">
            PyroGuard dibangun atas dasar prinsip sains terbuka dan transparansi data. Dokumen ini merinci rumus matematis, ambang batas bahaya, hasil evaluasi historis, serta keterbatasan teknis sistem.
        </p>
    </div>

    {{-- KOTAK PERNYATAAN INTEGRITAS & BATAS OTORITAS (§13 design.md) --}}
    <div class="p-5 bg-paper-2 border-2 border-line rounded-2xl shadow-card space-y-3">
        <div class="flex items-center gap-2 text-forest font-display font-bold text-base">
            <span class="text-xl">⚖️</span>
            <span>PyroGuard adalah Alat Bantu Keputusan (Decision Support Tool)</span>
        </div>
        <p class="text-sm text-ink leading-relaxed">
            Informasi pada platform ini bertujuan untuk membantu kesiapsiagaan pengelola wisata, relawan, dan masyarakat dalam mendeteksi ancaman kebakaran semak dan savana sedini mungkin. <strong>Sistem ini bukan pengganti otoritas resmi</strong>. Dalam keadaan darurat bencana, seluruh tindakan penanganan dan evakuasi tetap tunduk pada instruksi resmi Balai Besar TNBTS, BPBD Provinsi Jawa Timur, dan BMKG.
        </p>
    </div>

    {{-- 1. APA ITU CDD DAN FDI --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            1. Definisi Indikator Utama
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-5 bg-paper-2 border border-line rounded-2xl shadow-card space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider">Metrik Kekeringan</span>
                    <span class="font-mono text-xs text-ink-muted">WMO Standard</span>
                </div>
                <h3 class="font-display font-bold text-lg text-ink">CDD (Consecutive Dry Days)</h3>
                <p class="text-sm text-ink-muted leading-relaxed">
                    Jumlah hari berturut-turut tanpa hujan efektif (didefinisikan sebagai curah hujan harian <strong>&lt; 1,0 mm</strong>). Di kawasan savana Bromo, vegetasi rumput malela mengering drastis setelah 8–10 hari berturut-turut tanpa presipitasi.
                </p>
            </div>

            <div class="p-5 bg-paper-2 border border-line rounded-2xl shadow-card space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs font-bold text-danger uppercase tracking-wider">Indeks Bahaya Kebakaran</span>
                    <span class="font-mono text-xs text-ink-muted">Skala 0 – 100</span>
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
            Mesin risiko PyroGuard menghitung FDI menggunakan model regresi linier terkalibrasi khusus iklim mikro Bromo Tengger Semeru:
        </p>

        <div class="p-5 bg-paper-2 border border-line rounded-2xl space-y-4 font-mono text-xs sm:text-sm">
            <div class="p-3 bg-paper rounded-xl border border-line text-ink overflow-x-auto">
                <span class="text-ink-muted block text-xs mb-1 font-sans font-semibold">Formula Komposit FDI:</span>
                <strong class="text-base text-forest">FDI = min(100, round(Poin_CDD + Poin_Kelembapan + Poin_Angin))</strong>
            </div>

            <div class="space-y-3 pt-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-line">
                    <span class="text-ink font-semibold">Poin Akumulasi Kekeringan (CDD):</span>
                    <code class="text-forest font-bold">Poin_CDD = round(CDD × 2.85)</code>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-line">
                    <span class="text-ink font-semibold">Poin Defisit Kelembapan Udara:</span>
                    <code class="text-forest font-bold">Poin_RH = round((100 - RH_min) × 0.35)</code>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-ink font-semibold">Poin Pengaruh Tiupan Angin:</span>
                    <code class="text-forest font-bold">Poin_Angin = round(Angin_maks × 0.30)</code>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. TABEL AMBANG BATAS RISIKO --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            3. Tabel Klasifikasi Ambang Batas
        </h2>
        <div class="overflow-x-auto rounded-2xl border border-line bg-paper-2 shadow-card">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-paper border-b border-line font-mono text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="p-3 sm:p-4">Kategori</th>
                        <th class="p-3 sm:p-4">Ambang CDD</th>
                        <th class="p-3 sm:p-4">Ambang FDI</th>
                        <th class="p-3 sm:p-4">Instruksi Lapangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono">
                    <tr class="hover:bg-paper-3 transition">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="AMAN" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4">≤ 7 hari</td>
                        <td class="p-3 sm:p-4">0 – 40</td>
                        <td class="p-3 sm:p-4 font-sans text-ink">Aktivitas normal. Tetap waspada dan jangan buang puntung rokok sembarangan.</td>
                    </tr>
                    <tr class="hover:bg-paper-3 transition">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="WASPADA" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4">8 – 14 hari</td>
                        <td class="p-3 sm:p-4">41 – 79</td>
                        <td class="p-3 sm:p-4 font-sans text-ink">Larangan membakar semak. Periksa kesiapan tandon & embung air pemadam.</td>
                    </tr>
                    <tr class="hover:bg-paper-3 transition">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="BAHAYA" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4">&gt; 14 hari</td>
                        <td class="p-3 sm:p-4">≥ 80</td>
                        <td class="p-3 sm:p-4 font-sans text-danger font-semibold">Dilarang membuat api terbuka, sampah, atau flare. Siaga regu pemadam TNBTS.</td>
                    </tr>
                    <tr class="hover:bg-paper-3 transition bg-danger/5">
                        <td class="p-3 sm:p-4">
                            <x-komponen.lencana-risiko status="SIAGA_1" varian="solid" ukuran="sm" />
                        </td>
                        <td class="p-3 sm:p-4">&gt; 14 hari</td>
                        <td class="p-3 sm:p-4">≥ 80 (Disetujui)</td>
                        <td class="p-3 sm:p-4 font-sans text-danger font-bold">Status darurat aktif disetujui BPBD. Pembatasan akses wisata ke area rawan.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- 4. HASIL BACKTESTING APA ADANYA (§7.6 design.md) --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            4. Hasil Uji Historis (Backtesting) Apa Adanya
        </h2>
        <p class="text-sm text-ink-muted leading-relaxed">
            Model divalidasi terhadap 23 catatan kejadian kebakaran lahan dan vegetasi di wilayah kerja Balai Besar TNBTS periode 2019 hingga 2024 (termasuk insiden kebakaran besar Savana Watangan September 2023):
        </p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center font-mono">
            <div class="p-4 bg-paper-2 border border-line rounded-xl">
                <span class="text-[11px] text-ink-muted block uppercase">Tingkat Deteksi (Hit Rate)</span>
                <span class="text-2xl sm:text-3xl font-display font-bold text-forest mt-1 block">
                    {{ $metrik['hit_rate'] }}%
                </span>
                <span class="text-[10px] text-ink-muted">21 dari 23 insiden</span>
            </div>

            <div class="p-4 bg-paper-2 border border-line rounded-xl">
                <span class="text-[11px] text-ink-muted block uppercase">Alarm Palsu (False Alarm)</span>
                <span class="text-2xl sm:text-3xl font-display font-bold text-warn mt-1 block">
                    {{ $metrik['false_alarm_rate'] }}%
                </span>
                <span class="text-[10px] text-ink-muted">Hari waspada tanpa api</span>
            </div>

            <div class="p-4 bg-paper-2 border border-line rounded-xl">
                <span class="text-[11px] text-ink-muted block uppercase">Insiden Diuji</span>
                <span class="text-2xl sm:text-3xl font-display font-bold text-ink mt-1 block">
                    {{ $metrik['jumlah_kejadian'] }}
                </span>
                <span class="text-[10px] text-ink-muted">Rekam jejak TNBTS</span>
            </div>

            <div class="p-4 bg-paper-2 border border-line rounded-xl">
                <span class="text-[11px] text-ink-muted block uppercase">Waktu Peringatan Awal</span>
                <span class="text-2xl sm:text-3xl font-display font-bold text-moss mt-1 block">
                    48 Jam
                </span>
                <span class="text-[10px] text-ink-muted">Sebelum eskalasi api</span>
            </div>
        </div>
    </section>

    {{-- 5. KETERBATASAN TEKNIS SISTEM (§7.6 design.md) --}}
    <section class="space-y-4">
        <h2 class="text-xl sm:text-2xl font-display font-bold text-ink">
            5. Keterbatasan Teknis Model
        </h2>
        <div class="p-5 bg-paper-2 border border-line rounded-2xl space-y-3 text-sm text-ink-muted leading-relaxed">
            <ul class="list-disc pl-5 space-y-2">
                <li>
                    <strong class="text-ink">Resolusi Spasial Cuaca:</strong> Data numerik Open-Meteo menggunakan resolusi grid ~11 km. Pada topografi pegunungan terjal Bromo dengan variasi elevasi 2.000–2.700 mdpl, mikroklimat lokal di dasar kaldera dapat memiliki fluktuasi suhu dan angin yang lebih ekstrem dibandingkan rata-rata grid.
                </li>
                <li>
                    <strong class="text-ink">Ketiadaan Sensor Kadar Air Biomassa (NDVI):</strong> Versi prototipe saat ini belum memasukkan indeks kehijauan vegetasi satelit Sentinel-2 secara real-time karena keterbatasan latensi pemrosesan awan, melainkan mengandalkan CDD sebagai representasi kadar air bahan bakar rumput.
                </li>
                <li>
                    <strong class="text-ink">Pemicu Non-Alam (Aktivitas Manusia):</strong> FDI memprediksi kerentanan lahan untuk terbakar dan merambat. Model tidak dapat memprediksi kelalaian manusia seketika (seperti penggunaan flare, puntung rokok sembarangan, atau pembakaran ladang tanpa pengawasan).
                </li>
            </ul>
        </div>
    </section>

    {{-- 6. KONTAK DAN SUMBER RESMI ALTERNATIF --}}
    <section class="p-5 bg-forest text-white rounded-2xl shadow-card space-y-3">
        <h3 class="font-display font-bold text-lg">Kanal Informasi Darurat Resmi Kawasan Bromo</h3>
        <p class="text-xs sm:text-sm text-white/90 leading-relaxed">
            Bila Anda melihat tanda-tanda kebakaran atau membutuhkan konfirmasi keadaan darurat, hubungi instansi resmi terkait:
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs font-mono">
            <div class="p-3 bg-white/10 rounded-xl">
                <span class="block opacity-80 text-[10px]">PANGGILAN DARURAT</span>
                <strong class="text-base text-amber-300">Call Center 112</strong>
                <span class="block opacity-80 mt-1">Bebas Pulsa 24 Jam</span>
            </div>
            <div class="p-3 bg-white/10 rounded-xl">
                <span class="block opacity-80 text-[10px]">BALAI BESAR TNBTS</span>
                <strong class="text-base text-white">0341-491828</strong>
                <span class="block opacity-80 mt-1">Kantor Pengelola Kawasan</span>
            </div>
            <div class="p-3 bg-white/10 rounded-xl">
                <span class="block opacity-80 text-[10px]">BMKG JUANDA</span>
                <strong class="text-base text-white">bmkg.go.id</strong>
                <span class="block opacity-80 mt-1">Prakiraan Cuaca Resmi</span>
            </div>
        </div>
    </section>
</div>
@endsection
