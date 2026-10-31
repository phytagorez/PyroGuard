@extends('layouts.publik')

@section('judul', 'Mode Demo Interaktif — Penggeser Kemarau')

@section('konten')
<div 
    x-data="{
        hari: {{ $hari }},
        zonas: {{ Js::from(array_values($zonasSimulasi)) }},
        zonaTertinggi: {{ Js::from($zonaTertinggi) }},
        zonaTerpilihKode: '{{ $zonaTertinggi['kode'] ?? 'BRM-04' }}',
        memuatSimulasi: false,
        mapInstance: null,
        zoneLayers: {},
        debounceTimer: null,

        init() {
            this.$nextTick(() => {
                this.inisialisasiPeta();
            });
        },

        get zonaAktif() {
            return this.zonas.find(z => z.kode === this.zonaTerpilihKode) || this.zonaTertinggi;
        },

        inisialisasiPeta() {
            if (!window.L) return;

            const map = window.L.map('petaDemo', {
                center: [-7.948, 112.946],
                zoom: 12,
                zoomControl: false,
                attributionControl: false,
            });

            this.mapInstance = map;
            window.L.control.zoom({ position: 'topright' }).addTo(map);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap',
            }).addTo(map);

            // Buat pola garis SVG untuk aksesibilitas buta warna (§2 P2 & §5.1)
            const svg = map.getPanes().overlayPane.querySelector('svg');
            if (svg) {
                const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                defs.innerHTML = `
                    <pattern id='pola-bahaya-demo' width='8' height='8' patternTransform='rotate(45 0 0)' patternUnits='userSpaceOnUse'>
                        <line x1='0' y1='0' x2='0' y2='8' stroke='#9E2B22' stroke-width='2' />
                    </pattern>
                `;
                svg.appendChild(defs);
            }

            // Render Poligon 6 Zona Bromo
            this.zonas.forEach(zona => {
                const styleWarna = this.getStyleZona(zona.siaga === 'SIAGA_1' ? 'SIAGA 1' : (zona.siaga === 'SIAGA_OTOMATIS' ? 'SIAGA OTOMATIS' : zona.kategori));

                const geoJsonLayer = window.L.geoJSON(zona.poligon_geojson, {
                    style: {
                        color: styleWarna.stroke,
                        weight: 2,
                        opacity: 0.9,
                        fillColor: styleWarna.fill,
                        fillOpacity: 0.55,
                    },
                    onEachFeature: (feature, layer) => {
                        layer.bindTooltip(`${zona.kode} · ${zona.nama.split('&')[0].trim()}`, {
                            permanent: true,
                            direction: 'center',
                            className: 'zone-label-pill',
                        });

                        layer.on('click', () => {
                            this.pilihZona(zona.kode);
                        });
                    }
                }).addTo(map);

                this.zoneLayers[zona.kode] = geoJsonLayer;
            });
        },

        pilihZona(kode) {
            this.zonaTerpilihKode = kode;
        },

        getStyleZona(status) {
            const styles = {
                'SIAGA 1': { stroke: '#7F1D1D', fill: '#7F1D1D' },
                'SIAGA OTOMATIS': { stroke: '#9E2B22', fill: '#D9544A' },
                'BAHAYA': { stroke: '#9E2B22', fill: '#D9544A' },
                'WASPADA': { stroke: '#A86412', fill: '#FBE3A6' },
                'AMAN': { stroke: '#1F6B45', fill: '#CFE6D6' },
                'DICABUT': { stroke: '#4C5A52', fill: '#EDE7DA' },
            };
            return styles[status] || styles['AMAN'];
        },

        onGeserHari() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.terapkanSimulasi(this.hari);
            }, 60);
        },

        pilihSkenario(targetHari) {
            this.hari = targetHari;
            this.terapkanSimulasi(targetHari);
        },

        async terapkanSimulasi(targetHari) {
            this.memuatSimulasi = true;
            try {
                const response = await fetch('{{ route('demo.simulasi') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ hari: targetHari }),
                });

                if (response.ok) {
                    const result = await response.json();
                    this.zonas = result.zonas;
                    this.zonaTertinggi = result.zonaTertinggi;

                    // Perbarui gaya warna poligon peta secara mulus (transisi 420 ms via CSS path.leaflet-interactive)
                    this.zonas.forEach(z => {
                        const layer = this.zoneLayers[z.kode];
                        if (layer) {
                            const status = z.siaga === 'SIAGA_1' ? 'SIAGA 1' : (z.siaga === 'SIAGA_OTOMATIS' ? 'SIAGA OTOMATIS' : z.kategori);
                            const styleWarna = this.getStyleZona(status);
                            layer.setStyle({
                                color: styleWarna.stroke,
                                fillColor: styleWarna.fill,
                            });
                        }
                    });
                }
            } catch (err) {
                console.error('Galat memuat simulasi:', err);
            } finally {
                this.memuatSimulasi = false;
            }
        },

        formatAngka(num) {
            return Number(num).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 });
        }
    }"
    class="space-y-6 pb-20"
