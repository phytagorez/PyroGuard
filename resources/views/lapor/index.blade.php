@extends('layouts.publik')

@section('judul', 'Lapor Asap & Titik Api Bromo')

@section('konten')
@php
    $suksesTiket = session('tiket_berhasil');
@endphp

<div 
    x-data="{
        sukses: {{ $suksesTiket ? 'true' : 'false' }},
        tiket: {{ Js::from($suksesTiket) }},
        zonas: {{ Js::from($zonas) }},
        
        // Form state
        lat: {{ $zonaPilihan?->titik_tengah_lat ?? -7.9650000 }},
        lng: {{ $zonaPilihan?->titik_tengah_lng ?? 112.9680000 }},
        akurasi: null,
        lokasiNama: '{{ $zonaPilihan?->nama ?? 'Savana & Jemplang' }}',
        zonaId: {{ $zonaPilihan?->id ?? 4 }},
        zonaKode: '{{ $zonaPilihan?->kode ?? 'BRM-04' }}',
        jenisTemuan: 'asap_tipis',
        catatan: '',
        
        // Peta & GPS
        mapInstance: null,
        markerInstance: null,
        gpsLoading: false,
        gpsError: null,
        
        // Foto state
        fotoPreviews: [],
        
        // Rate limiting state
        sisaKuota: {{ $batasLaju['sisa_kuota'] ?? 3 }},
        terkunci: {{ ($batasLaju['terkunci'] ?? false) ? 'true' : 'false' }},
        detikTunggu: {{ $batasLaju['detik_tunggu'] ?? 0 }},
        
        // Timer lapor lagi (60s)
        detikLaporLagi: 60,
        timerLaporLagi: null,
        
        init() {
            if (this.terkunci && this.detikTunggu > 0) {
                this.mulaiCountdownBatasLaju();
            }

            if (this.sukses) {
                this.mulaiCountdownLaporLagi();
            } else {
                this.$nextTick(() => {
                    this.inisialisasiPetaMini();
                    this.ambilGpsOtomatis();
                });
            }
        },

        inisialisasiPetaMini() {
            if (!window.L || this.sukses) return;

            const map = window.L.map('petaMiniLapor', {
                center: [this.lat, this.lng],
                zoom: 13,
                zoomControl: false,
                attributionControl: false,
            });

            this.mapInstance = map;
            window.L.control.zoom({ position: 'topright' }).addTo(map);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
            }).addTo(map);

            // Icon pin merah
            const pinIcon = window.L.divIcon({
                className: 'custom-pin',
                html: `
                    <div class='flex items-center justify-center w-8 h-8'>
                        <div class='w-5 h-5 rounded-full bg-ember border-2 border-white shadow-lg flex items-center justify-center text-white text-xs'>
                            🔥
                        </div>
                    </div>
                `,
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });

            const marker = window.L.marker([this.lat, this.lng], {
                icon: pinIcon,
                draggable: true,
            }).addTo(map);

            this.markerInstance = marker;

            marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                this.lat = parseFloat(pos.lat.toFixed(7));
                this.lng = parseFloat(pos.lng.toFixed(7));
                this.akurasi = null;
                this.hitungZonaTerdekat();
            });

            map.on('click', (e) => {
                this.lat = parseFloat(e.latlng.lat.toFixed(7));
                this.lng = parseFloat(e.latlng.lng.toFixed(7));
                this.markerInstance.setLatLng(e.latlng);
                this.akurasi = null;
                this.hitungZonaTerdekat();
            });
        },

        ambilGpsOtomatis() {
            if (!navigator.geolocation) {
                this.gpsError = 'Perangkat tidak mendukung GPS. Silakan geser pin manual pada peta.';
                return;
            }

            this.gpsLoading = true;
            this.gpsError = null;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = parseFloat(pos.coords.latitude.toFixed(7));
                    this.lng = parseFloat(pos.coords.longitude.toFixed(7));
                    this.akurasi = Math.round(pos.coords.accuracy);
                    this.gpsLoading = false;

                    if (this.markerInstance && this.mapInstance) {
                        this.markerInstance.setLatLng([this.lat, this.lng]);
                        this.mapInstance.panTo([this.lat, this.lng]);
                    }

                    this.hitungZonaTerdekat();
                },
                (err) => {
                    this.gpsLoading = false;
                    this.gpsError = 'Izin lokasi tidak diaktifkan. Anda dapat menggeser pin merah di peta untuk menentukan titik temuan.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        hitungZonaTerdekat() {
            let jarakMin = Infinity;
            let terpilih = this.zonas[0];

            this.zonas.forEach(z => {
                const dLat = z.titik_tengah_lat - this.lat;
                const dLng = z.titik_tengah_lng - this.lng;
                const d = (dLat * dLat) + (dLng * dLng);
                if (d < jarakMin) {
                    jarakMin = d;
                    terpilih = z;
                }
            });

            this.zonaId = terpilih.id;
            this.zonaKode = terpilih.kode;
            this.lokasiNama = terpilih.nama;
        },

        handleFoto(event) {
            const files = Array.from(event.target.files).slice(0, 2);
            this.fotoPreviews = [];

            files.forEach((file, idx) => {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Foto ' + file.name + ' melebihi ukuran maksimal 5 MB.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const now = new Date();
                    const jam = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    this.fotoPreviews.push({
                        url: e.target.result,
                        nama: file.name,
                        waktu: jam + ' WIB',
                        pos: this.lat.toFixed(4) + ', ' + this.lng.toFixed(4),
                    });
                };
                reader.readAsDataURL(file);
            });
        },

        mulaiCountdownBatasLaju() {
            const interval = setInterval(() => {
                if (this.detikTunggu <= 1) {
                    clearInterval(interval);
                    this.terkunci = false;
                    this.sisaKuota = 3;
                    this.detikTunggu = 0;
                } else {
                    this.detikTunggu--;
                }
            }, 1000);
        },

        mulaiCountdownLaporLagi() {
            this.detikLaporLagi = 60;
            this.timerLaporLagi = setInterval(() => {
                if (this.detikLaporLagi <= 1) {
                    clearInterval(this.timerLaporLagi);
                    this.detikLaporLagi = 0;
                } else {
                    this.detikLaporLagi--;
                }
            }, 1000);
        },

        formatWaktu(sec) {
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        salinNomorTiket(nomor) {
            navigator.clipboard.writeText(nomor);
            alert('Nomor tiket ' + nomor + ' berhasil disalin ke clipboard.');
        },

        resetForm() {
            this.sukses = false;
            this.tiket = null;
            this.fotoPreviews = [];
            this.catatan = '';
            this.$nextTick(() => {
                this.inisialisasiPetaMini();
            });
        }
    }"
    class="max-w-xl mx-auto space-y-6 pb-24"
