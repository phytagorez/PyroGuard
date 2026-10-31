@extends('layouts.publik')

@section('judul', 'Pantauan Risiko Kebakaran Bromo')

@section('konten')
<div 
    x-data="{
        lapisanAktif: {
            zonasi: true,
            titikPanas: true,
            logistik: false,
        },
        zonaPilihan: '{{ $zonaTertinggi?->kode ?? 'BRM-04' }}',
        mapInstance: null,
        zoneLayers: {},
        hotspotLayers: [],
        logistikLayers: [],

        init() {
            this.$nextTick(() => {
                this.inisialisasiPeta();
            });
        },

        inisialisasiPeta() {
            if (!window.L) return;

            // Inisialisasi peta Leaflet berpusat di Kaldera Bromo
            const map = window.L.map('petaPantauan', {
                center: [-7.948, 112.946],
                zoom: 12,
                zoomControl: false,
                attributionControl: false,
            });

            this.mapInstance = map;

            // Tombol zoom di pojok kanan atas
            window.L.control.zoom({ position: 'topright' }).addTo(map);

            // Layer Tile OSM
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap',
            }).addTo(map);

            // Inisialisasi Pola Garis SVG untuk Aksesibilitas Buta Warna (§2 P2 & §5.1)
            const svg = map.getPanes().overlayPane.querySelector('svg');
            if (svg) {
                const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                defs.innerHTML = `
                    <pattern id='pola-bahaya' width='8' height='8' patternTransform='rotate(45 0 0)' patternUnits='userSpaceOnUse'>
                        <line x1='0' y1='0' x2='0' y2='8' stroke='#9E2B22' stroke-width='2' />
                    </pattern>
                    <pattern id='pola-siaga' width='6' height='6' patternTransform='rotate(45 0 0)' patternUnits='userSpaceOnUse'>
                        <line x1='0' y1='0' x2='0' y2='6' stroke='#7F1D1D' stroke-width='2.5' />
                    </pattern>
                `;
                svg.appendChild(defs);
            }

            // Gambar Poligon 6 Zona Bromo
            const zonasData = {{ Js::from($zonas) }};
            zonasData.forEach(zona => {
                const risiko = zona.risiko || {};
                const status = risiko.status_siaga === 'SIAGA_1' ? 'SIAGA 1' : (risiko.kategori || 'AMAN');

                // Warna Poligon (§5.1 design.md)
                const styleWarna = this.getStyleZona(status);

                const geoJsonLayer = window.L.geoJSON(zona.poligon_geojson, {
                    style: {
                        color: styleWarna.stroke,
                        weight: 2,
                        opacity: 0.9,
                        fillColor: styleWarna.fill,
                        fillOpacity: 0.55,
                    },
                    onEachFeature: (feature, layer) => {
                        // Label pil permanen mono (§8 design.md)
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

            // Gambar Titik Panas FIRMS (§8 - Belah Ketupat Merah)
            const hotspotsData = {{ Js::from($hotspots) }};
            hotspotsData.forEach(hp => {
                const diamondIcon = window.L.divIcon({
                    className: 'custom-hotspot-marker',
                    html: `
                        <div class='relative flex items-center justify-center w-6 h-6'>
                            <div class='anim-denyut-api absolute w-6 h-6 bg-red-600/50 rounded-sm rotate-45'></div>
                            <div class='w-3.5 h-3.5 bg-hotspot border-2 border-white rounded-sm rotate-45 shadow-sm'></div>
                        </div>
                    `,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12],
                });

                const marker = window.L.marker([hp.latitude, hp.longitude], { icon: diamondIcon })
                    .bindPopup(`
                        <div class='text-xs font-mono p-1'>
                            <strong class='text-danger block'>TITIK PANAS SATELIT</strong>
                            <span>Kecerahan: ${hp.kecerahan_kelvin || hp.kecerahan} K</span><br>
                            <span>Satelit: ${hp.sumber_satelit}</span>
                        </div>
                    `);

                this.hotspotLayers.push(marker);
                if (this.lapisanAktif.titikPanas) {
                    marker.addTo(map);
                }
            });

            // Gambar Sumber Air & Embung (§8 - Lingkaran Biru Tetes Air)
            const sumberAirData = {{ Js::from($sumberAirs) }};
            sumberAirData.forEach(sa => {
                const waterIcon = window.L.divIcon({
                    className: 'custom-water-marker',
                    html: `
                        <div class='w-5 h-5 rounded-full bg-water border-2 border-white text-white flex items-center justify-center shadow-sm'>
                            <span class='text-[9px]'>💧</span>
                        </div>
                    `,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                });

                const marker = window.L.marker([sa.latitude, sa.longitude], { icon: waterIcon })
                    .bindPopup(`
                        <div class='text-xs font-mono p-1'>
                            <strong class='text-water block'>${sa.nama}</strong>
                            <span>Jenis: ${sa.jenis}</span><br>
                            <span>Status: ${sa.status_siaga}</span>
                        </div>
                    `);

                this.logistikLayers.push(marker);
            });
        },

        getStyleZona(status) {
            return matchStatus = {
                'SIAGA 1': { stroke: '#7F1D1D', fill: '#7F1D1D' },
                'SIAGA OTOMATIS': { stroke: '#9E2B22', fill: '#D9544A' },
                'BAHAYA': { stroke: '#9E2B22', fill: '#D9544A' },
                'WASPADA': { stroke: '#A86412', fill: '#FBE3A6' },
                'AMAN': { stroke: '#1F6B45', fill: '#CFE6D6' },
                'DICABUT': { stroke: '#4C5A52', fill: '#EDE7DA' },
            }[status] || { stroke: '#1F6B45', fill: '#CFE6D6' };
        },

        toggleLapisan(nama) {
            this.lapisanAktif[nama] = !this.lapisanAktif[nama];
            
            if (nama === 'titikPanas') {
                this.hotspotLayers.forEach(m => this.lapisanAktif.titikPanas ? m.addTo(this.mapInstance) : m.remove());
            } else if (nama === 'logistik') {
                this.logistikLayers.forEach(m => this.lapisanAktif.logistik ? m.addTo(this.mapInstance) : m.remove());
            } else if (nama === 'zonasi') {
                Object.values(this.zoneLayers).forEach(l => this.lapisanAktif.zonasi ? l.addTo(this.mapInstance) : l.remove());
            }
        },

        pilihZona(kode) {
            this.zonaPilihan = kode;
            $dispatch('buka-panel-zona', { kode: kode });
            
            const targetLayer = this.zoneLayers[kode];
            if (targetLayer && this.mapInstance) {
                this.mapInstance.fitBounds(targetLayer.getBounds(), { padding: [20, 20], maxZoom: 13 });
            }
        }
    }"
    class="space-y-6"
>
    {{-- 1. STATUS BANNER ZONA TERTINGGI (§6.1 & §7.1 - SELALU DI ATAS SEBELUM PETA) --}}
    <section aria-label="Status Kawasan Tertinggi">
        <x-komponen.banner-status :zonaTertinggi="$zonaTertinggi" :kesegaran="$kesegaran" />
    </section>

    {{-- GRID DESKTOP: PETA (8 KOLOM) + PANEL ZONA KANAN (4 KOLOM) (§4 & §7.1) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- KOLOM KIRI (PETA & DAFTAR KARTU) --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- 2. PETA LEAFLET 16:10 DENGAN 6 POLIGON ZONA BROMO (§8) --}}
            <div class="bg-paper-2 border border-line rounded-2xl overflow-hidden shadow-card">
                <div class="relative w-full aspect-[16/10] min-h-[300px] z-10" id="petaPantauan" tabindex="0" role="region" aria-label="Peta Interaktif Pantauan Risiko Kebakaran 6 Zona Kawasan Bromo">
                    {{-- Skeleton Loader Jika Peta Sedang Dimuat --}}
                    <div class="absolute inset-0 bg-paper-3 animate-pulse flex items-center justify-center text-xs font-mono text-ink-muted">
                        Memuat peta risiko kawasan Bromo...
                    </div>
                </div>

                {{-- 3. LAYER SWITCH (3 SAKELAR 44px SESUAI §6.12 & §7.1) --}}
                <div class="p-3 bg-paper border-t border-line flex flex-wrap items-center justify-between gap-2 text-xs font-mono">
                    <span class="text-ink-muted uppercase font-bold text-[10px] hidden sm:inline-block">Lapisan:</span>
                    <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                        <button 
                            type="button" 
                            @click="toggleLapisan('zonasi')"
                            :class="lapisanAktif.zonasi ? 'bg-forest text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
                            class="min-h-[44px] px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-medium transition active:scale-95"
                            aria-pressed="true"
                        >
                            <span>🗺️ Zonasi Risiko</span>
                        </button>

                        <button 
                            type="button" 
                            @click="toggleLapisan('titikPanas')"
                            :class="lapisanAktif.titikPanas ? 'bg-forest text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
                            class="min-h-[44px] px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-medium transition active:scale-95"
                        >
                            <span>🔥 Titik Panas & Laporan</span>
                        </button>

                        <button 
                            type="button" 
                            @click="toggleLapisan('logistik')"
                            :class="lapisanAktif.logistik ? 'bg-forest text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
                            class="min-h-[44px] px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-medium transition active:scale-95"
                        >
                            <span>💧 Logistik & Air</span>
                        </button>
                    </div>

                    {{-- Legenda Ringkas (§8) --}}
                    <div class="flex items-center gap-2 text-[10px] text-ink-muted pt-1 sm:pt-0">
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-safe"></span> Aman</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-warn"></span> Waspada</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-danger"></span> Bahaya</span>
                    </div>
                </div>
            </div>

            {{-- 4. DUA TOMBOL BERJAJAR: JALUR EVAKUASI & SUMBER AIR (§7.1) --}}
            <div class="grid grid-cols-2 gap-3">
                <a 
                    href="{{ url('/evakuasi') }}" 
                    class="h-12 flex items-center justify-center gap-2 p-3 bg-paper-2 hover:bg-paper-3 border border-line rounded-xl text-xs sm:text-sm font-semibold text-ink transition active:scale-98 shadow-sm"
                >
                    <svg class="w-4 h-4 text-evac shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Jalur Evakuasi</span>
                </a>
                <a 
                    href="{{ url('/evakuasi') }}?tab=air" 
                    class="h-12 flex items-center justify-center gap-2 p-3 bg-paper-2 hover:bg-paper-3 border border-line rounded-xl text-xs sm:text-sm font-semibold text-ink transition active:scale-98 shadow-sm"
                >
                    <svg class="w-4 h-4 text-water shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    <span>Sumber Air Terdekat</span>
                </a>
            </div>

            {{-- 5. DAFTAR KARTU RINGKASAN 6 ZONA (TERURUT RISIKO) (§7.1) --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-display font-bold text-base sm:text-lg text-ink">
                        Ringkasan 6 Zona Pemantauan Bromo
                    </h3>
                    <span class="text-xs font-mono text-ink-muted">Ketuk kartu untuk analisis mendalam</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="list">
                    @foreach ($zonas as $zona)
                        @php
                            $r = $zona->risikoTerbaru;
                            $kategori = $r?->status_siaga === 'SIAGA_1' ? 'SIAGA 1' : ($r?->kategori_risiko ?? 'AMAN');
                            $cdd = $r?->hari_tanpa_hujan ?? 0;
                            $fdi = $r?->indeks_bahaya_api ?? 0;
                        @endphp
                        <div 
                            @click="pilihZona('{{ $zona->kode }}')"
                            tabindex="0"
                            @keydown.enter="pilihZona('{{ $zona->kode }}')"
                            role="button"
                            aria-label="Zona {{ $zona->nama }}, status {{ $kategori }}, {{ $cdd }} hari tanpa hujan"
                            class="p-4 bg-paper-2 hover:bg-paper-3 border border-line rounded-xl shadow-card transition duration-fast cursor-pointer flex items-center justify-between gap-3 group"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider">{{ $zona->kode }}</span>
                                    <x-komponen.lencana-risiko :status="$kategori" varian="soft" ukuran="sm" />
                                </div>
                                <h4 class="font-display font-bold text-sm text-ink truncate group-hover:text-forest transition">
                                    {{ $zona->nama }}
                                </h4>
                                <div class="flex items-center gap-2 mt-1 text-xs font-mono text-ink-muted">
                                    <span>{{ $cdd }} hari kering</span>
                                    <span>•</span>
                                    <span>FDI <strong class="text-ink">{{ $fdi }}</strong>/100</span>
                                </div>
                            </div>

                            <svg class="w-5 h-5 text-ink-muted group-hover:text-ink group-hover:translate-x-1 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 6. KARTU "BUKTI LAPANGAN" (§7.1) --}}
            <div class="p-4 sm:p-5 bg-paper-2 border border-line rounded-2xl shadow-card space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider">
                        Bukti Lapangan 72 Jam Terakhir
                    </span>
                    <span class="text-xs font-mono text-ink-muted">Satelit & Verifikasi</span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs font-mono">
                    <div class="p-3 bg-paper rounded-xl border border-line">
                        <span class="text-ink-muted block text-[11px] mb-1">Titik Panas Satelit FIRMS:</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-display font-bold text-danger">{{ $hotspots->count() }}</span>
                            <span class="text-ink-muted text-[10px]">terdeteksi</span>
                        </div>
                        <span class="text-[10px] text-ink-muted block mt-1">MODIS / VIIRS NASA</span>
                    </div>

                    <div class="p-3 bg-paper rounded-xl border border-line">
                        <span class="text-ink-muted block text-[11px] mb-1">Laporan Api Terverifikasi:</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-display font-bold text-forest">{{ $laporanTerverifikasi->count() }}</span>
                            <span class="text-ink-muted text-[10px]">tiket api nyata</span>
                        </div>
                        <span class="text-[10px] text-ink-muted block mt-1">Dicek Petugas TNBTS</span>
                    </div>
                </div>
            </div>

            {{-- 7. FRESHNESS STAMP & TAUTAN METODE (§6.6 & §7.1) --}}
            <div class="pt-2 pb-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs font-mono text-ink-muted">
                <span>{{ $kesegaran['label'] }}</span>
                <a href="{{ url('/metode') }}" class="underline hover:text-ink transition">
                    Metode & Keterbatasan Formula →
                </a>
            </div>
        </div>

        {{-- KOLOM KANAN: ZONE PANEL STICKY DI DESKTOP (§4 & §6.3) --}}
        <div class="lg:col-span-4 lg:sticky lg:top-20">
            <x-komponen.panel-zona :zonas="$zonas" :zonaAwal="$zonaTertinggi" />
        </div>
    </div>

    {{-- 8. FAB LAPOR ASAP STICKY MOBILE (§6.7) --}}
    <x-komponen.tombol-lapor-fab />
</div>
@endsection