>
    {{-- 1. PITA TETAP MODE DEMO · [SIMULASI] (§6.13, §7.10, §15) --}}
    <div class="sticky top-[52px] z-30 -mx-4 sm:-mx-6 px-4 sm:px-6 py-2.5 bg-amber-500 text-ink border-b border-amber-600 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-mono">
            <span class="w-2.5 h-2.5 rounded-full bg-ink animate-pulse"></span>
            <span class="font-bold tracking-wider uppercase">MODE DEMO · [SIMULASI]</span>
            <span class="hidden md:inline text-ink/80">• Data simulasi pengujian & penjurian. Tidak memicu peringatan nyata.</span>
        </div>

        <div class="flex items-center gap-2">
            <form action="{{ route('demo.reset') }}" method="POST" class="inline">
                @csrf
                <button 
                    type="submit" 
                    class="px-3 py-1 bg-ink text-white hover:bg-forest text-xs font-mono font-semibold rounded-lg transition active:scale-98 flex items-center gap-1.5 shadow-xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Data Aktual</span>
                </button>
            </form>
        </div>
    </div>

    {{-- 2. PANDUAN ALUR DEMO UJUNG-KE-UJUNG (7 LANGKAH SEKUENSIAL §15) --}}
    <div class="p-4 sm:p-5 bg-paper-2 border border-line rounded-3xl shadow-card space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-forest"></span>
                <h2 class="text-xs font-mono uppercase font-bold text-forest tracking-wider">
                    Alur Demonstrasi Sistem Ujung-ke-Ujung (Tuntas dlm 3 Menit)
                </h2>
            </div>
            <span class="text-[11px] font-mono text-ink-muted">Satu Sesi Lengkap</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 text-[11px] font-mono">
            {{-- Langkah 1: Hari 15 --}}
            <button 
                type="button" 
                @click="pilihSkenario(15)"
                :class="hari === 15 ? 'bg-forest text-white font-bold ring-2 ring-forest' : 'bg-paper hover:bg-paper-3 text-ink border border-line'"
                class="p-2.5 rounded-xl text-left transition flex flex-col justify-between gap-1 shadow-xs"
            >
                <span class="text-[10px] text-amber-500 font-bold">1. PEMICU</span>
                <span class="text-xs font-bold leading-tight">Hari 15 (Savana Bahaya)</span>
            </button>

            {{-- Langkah 2: Admin Siaga 1 --}}
            <a 
                href="{{ route('admin.siaga') }}" 
                target="_blank"
                class="p-2.5 bg-paper hover:bg-paper-3 text-ink border border-line rounded-xl text-left transition flex flex-col justify-between gap-1 shadow-xs group"
            >
                <span class="text-[10px] text-danger font-bold flex items-center justify-between">
                    <span>2. OTORITAS</span>
                    <span class="group-hover:translate-x-0.5 transition">↗</span>
                </span>
                <span class="text-xs font-bold leading-tight">Setujui Siaga 1 (/admin)</span>
            </a>

            {{-- Langkah 3: Siaran Pratinjau --}}
            <a 
                href="{{ route('admin.siaran') }}" 
                target="_blank"
                class="p-2.5 bg-paper hover:bg-paper-3 text-ink border border-line rounded-xl text-left transition flex flex-col justify-between gap-1 shadow-xs group"
            >
                <span class="text-[10px] text-forest font-bold flex items-center justify-between">
                    <span>3. LOG SIARAN</span>
                    <span class="group-hover:translate-x-0.5 transition">↗</span>
                </span>
                <span class="text-xs font-bold leading-tight">Siaran [SIMULASI]</span>
            </a>

            {{-- Langkah 4: Warga Lapor --}}
            <a 
                href="{{ route('lapor.index') }}" 
                target="_blank"
                class="p-2.5 bg-paper hover:bg-paper-3 text-ink border border-line rounded-xl text-left transition flex flex-col justify-between gap-1 shadow-xs group"
            >
                <span class="text-[10px] text-ember font-bold flex items-center justify-between">
                    <span>4. WARGA</span>
                    <span class="group-hover:translate-x-0.5 transition">↗</span>
                </span>
                <span class="text-xs font-bold leading-tight">Lapor Asap Savana</span>
            </a>

            {{-- Langkah 5: Tombol Instan Buat Laporan Petugas --}}
            <form action="{{ route('demo.buat-laporan-contoh') }}" method="POST" class="contents">
                @csrf
                <button 
                    type="submit" 
                    title="Buat tiket otomatis & buka layar petugas"
                    class="p-2.5 bg-ember/10 hover:bg-ember/20 text-ember border border-ember/30 rounded-xl text-left transition flex flex-col justify-between gap-1 shadow-xs cursor-pointer"
                >
                    <span class="text-[10px] font-bold">5. INSTAN TIKET</span>
                    <span class="text-xs font-bold leading-tight">+ Tiket Lapangan ➔</span>
                </button>
            </form>

            {{-- Langkah 6: Verifikasi Petugas --}}
            <a 
                href="{{ route('petugas.index') }}" 
                target="_blank"
                class="p-2.5 bg-paper hover:bg-paper-3 text-ink border border-line rounded-xl text-left transition flex flex-col justify-between gap-1 shadow-xs group"
            >
                <span class="text-[10px] text-moss font-bold flex items-center justify-between">
                    <span>6. VERIFIKASI</span>
                    <span class="group-hover:translate-x-0.5 transition">↗</span>
                </span>
                <span class="text-xs font-bold leading-tight">Petugas Api Nyata</span>
            </a>

            {{-- Langkah 7: Hari 20 Hujan --}}
            <button 
                type="button" 
                @click="pilihSkenario(20)"
                :class="hari === 20 ? 'bg-forest text-white font-bold ring-2 ring-forest' : 'bg-paper hover:bg-paper-3 text-ink border border-line'"
                class="p-2.5 rounded-xl text-left transition flex flex-col justify-between gap-1 shadow-xs"
            >
                <span class="text-[10px] text-safe font-bold">7. PEMULIHAN</span>
                <span class="text-xs font-bold leading-tight">Hari 20 (Cabut Siaga)</span>
            </button>
        </div>
    </div>

    {{-- 3. KOMPONEN PENGGESER HARI KEMARAU DEMODIAL (§6.13 & §7.10) --}}
    <div class="p-5 sm:p-6 bg-paper-2 border border-line rounded-3xl shadow-card space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider">Komponen DemoDial (§6.13)</span>
                <h1 class="text-xl sm:text-2xl font-display font-bold text-ink mt-0.5">
                    Penggeser Hari Kemarau: <span class="text-ember font-mono" x-text="'Hari ke-' + hari"></span>
                </h1>
                <p class="text-xs text-ink-muted mt-1 leading-relaxed">
                    Geser tuas hari untuk mengamati transmisi risiko berantai: CDD terakumulasi, FDI menanjak, warna poligon bertransisi mulus (420 ms), hingga status Siaga 1 dipicu.
                </p>
            </div>

            {{-- Indikator Metrik Terkini Hari --}}
            <div class="px-4 py-2.5 bg-paper rounded-2xl border border-line text-right shrink-0">
                <span class="text-[10px] font-mono uppercase text-ink-muted block">Status Kawasan Bromo:</span>
                <span 
                    class="text-sm font-display font-bold uppercase"
                    :class="{
                        'text-danger': zonaTertinggi.kategori === 'BAHAYA',
                        'text-warn': zonaTertinggi.kategori === 'WASPADA',
                        'text-safe': zonaTertinggi.kategori === 'AMAN'
                    }"
                    x-text="zonaTertinggi.siaga === 'SIAGA_1' ? 'SIAGA 1' : (zonaTertinggi.siaga === 'SIAGA_OTOMATIS' ? 'SIAGA OTOMATIS' : zonaTertinggi.kategori)"
                ></span>
            </div>
        </div>

        {{-- TUAS PENGGESER SLIDER INTERAKTIF (0 - 20 HARI) --}}
        <div class="space-y-2 pt-2">
            <div class="flex items-center justify-between text-xs font-mono text-ink-muted px-1">
                <span>Hari ke-0 (Basah)</span>
                <span class="font-bold text-ink" x-text="'Hari ' + hari + ' dari 20 Hari'"></span>
                <span>Hari ke-20 (Hujan Turun)</span>
            </div>

            <div class="relative flex items-center">
                <input 
                    type="range" 
                    min="0" 
                    max="20" 
                    step="1"
                    x-model.number="hari"
                    @input="onGeserHari()"
                    class="w-full h-3 bg-paper-3 rounded-lg appearance-none cursor-pointer accent-forest focus:outline-none"
                    aria-label="Penggeser Hari Kemarau Simulasi Bromo"
                >
            </div>

            {{-- Penanda Skala Bawah --}}
            <div class="grid grid-cols-5 text-[10px] font-mono text-ink-muted px-1">
                <span class="text-left text-safe font-bold">Hari 0</span>
                <span class="text-center text-safe">Hari 5</span>
                <span class="text-center text-warn font-bold">Hari 10 (Waspada)</span>
                <span class="text-center text-danger font-bold">Hari 15 (Bahaya)</span>
                <span class="text-right text-safe font-bold">Hari 20 (Hujan)</span>
            </div>
        </div>

        {{-- 3 TOMBOL SKENARIO CEPAT (§7.10 design.md) --}}
        <div class="pt-2 border-t border-line/60">
            <span class="text-xs font-mono text-ink-muted block mb-2 font-semibold">Tiga Skenario Siap Pakai (§7.10):</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                {{-- Skenario 1: Hari 0 --}}
                <button 
                    type="button" 
                    @click="pilihSkenario(0)"
                    :class="hari === 0 ? 'bg-safe/20 border-safe text-safe font-bold' : 'bg-paper hover:bg-paper-3 border-line text-ink'"
                    class="min-h-[46px] p-3 rounded-xl border text-left text-xs transition flex flex-col justify-center"
                >
                    <span class="font-display font-bold">1. Awal Kemarau (Hari 0)</span>
                    <span class="text-[11px] text-ink-muted font-mono mt-0.5">Semua zona AMAN, FDI rendah (< 30)</span>
                </button>

                {{-- Skenario 2: Hari 15 --}}
                <button 
                    type="button" 
                    @click="pilihSkenario(15)"
                    :class="hari === 15 ? 'bg-danger/20 border-danger text-danger font-bold' : 'bg-paper hover:bg-paper-3 border-line text-ink'"
                    class="min-h-[46px] p-3 rounded-xl border text-left text-xs transition flex flex-col justify-center"
                >
                    <span class="font-display font-bold">2. Kebakaran Sept 2023 (Hari 15)</span>
                    <span class="text-[11px] text-ink-muted font-mono mt-0.5">Savana BAHAYA, FDI 86, SIAGA_OTOMATIS</span>
                </button>

                {{-- Skenario 3: Hari 20 --}}
                <button 
                    type="button" 
                    @click="pilihSkenario(20)"
                    :class="hari === 20 ? 'bg-moss/20 border-moss text-forest font-bold' : 'bg-paper hover:bg-paper-3 border-line text-ink'"
                    class="min-h-[46px] p-3 rounded-xl border text-left text-xs transition flex flex-col justify-center"
                >
                    <span class="font-display font-bold">3. Hujan Pertama (Hari 20)</span>
                    <span class="text-[11px] text-ink-muted font-mono mt-0.5">Hujan turun, FDI 41, SIAGA 1 DICABUT</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 4. STATUS BANNER REAKTIF SIMULASI (§6.1) --}}
    <div 
        id="kode-zona-tertinggi"
        :data-kode="zonaTertinggi.kode"
        :data-nama="zonaTertinggi.nama"
        :data-kategori="zonaTertinggi.kategori"
        :data-cdd="zonaTertinggi.cdd"
        :data-fdi="zonaTertinggi.fdi"
        :class="{
            'bg-danger text-white': zonaTertinggi.kategori === 'BAHAYA',
            'bg-warn text-ink': zonaTertinggi.kategori === 'WASPADA',
            'bg-safe text-white': zonaTertinggi.kategori === 'AMAN'
        }"
        class="p-5 sm:p-6 rounded-3xl shadow-card transition duration-300 space-y-3"
    >
        <div class="flex items-center justify-between text-xs font-mono opacity-90">
            <span class="uppercase tracking-wider" x-text="zonaTertinggi.kode + ' · ' + zonaTertinggi.nama"></span>
            <span class="font-bold">[SIMULASI HARI KE-' + hari + ']</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
            <div>
                <h2 
                    class="text-3xl sm:text-4xl font-display font-bold tracking-tight uppercase"
                    x-text="zonaTertinggi.siaga === 'SIAGA_1' ? 'SIAGA 1' : (zonaTertinggi.siaga === 'SIAGA_OTOMATIS' ? 'SIAGA OTOMATIS' : zonaTertinggi.kategori)"
                ></h2>
                <p class="text-sm opacity-95 mt-1 font-sans">
                    <span x-text="zonaTertinggi.cdd + ' hari tanpa hujan berturut-turut.'"></span>
                    <span x-text="zonaTertinggi.kategori === 'BAHAYA' ? 'Dilarang membuat api terbuka di savana.' : (zonaTertinggi.kategori === 'WASPADA' ? 'Tingkatkan kewaspadaan puntung rokok.' : 'Kondisi kelembapan vegetasi aman.')"></span>
                </p>
            </div>

            <div class="text-left sm:text-right font-mono text-xs opacity-90 shrink-0">
                <span class="block font-bold" x-text="'FDI ' + zonaTertinggi.fdi + '/100'"></span>
                <span x-text="'Angin ' + zonaTertinggi.angin_kmj + ' km/j dari ' + zonaTertinggi.arah_angin"></span>
            </div>
        </div>
    </div>

    {{-- 5. GRID UTAMA: PETA LEAFLET & PANEL ZONA AKTIF (§7.1 & §7.2) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Sisi Kiri: Peta Leaflet Berpoligon 420ms (8 Kolom) --}}
        <div class="lg:col-span-8 space-y-4">
            <div class="bg-paper-2 border border-line rounded-3xl overflow-hidden shadow-card">
                <div class="p-3.5 bg-paper border-b border-line flex items-center justify-between text-xs font-mono">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-forest"></span>
                        <span class="font-bold text-ink">Peta Kawasan Bromo (Transisi Warna 420 ms)</span>
                    </div>
                    <span class="text-ink-muted" x-show="memuatSimulasi">Menghitung risiko...</span>
                    <span class="text-ink-muted" x-show="!memuatSimulasi">Klik poligon untuk telaah zona</span>
                </div>

                {{-- Kontainer Peta --}}
                <div id="petaDemo" class="w-full h-[400px] sm:h-[460px] z-10"></div>

                {{-- Legenda Ringkas Peta (§8) --}}
                <div class="p-3 bg-paper border-t border-line flex flex-wrap items-center justify-between gap-3 text-xs font-mono">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-safe"></span> Aman (FDI 0-40)</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-warn"></span> Waspada (41-79)</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-danger"></span> Bahaya (80-100)</span>
                    </div>
                    <span class="text-ink-muted">Leaflet + GeoJSON 6 Zona</span>
                </div>
            </div>

            {{-- Matriks Seluruh Zona Simulasi (§7.8) --}}
            <div class="p-5 bg-paper-2 border border-line rounded-3xl shadow-card space-y-3">
                <h3 class="text-xs font-mono uppercase font-bold text-forest tracking-wider">
                    Daftar 6 Zona Kawasan Bromo (Hari ke-<span x-text="hari"></span>)
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                    <template x-for="z in zonas" :key="z.kode">
                        <div 
                            @click="pilihZona(z.kode)"
                            :class="zonaTerpilihKode === z.kode ? 'ring-2 ring-forest bg-paper' : 'bg-paper-2 hover:bg-paper'"
                            class="p-3 rounded-2xl border border-line cursor-pointer transition flex flex-col justify-between gap-2 shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-ink" x-text="z.kode"></span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold text-white uppercase"
                                    :class="{
                                        'bg-danger': z.kategori === 'BAHAYA',
                                        'bg-warn': z.kategori === 'WASPADA',
                                        'bg-safe': z.kategori === 'AMAN'
                                    }"
                                    x-text="z.siaga === 'SIAGA_1' ? 'SIAGA 1' : (z.siaga === 'SIAGA_OTOMATIS' ? 'SIAGA' : z.kategori)"
                                ></span>
                            </div>

                            <div>
                                <span class="text-xs font-display font-semibold text-ink line-clamp-1" x-text="z.nama"></span>
                                <div class="flex items-center justify-between text-[11px] font-mono text-ink-muted mt-1">
                                    <span x-text="z.cdd + ' Hari CDD'"></span>
                                    <span class="font-bold text-ink" x-text="'FDI ' + z.fdi + '/100'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Sisi Kanan: Panel Telaah Zona Aktif (4 Kolom Sticky) --}}
        <div class="lg:col-span-4 space-y-4 lg:sticky lg:top-24">
            <div class="p-5 bg-paper-2 border border-line rounded-3xl shadow-card space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-ink-muted uppercase tracking-wider" x-text="zonaAktif.kode + ' · ' + zonaAktif.jenis_lahan"></span>
                        <h2 class="text-lg font-display font-bold text-ink mt-0.5" x-text="zonaAktif.nama"></h2>
                    </div>
                    <span 
                        class="px-2.5 py-1 rounded-full text-xs font-mono font-bold text-white uppercase shrink-0"
                        :class="{
                            'bg-danger': zonaAktif.kategori === 'BAHAYA',
                            'bg-warn': zonaAktif.kategori === 'WASPADA',
                            'bg-safe': zonaAktif.kategori === 'AMAN'
                        }"
                        x-text="zonaAktif.siaga === 'SIAGA_1' ? 'SIAGA 1' : (zonaAktif.siaga === 'SIAGA_OTOMATIS' ? 'SIAGA OTOMATIS' : zonaAktif.kategori)"
                    ></span>
                </div>

                {{-- 2 Metrik Besar --}}
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-3 bg-paper rounded-2xl border border-line">
                        <span class="text-[10px] font-mono text-ink-muted uppercase block">Kekeringan (CDD)</span>
                        <span class="text-2xl font-display font-bold text-ink tabular-nums" x-text="zonaAktif.cdd"></span>
                        <span class="text-[11px] font-mono text-ink-muted block">hari tanpa hujan</span>
                    </div>

                    <div class="p-3 bg-paper rounded-2xl border border-line">
                        <span class="text-[10px] font-mono text-ink-muted uppercase block">Indeks Bahaya Api</span>
                        <span class="text-2xl font-display font-bold text-ink tabular-nums" x-text="zonaAktif.fdi + '/100'"></span>
                        <span class="text-[11px] font-mono text-ink-muted block">skala FDI Bromo</span>
                    </div>
                </div>

                {{-- Batang FDI Meter (§6.4) --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <span class="text-ink-muted">Tingkat Ancaman:</span>
                        <span class="font-bold text-ink" x-text="zonaAktif.fdi + ' %'"></span>
                    </div>
                    <div class="w-full h-2.5 bg-paper rounded-full overflow-hidden border border-line">
                        <div 
                            class="h-full transition-all duration-300"
                            :class="{
                                'bg-danger': zonaAktif.kategori === 'BAHAYA',
                                'bg-warn': zonaAktif.kategori === 'WASPADA',
                                'bg-safe': zonaAktif.kategori === 'AMAN'
                            }"
                            :style="'width: ' + zonaAktif.fdi + '%'"
                        ></div>
                    </div>
                </div>

                {{-- Tiga Batang Kontribusi Faktor (§6.5 & §16.3) --}}
                <div class="space-y-2 pt-2 border-t border-line text-xs font-mono">
                    <span class="text-[11px] text-ink-muted font-bold block uppercase tracking-wider">
                        Rincian Kontribusi Poin (§16.3):
                    </span>

                    {{-- 1. Poin CDD --}}
                    <div class="space-y-0.5">
                        <div class="flex justify-between">
                            <span class="text-ink-muted">CDD (×2,85)</span>
                            <span class="font-bold text-ink" x-text="zonaAktif.poin_cdd + ' poin'"></span>
                        </div>
                        <div class="w-full h-1.5 bg-paper rounded-full overflow-hidden">
                            <div class="h-full bg-forest" :style="'width: ' + Math.min(100, (zonaAktif.poin_cdd / 60) * 100) + '%'"></div>
                        </div>
                    </div>

                    {{-- 2. Poin Kelembapan --}}
                    <div class="space-y-0.5">
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Defisit RH (×0,35)</span>
                            <span class="font-bold text-ink" x-text="zonaAktif.poin_kelembapan + ' poin'"></span>
                        </div>
                        <div class="w-full h-1.5 bg-paper rounded-full overflow-hidden">
                            <div class="h-full bg-forest" :style="'width: ' + Math.min(100, (zonaAktif.poin_kelembapan / 30) * 100) + '%'"></div>
                        </div>
                    </div>

                    {{-- 3. Poin Angin --}}
                    <div class="space-y-0.5">
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Kecepatan Angin (×0,30)</span>
                            <span class="font-bold text-ink" x-text="zonaAktif.poin_angin + ' poin'"></span>
                        </div>
                        <div class="w-full h-1.5 bg-paper rounded-full overflow-hidden">
                            <div class="h-full bg-forest" :style="'width: ' + Math.min(100, (zonaAktif.poin_angin / 20) * 100) + '%'"></div>
                        </div>
                    </div>

                    <div class="pt-2 text-[11px] text-ink-muted">
                        <span class="font-bold text-forest">Penyumbang Utama: </span>
                        <span x-text="zonaAktif.penyumbang_utama"></span>
                    </div>
                </div>

                {{-- Jam Rentan & Prakiraan Proyeksi --}}
                <div class="p-3 bg-paper rounded-2xl border border-line text-xs font-mono space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Jam Rentan Puncak:</span>
                        <span class="font-bold text-danger" x-text="zonaAktif.jam_rentan"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Proyeksi +24j / +48j / +72j:</span>
                        <span class="font-bold text-ink" x-text="zonaAktif.proyeksi_24j + ' / ' + zonaAktif.proyeksi_48j + ' / ' + zonaAktif.proyeksi_72j + ' hr'"></span>
                    </div>
                </div>

                {{-- Tautan Masuk ke Modul Operasional Terkait --}}
                <div class="pt-2">
                    <a 
                        href="{{ route('admin.siaga') }}" 
                        class="w-full min-h-[44px] bg-forest hover:bg-moss text-white rounded-xl font-display font-semibold text-xs flex items-center justify-center gap-2 transition shadow-xs text-center"
                    >
                        <span>Buka Persetujuan Siaga BPBD</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
