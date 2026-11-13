@extends('layouts.publik')

@section('judul', 'Lapor Asap & Titik Api Bromo')

@section('konten')
@php
    $suksesTiket = session('tiket_berhasil');
@endphp

<div
    x-data="laporAsap({{ Js::from(['sukses' => (bool) $suksesTiket, 'tiket' => $suksesTiket, 'zonas' => $zonas, 'lat' => $zonaPilihan?->titik_tengah_lat ?? -7.965, 'lng' => $zonaPilihan?->titik_tengah_lng ?? 112.968, 'lokasiNama' => $zonaPilihan?->nama ?? 'Menentukan desa…', 'zonaId' => $zonaPilihan?->id, 'zonaKode' => $zonaPilihan?->kode ?? '', 'statusUrl' => $statusUrl ?? null, 'sisaKuota' => $batasLaju['sisa_kuota'] ?? 3, 'terkunci' => (bool) ($batasLaju['terkunci'] ?? false), 'detikTunggu' => $batasLaju['detik_tunggu'] ?? 0]) }})"
    class="report-workspace space-y-6"
>
    {{-- TAMPILAN 1: TICKET SUCCESS (§6.8 & §7.3) --}}
    <template x-if="sukses">
        <div class="space-y-6 animate-fade-in">
            {{-- KARTU TIKET SUKSES --}}
            <div class="p-6 sm:p-8 bg-paper-2 border-2 border-forest/30 rounded-lg shadow-sheet text-center space-y-5">


                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-sans font-bold bg-warn-fill text-[#5C3D06] border border-warn/40 mb-2">
                        STATUS: BARU
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-sans font-bold text-ink tracking-tight tabular-nums" x-text="tiket?.nomor_tiket || '—'"></h1>
                    <p class="text-base font-medium text-ink mt-1">
                        Laporan telah masuk ke antrean verifikasi.
                    </p>
                    <p class="text-xs text-ink-muted" x-show="tiket?.petugas_diberi_tahu">
                        Petugas sudah diberi tahu lewat Telegram untuk memeriksa lokasi.
                    </p>
                    <p class="text-xs text-ink-muted" x-show="!tiket?.petugas_diberi_tahu">
                        Laporanmu tersimpan di antrean petugas untuk diperiksa.
                    </p>
                </div>

                {{-- Detail Ringkas Laporan --}}
                <div class="p-4 bg-paper rounded-md border border-line text-left text-xs font-sans space-y-2">
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Desa:</span>
                        <strong class="text-ink text-right" x-text="(tiket?.zona?.data_analisis?.Label || tiket?.zona?.nama || lokasiNama) + (tiket?.zona?.data_analisis?.Kelas ? ' · Risiko ' + tiket.zona.data_analisis.Kelas.toLowerCase() : '')"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Koordinat:</span>
                        <span class="text-ink tabular-nums" x-text="(tiket?.latitude || lat) + ', ' + (tiket?.longitude || lng)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Jenis Temuan:</span>
                        <span class="font-bold text-ember" x-text="labelJenis(tiket?.jenis_temuan || jenisTemuan)"></span>
                    </div>
                </div>

                {{-- Aksi Tiket --}}
                <div class="space-y-3 pt-2">
                    <a x-show="statusUrl" x-cloak :href="statusUrl" class="w-full h-12 bg-ember hover:brightness-105 text-white rounded-md font-display font-semibold text-sm flex items-center justify-center">Pantau status laporan</a>
                    <button x-show="statusUrl" x-cloak type="button" @click="salinTautan()" class="w-full h-12 bg-paper hover:bg-paper-2 text-ink border border-line rounded-md font-display font-semibold text-sm">Salin tautan status</button>
                    <button
                        type="button"
                        @click="salinNomorTiket(tiket?.nomor_tiket || '—')"
                        class="w-full h-12 bg-forest hover:bg-moss active:scale-[0.98] text-white rounded-md font-display font-semibold text-sm transition duration-fast flex items-center justify-center gap-2 shadow-none"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span>Simpan nomor tiket</span>
                    </button>

                    <button
                        type="button"
                        @click="resetForm()"
                        :disabled="detikLaporLagi > 0"
                        :class="detikLaporLagi > 0 ? 'bg-paper-3 text-ink-muted cursor-not-allowed border border-line' : 'bg-paper hover:bg-paper-2 text-ink border border-line cursor-pointer active:scale-[0.98]'"
                        class="w-full h-12 rounded-md font-display font-semibold text-sm transition duration-fast flex items-center justify-center gap-2"
                    >
                        <template x-if="detikLaporLagi > 0">
                            <span>Lapor lagi (<span x-text="detikLaporLagi"></span>s)</span>
                        </template>
                        <template x-if="detikLaporLagi <= 0">
                            <span>Lapor asap lainnya</span>
                        </template>
                    </button>

                    <p class="text-xs font-sans text-ink pt-1"><strong class="text-danger">Api membesar?</strong> Jauhi lokasi dan telepon <a href="tel:112" class="font-bold underline">112</a>.</p>
                    <a
                        href="{{ url('/') }}"
                        class="inline-block text-xs font-sans text-ink-muted hover:text-ink underline pt-2 min-h-[44px] leading-[44px]"
                    >
                        ← Kembali ke Halaman Pantauan Utama
                    </a>
                </div>
            </div>
        </div>
    </template>

    {{-- TAMPILAN 2: FORMULIR LAPOR ASAP 1 LAYAR (§6.8 & §7.3) --}}
    <template x-if="!sukses">
        <div class="space-y-6">
            {{-- HEADER FORMULIR --}}
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <a href="{{ url('/') }}" class="text-xs font-sans text-ink-muted hover:text-ink transition flex items-center gap-1 min-h-[44px]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span>Kembali ke Pantauan</span>
                    </a>
                    <span class="text-xs text-ink-muted font-sans">•</span>
                    <span class="text-xs font-sans text-ember font-bold tracking-normal">Laporan Lapangan</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-display font-bold text-ink tracking-tight">
                    Lapor Asap & Titik Api
                </h1>
                <p class="text-base text-ink-muted mt-1 leading-relaxed">
                    Laporan Anda langsung diverifikasi oleh petugas TNBTS sebelum dipublikasikan ke peta kawasan.
                </p>
            </div>

            <div class="p-3 border-2 border-danger rounded-md text-xs font-sans" role="note">
                <strong class="text-danger">Api besar atau ada orang dalam bahaya?</strong>
                <span class="text-ink"> Jauhi lokasi dan telepon <a href="tel:112" class="font-bold underline">112</a> lebih dulu, baru kirim laporan ini.</span>
            </div>

            {{-- PITA PERINGATAN PEMBATASAN LAJU JIKA TERKUNCI (§9) --}}
            <template x-if="terkunci">
                <div class="p-4 bg-danger/10 border-2 border-danger/30 rounded-md text-xs font-sans text-danger space-y-1" role="alert">
                    <div class="flex items-center gap-2 font-bold">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        <span>BATAS LAPORAN TERCAPAI (3 LAPORAN / 15 MENIT)</span>
                    </div>
                    <p class="text-xs text-ink">
                        Untuk mencegah laporan berulang, formulir dapat digunakan kembali dalam <strong class="text-danger tabular-nums" x-text="formatWaktu(detikTunggu)"></strong>.
                    </p>
                </div>
            </template>

            @if ($errors->any())
                <div class="p-4 bg-danger/10 border border-danger/30 rounded-md text-xs font-sans text-danger space-y-1">
                    <strong class="block font-bold">Periksa kembali data Anda:</strong>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- FORMULIR UTAMA --}}
            <form
                action="{{ route('lapor.store') }}"
                method="POST"
                enctype="multipart/form-data"
                @submit="periksaJenis($event)"
                class="p-5 sm:p-6 bg-paper-2 border border-line rounded-lg shadow-none space-y-5"
            >
                @csrf

                {{-- 1. LOKASI GPS & PETA MINI --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-sans">
                        <label class="font-bold text-ink">
                            1. Titik Lokasi Temuan
                        </label>
                        <button
                            type="button"
                            @click="ambilGpsOtomatis()"
                            class="text-forest hover:text-moss hover:underline font-semibold flex items-center gap-1.5 min-h-[44px] px-2 rounded-md active:scale-[0.98] transition"
                        >
                            <svg class="w-3.5 h-3.5" :class="gpsLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Perbarui GPS</span>
                        </button>
                    </div>

                    {{-- Status GPS Akurasi (Badge Hijau WCAG) --}}
                    <div class="p-3 bg-paper rounded-md border border-line flex items-center justify-between text-xs font-sans">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="akurasi ? 'bg-safe' : 'bg-warn'"></span>
                            <span class="text-ink truncate font-semibold" x-text="lokasiNama"></span>
                            <span x-show="kelasRisiko" x-cloak class="px-2 py-0.5 rounded-full text-xs font-bold border border-line text-ink shrink-0" x-text="kelasRisiko ? 'Risiko ' + kelasRisiko.toLowerCase() : ''"></span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <template x-if="akurasi">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-safe-fill text-[#14532D] border border-safe/30 tabular-nums" x-text="'Akurasi ±' + akurasi + ' m'"></span>
                            </template>
                            <template x-if="!akurasi">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-warn-fill text-[#5C3D06] border border-warn/30">Pin Manual</span>
                            </template>
                        </div>
                    </div>

                    <template x-if="gpsError">
                        <p class="text-xs font-sans text-warn leading-tight" x-text="gpsError"></p>
                    </template>

                    {{-- Peta Mini Leaflet --}}
                    <div class="report-map-frame relative w-full rounded-md overflow-hidden border border-line bg-paper-3">
                        <div id="petaMiniLapor" class="w-full h-full z-10" tabindex="0" role="region" aria-label="Peta Mini Pemilihan Titik Lapor"></div>
                    </div>
                    <span class="text-xs font-sans text-ink-muted block text-center">
                        Geser pin merah atau ketuk peta bila posisi GPS kurang tepat.
                    </span>

                    {{-- Hidden Inputs --}}
                    <input type="hidden" name="latitude" x-model="lat">
                    <input type="hidden" name="longitude" x-model="lng">
                    <input type="hidden" name="akurasi_gps" x-model="akurasi">
                    <input type="hidden" name="zona_id" x-model="zonaId">
                </div>

                {{-- 2. JENIS GEJALA (PILIHAN WAJIB, TANPA NILAI AWAL) --}}
                <div class="space-y-2" id="blok-jenis">
                    <label class="block text-xs font-sans font-bold text-ink">
                        2. Apa yang kamu lihat?
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs font-sans">
                        <label
                            :class="jenisTemuan === 'asap_tipis' ? 'bg-forest text-white border-forest' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[64px] flex flex-col justify-center gap-0.5 p-3 border rounded-md cursor-pointer transition select-none active:scale-[0.98]"
                        >
                            <input type="radio" name="jenis_temuan" value="asap_tipis" x-model="jenisTemuan" @change="jenisError = false" class="sr-only">
                            <span class="font-semibold text-xs">Asap tipis</span>
                            <span class="text-[11px] leading-tight opacity-90">Asap sedikit, mungkin masih kecil</span>
                        </label>
                        <label
                            :class="jenisTemuan === 'asap_tebal' ? 'bg-warn text-white border-warn' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[64px] flex flex-col justify-center gap-0.5 p-3 border rounded-md cursor-pointer transition select-none active:scale-[0.98]"
                        >
                            <input type="radio" name="jenis_temuan" value="asap_tebal" x-model="jenisTemuan" @change="jenisError = false" class="sr-only">
                            <span class="font-semibold text-xs">Asap tebal</span>
                            <span class="text-[11px] leading-tight opacity-90">Asap pekat atau membumbung banyak</span>
                        </label>
                        <label
                            :class="jenisTemuan === 'api_terlihat' ? 'bg-ember text-white border-ember' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[64px] flex flex-col justify-center gap-0.5 p-3 border rounded-md cursor-pointer transition select-none active:scale-[0.98]"
                        >
                            <input type="radio" name="jenis_temuan" value="api_terlihat" x-model="jenisTemuan" @change="jenisError = false" class="sr-only">
                            <span class="font-semibold text-xs">Api terlihat</span>
                            <span class="text-[11px] leading-tight opacity-90">Ada nyala api</span>
                        </label>
                        <label
                            :class="jenisTemuan === 'bekas_terbakar' ? 'bg-forest text-white border-forest' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[64px] flex flex-col justify-center gap-0.5 p-3 border rounded-md cursor-pointer transition select-none active:scale-[0.98]"
                        >
                            <input type="radio" name="jenis_temuan" value="bekas_terbakar" x-model="jenisTemuan" @change="jenisError = false" class="sr-only">
                            <span class="font-semibold text-xs">Bekas terbakar</span>
                            <span class="text-[11px] leading-tight opacity-90">Lahan hangus, tidak ada api</span>
                        </label>
                    </div>
                    <p x-show="jenisError" x-cloak class="text-xs font-sans font-semibold text-danger" role="alert">Pilih salah satu dulu supaya petugas tahu apa yang kamu lihat.</p>
                </div>

                {{-- 3. UNGGAH FOTO 1:1 DENGAN PRATINJAU & TOMBOL HAPUS CEPAT --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-sans">
                        <label class="font-bold text-ink">
                            3. Foto Lapangan (Maks 2 Foto)
                        </label>
                        <span class="text-xs text-ink-muted">Maks 5 MB / foto</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Input Berkas Foto 1 & 2 --}}
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-sans text-ink-muted mb-1">Foto Utama:</label>
                            <input
                                type="file"
                                id="inputFoto1"
                                name="foto_1"
                                accept="image/jpeg,image/png,image/heic,image/webp"
                                @change="handleFoto($event)"
                                class="w-full text-xs font-sans file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-forest file:text-white hover:file:bg-moss cursor-pointer"
                            >
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-sans text-ink-muted mb-1">Foto Tambahan (Opsional):</label>
                            <input
                                type="file"
                                id="inputFoto2"
                                name="foto_2"
                                accept="image/jpeg,image/png,image/heic,image/webp"
                                @change="handleFoto($event)"
                                class="w-full text-xs font-sans file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-paper-3 file:text-ink hover:file:bg-paper cursor-pointer"
                            >
                        </div>
                    </div>

                    {{-- Pratinjau 1:1 dengan Cap Waktu & Tombol Hapus Cepat --}}
                    <div x-show="fotoPreviews.length > 0" class="grid grid-cols-2 gap-3 pt-2">
                        <template x-for="(foto, i) in fotoPreviews" :key="i">
                            <div class="relative aspect-square rounded-md overflow-hidden border border-line bg-paper-3 shadow-none">
                                <img :src="foto.url" class="w-full h-full object-cover" alt="Pratinjau foto laporan">

                                {{-- Tombol Hapus Cepat () --}}
                                <button
                                    type="button"
                                    @click="hapusFoto(i)"
                                    aria-label="Hapus foto"
                                    class="absolute top-2 right-2 w-7 h-7 rounded-full bg-ink/80 hover:bg-danger text-white flex items-center justify-center text-xs font-bold transition duration-fast shadow-none cursor-pointer"
                                >

                                </button>

                                <div class="absolute inset-x-0 bottom-0 bg-black/60 p-2 text-white text-xs font-sans leading-tight">
                                    <div class="flex justify-between font-semibold">
                                        <span x-text="foto.waktu"></span>
                                        <span>Bromo</span>
                                    </div>
                                    <div class="truncate opacity-80 tabular-nums text-[9px]" x-text="foto.pos"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 4. CATATAN TAMBAHAN --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-sans font-bold text-ink">
                        4. Catatan Arah Angin / Patokan Lokasi
                    </label>
                    <textarea
                        name="catatan"
                        x-model="catatan"
                        rows="3"
                        placeholder="Contoh: Asap terlihat di lereng bukit sebelah timur savana, angin bertiup kencang ke arah kaldera."
                        class="w-full px-3.5 py-2.5 rounded-md border border-line bg-paper text-base text-ink focus:outline-none focus:ring-2 focus:ring-forest leading-relaxed"
                    ></textarea>
                </div>

                {{-- 5. INFORMASI PRIVASI --}}
                <div class="p-3 bg-paper rounded-md border border-line text-xs font-sans text-ink-muted space-y-1">
                    <div class="flex items-center gap-1.5 text-ink font-semibold">
                        <span></span>
                        <span>Kebijakan Privasi Pelapor</span>
                    </div>
                    <p class="text-xs leading-relaxed">
                        Lokasi Anda hanya terlihat oleh petugas resmi TNBTS & BPBD. Laporan tamu disimpan tanpa riwayat pelacakan pribadi.
                    </p>
                    <div class="flex items-center justify-between pt-1 text-xs">
                        <span>Batas sisa perangkat: <strong class="text-ink" x-text="sisaKuota"></strong> / 3 laporan</span>
                        <span>Maks 3 per 15 menit</span>
                    </div>
                </div>

                {{-- TOMBOL KIRIM LAPORAN 56 PX SOLID STICKY (SAFE AREA) --}}
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="terkunci"
                        :class="terkunci ? 'bg-paper-3 text-ink-muted cursor-not-allowed border border-line' : 'bg-ember hover:brightness-105 active:scale-[0.98] text-white cursor-pointer shadow-sheet'"
                        class="w-full h-14 rounded-md font-display font-bold text-base tracking-wide transition duration-fast flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <template x-if="!terkunci">
                            <span>Kirim laporan sekarang</span>
                        </template>
                        <template x-if="terkunci">
                            <span>Batas tercapai (Tunggu <span x-text="formatWaktu(detikTunggu)"></span>)</span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </template>
</div>
@endsection