>
    {{-- TAMPILAN 1: TICKET SUCCESS (§6.8 & §7.3) --}}
    <template x-if="sukses">
        <div class="space-y-6 animate-fade-in">
            {{-- KARTU TIKET SUKSES --}}
            <div class="p-6 sm:p-8 bg-paper-2 border-2 border-forest/30 rounded-3xl shadow-sheet text-center space-y-5">
                <div class="w-16 h-16 rounded-2xl bg-safe/10 text-safe border border-safe/30 mx-auto flex items-center justify-center text-3xl">
                    ✓
                </div>

                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/20 text-warn border border-warn/30 mb-2">
                        STATUS: BARU
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-mono font-bold text-ink tracking-tight" x-text="tiket?.nomor_tiket || '#LP-0231'"></h1>
                    <p class="text-sm font-medium text-ink mt-1">
                        Laporan telah masuk ke antrean verifikasi.
                    </p>
                    <p class="text-xs text-ink-muted">
                        Petugas terdekat di pos TNBTS telah diberi tahu untuk peninjauan lapangan.
                    </p>
                </div>

                {{-- Detail Ringkas Laporan --}}
                <div class="p-4 bg-paper rounded-2xl border border-line text-left text-xs font-mono space-y-2">
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Zona Temuan:</span>
                        <strong class="text-ink" x-text="(tiket?.zona?.kode || zonaKode) + ' · ' + (tiket?.zona?.nama || lokasiNama)"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Koordinat:</span>
                        <span class="text-ink tabular-nums" x-text="(tiket?.latitude || lat) + ', ' + (tiket?.longitude || lng)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Jenis Temuan:</span>
                        <span class="font-bold uppercase text-ember" x-text="(tiket?.jenis_temuan || jenisTemuan).replace('_', ' ')"></span>
                    </div>
                </div>

                {{-- Aksi Tiket --}}
                <div class="space-y-3 pt-2">
                    <button 
                        type="button" 
                        @click="salinNomorTiket(tiket?.nomor_tiket || '#LP-0231')"
                        class="w-full h-12 bg-forest hover:bg-moss active:scale-98 text-white rounded-xl font-display font-semibold text-sm transition flex items-center justify-center gap-2 shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span>Simpan nomor tiket</span>
                    </button>

                    <button 
                        type="button" 
                        @click="resetForm()"
                        :disabled="detikLaporLagi > 0"
                        :class="detikLaporLagi > 0 ? 'bg-paper-3 text-ink-muted cursor-not-allowed border border-line' : 'bg-paper hover:bg-paper-2 text-ink border border-line cursor-pointer'"
                        class="w-full h-12 rounded-xl font-display font-semibold text-sm transition flex items-center justify-center gap-2"
                    >
                        <template x-if="detikLaporLagi > 0">
                            <span>Lapor lagi (<span x-text="detikLaporLagi"></span>s)</span>
                        </template>
                        <template x-if="detikLaporLagi <= 0">
                            <span>Lapor asap lainnya</span>
                        </template>
                    </button>

                    <a 
                        href="{{ url('/') }}" 
                        class="inline-block text-xs font-mono text-ink-muted hover:text-ink underline pt-2"
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
                    <a href="{{ url('/') }}" class="text-xs font-mono text-ink-muted hover:text-ink transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span>Kembali ke Pantauan</span>
                    </a>
                    <span class="text-xs text-ink-muted font-mono">•</span>
                    <span class="text-xs font-mono text-ember font-bold uppercase tracking-wider">Laporan Lapangan</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-display font-bold text-ink tracking-tight">
                    Lapor Asap & Titik Api
                </h1>
                <p class="text-sm text-ink-muted mt-1 leading-relaxed">
                    Laporan Anda langsung diverifikasi oleh petugas TNBTS sebelum dipublikasikan ke peta kawasan.
                </p>
            </div>

            {{-- PITA PERINGATAN PEMBATASAN LAJU JIKA TERKUNCI (§9) --}}
            <template x-if="terkunci">
                <div class="p-4 bg-danger/10 border-2 border-danger/30 rounded-2xl text-xs font-mono text-danger space-y-1" role="alert">
                    <div class="flex items-center gap-2 font-bold">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        <span>BATAS LAPORAN TERCAPAI (3 LAPORAN / 15 MENIT)</span>
                    </div>
                    <p class="text-[11px] text-ink">
                        Untuk mencegah laporan berulang, formulir dapat digunakan kembali dalam <strong class="text-danger" x-text="formatWaktu(detikTunggu)"></strong>.
                    </p>
                </div>
            </template>

            @if ($errors->any())
                <div class="p-4 bg-danger/10 border border-danger/30 rounded-2xl text-xs font-mono text-danger space-y-1">
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
                class="p-5 sm:p-6 bg-paper-2 border border-line rounded-2xl shadow-card space-y-5"
            >
                @csrf

                {{-- 1. LOKASI GPS & PETA MINI --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <label class="font-bold uppercase text-ink">
                            1. Titik Lokasi Temuan
                        </label>
                        <button 
                            type="button" 
                            @click="ambilGpsOtomatis()" 
                            class="text-forest hover:underline font-semibold flex items-center gap-1 min-h-[36px]"
                        >
                            <svg class="w-3.5 h-3.5" :class="gpsLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Perbarui GPS</span>
                        </button>
                    </div>

                    {{-- Status GPS Akurasi --}}
                    <div class="p-2.5 bg-paper rounded-xl border border-line flex items-center justify-between text-xs font-mono">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="w-2 h-2 rounded-full" :class="akurasi ? 'bg-safe' : 'bg-warn'"></span>
                            <span class="text-ink truncate" x-text="zonaKode + ' · ' + lokasiNama"></span>
                        </div>
                        <span class="text-[11px] text-ink-muted shrink-0 tabular-nums" x-text="akurasi ? 'Akurasi ±' + akurasi + ' m' : 'Pin Manual'"></span>
                    </div>

                    <template x-if="gpsError">
                        <p class="text-[11px] font-mono text-warn leading-tight" x-text="gpsError"></p>
                    </template>

                    {{-- Peta Mini Leaflet --}}
                    <div class="relative w-full h-44 rounded-xl overflow-hidden border border-line bg-paper-3">
                        <div id="petaMiniLapor" class="w-full h-full z-10" tabindex="0" role="region" aria-label="Peta Mini Pemilihan Titik Lapor"></div>
                    </div>
                    <span class="text-[10px] font-mono text-ink-muted block text-center">
                        Geser pin merah atau ketuk peta bila posisi GPS kurang tepat.
                    </span>

                    {{-- Hidden Inputs --}}
                    <input type="hidden" name="latitude" x-model="lat">
                    <input type="hidden" name="longitude" x-model="lng">
                    <input type="hidden" name="akurasi_gps" x-model="akurasi">
                    <input type="hidden" name="zona_id" x-model="zonaId">
                </div>

                {{-- 2. JENIS GEJALA (CHIP RADIO TARGET SENTUH >= 44 PX - §6.8) --}}
                <div class="space-y-2">
                    <label class="block text-xs font-mono font-bold uppercase text-ink">
                        2. Jenis Gejala yang Terlihat
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                        <label 
                            :class="jenisTemuan === 'asap_tipis' ? 'bg-forest text-white border-forest' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[48px] flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition select-none active:scale-98"
                        >
                            <input type="radio" name="jenis_temuan" value="asap_tipis" x-model="jenisTemuan" class="sr-only">
                            <span class="text-sm">💨</span>
                            <span class="font-semibold">Asap tipis</span>
                        </label>

                        <label 
                            :class="jenisTemuan === 'asap_tebal' ? 'bg-warn text-white border-warn' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[48px] flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition select-none active:scale-98"
                        >
                            <input type="radio" name="jenis_temuan" value="asap_tebal" x-model="jenisTemuan" class="sr-only">
                            <span class="text-sm">🌪️</span>
                            <span class="font-semibold">Asap tebal</span>
                        </label>

                        <label 
                            :class="jenisTemuan === 'api_terlihat' ? 'bg-danger text-white border-danger' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[48px] flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition select-none active:scale-98"
                        >
                            <input type="radio" name="jenis_temuan" value="api_terlihat" x-model="jenisTemuan" class="sr-only">
                            <span class="text-sm">🔥</span>
                            <span class="font-bold">Api terlihat</span>
                        </label>

                        <label 
                            :class="jenisTemuan === 'bekas_terbakar' ? 'bg-ink text-white border-ink' : 'bg-paper text-ink border-line hover:bg-paper-3'"
                            class="min-h-[48px] flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition select-none active:scale-98"
                        >
                            <input type="radio" name="jenis_temuan" value="bekas_terbakar" x-model="jenisTemuan" class="sr-only">
                            <span class="text-sm">🪵</span>
                            <span class="font-semibold">Bekas terbakar</span>
                        </label>
                    </div>
                </div>

                {{-- 3. UNGGAH FOTO (MAKS 2, KOMPRESI 1200 PX, PRATINJAU 1:1) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <label class="font-bold uppercase text-ink">
                            3. Foto Lapangan (Maks 2 Foto)
                        </label>
                        <span class="text-[11px] text-ink-muted">Maks 5 MB / foto</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Input Berkas Foto 1 & 2 --}}
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[11px] font-mono text-ink-muted mb-1">Foto Utama:</label>
                            <input 
                                type="file" 
                                name="foto_1" 
                                accept="image/jpeg,image/png,image/heic,image/webp"
                                @change="handleFoto($event)"
                                class="w-full text-xs font-mono file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-forest file:text-white hover:file:bg-moss cursor-pointer"
                            >
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[11px] font-mono text-ink-muted mb-1">Foto Tambahan (Opsional):</label>
                            <input 
                                type="file" 
                                name="foto_2" 
                                accept="image/jpeg,image/png,image/heic,image/webp"
                                @change="handleFoto($event)"
                                class="w-full text-xs font-mono file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-paper-3 file:text-ink hover:file:bg-paper cursor-pointer"
                            >
                        </div>
                    </div>

                    {{-- Pratinjau 1:1 dengan Cap Waktu & Koordinat (§6.8) --}}
                    <div x-show="fotoPreviews.length > 0" class="grid grid-cols-2 gap-3 pt-2">
                        <template x-for="(foto, i) in fotoPreviews" :key="i">
                            <div class="relative aspect-square rounded-xl overflow-hidden border border-line bg-paper-3 shadow-sm">
                                <img :src="foto.url" class="w-full h-full object-cover" alt="Pratinjau foto laporan">
                                <div class="absolute inset-x-0 bottom-0 bg-black/60 backdrop-blur-xs p-1.5 text-white text-[9px] font-mono leading-tight">
                                    <div class="flex justify-between">
                                        <span x-text="foto.waktu"></span>
                                        <span>Bromo</span>
                                    </div>
                                    <div class="truncate opacity-80" x-text="foto.pos"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 4. CATATAN TAMBAHAN (3 BARIS) --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-mono font-bold uppercase text-ink">
                        4. Catatan Arah Angin / Patokan Lokasi
                    </label>
                    <textarea 
                        name="catatan" 
                        x-model="catatan"
                        rows="3" 
                        placeholder="Contoh: Asap terlihat di lereng bukit sebelah timur savana, angin bertiup kencang ke arah kaldera."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest leading-relaxed"
                    ></textarea>
                </div>

                {{-- 5. INFORMASI PRIVASI (§6.8) --}}
                <div class="p-3 bg-paper rounded-xl border border-line text-xs font-mono text-ink-muted space-y-1">
                    <div class="flex items-center gap-1.5 text-ink font-semibold">
                        <span>🛡️</span>
                        <span>Kebijakan Privasi Pelapor</span>
                    </div>
                    <p class="text-[11px] leading-relaxed">
                        Lokasi Anda hanya terlihat oleh petugas resmi TNBTS & BPBD. Laporan tamu disimpan tanpa riwayat pelacakan pribadi.
                    </p>
                    <div class="flex items-center justify-between pt-1 text-[10px]">
                        <span>Batas sisa perangkat: <strong class="text-ink" x-text="sisaKuota"></strong> / 3 laporan</span>
                        <span>Maks 3 per 15 menit</span>
                    </div>
                </div>

                {{-- TOMBOL KIRIM LAPORAN 56 PX SOLID (§6.8) --}}
                <button 
                    type="submit"
                    :disabled="terkunci"
                    :class="terkunci ? 'bg-paper-3 text-ink-muted cursor-not-allowed border border-line' : 'bg-ember hover:brightness-105 active:scale-98 text-white cursor-pointer shadow-sheet'"
                    class="w-full h-14 rounded-2xl font-display font-bold text-base tracking-wide transition flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <template x-if="!terkunci">
                        <span>Kirim laporan sekarang</span>
                    </template>
                    <template x-if="terkunci">
                        <span>Batas tercapai (Tunggu <span x-text="formatWaktu(detikTunggu)"></span>)</span>
                    </template>
                </button>
            </form>
        </div>
    </template>
</div>
@endsection
