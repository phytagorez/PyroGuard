@extends('layouts.publik')

@section('judul', 'Jalur Evakuasi & Logistik Air Bromo')

@section('konten')
<div 
    x-data="{
        kategoriAktif: '{{ $tabPilihan ?? 'semua' }}',
        sumberAirs: {{ Js::from($sumberAirs) }},
        jalurData: {{ Js::from($jalurEvakuasi) }},
        mapInstance: null,
        userLocation: null,
        markers: [],
        lines: [],

        init() {
            this.$nextTick(() => {
                this.inisialisasiPeta();
            });
        },

        inisialisasiPeta() {
            if (!window.L) return;

            // Inisialisasi peta berpusat di Kaldera Bromo
            const map = window.L.map('petaEvakuasi', {
                center: [-7.948, 112.946],
                zoom: 12,
                zoomControl: false,
                attributionControl: false,
            });

            this.mapInstance = map;
            window.L.control.zoom({ position: 'topright' }).addTo(map);

            // Layer Tile OSM
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap',
            }).addTo(map);

            // Render Jalur Evakuasi GeoJSON (Garis Ungu Putus-Putus --evac #6D4C9A)
            this.jalurData.forEach(j => {
                const polyline = window.L.geoJSON(j.geojson, {
                    style: {
                        color: '#6D4C9A',
                        weight: 4,
                        opacity: 0.9,
                        dashArray: '8, 6',
                    }
                }).bindTooltip(`Jalur Evakuasi: ${j.nama}`, {
                    permanent: false,
                    className: 'font-mono text-xs',
                }).addTo(map);

                this.lines.push(polyline);
            });

            // Render Marker Sumber Air & Titik Kumpul
            this.renderMarkers();
        },

        renderMarkers() {
            // Bersihkan marker lama
            this.markers.forEach(m => m.remove());
            this.markers = [];

            const list = this.kategoriAktif === 'semua' 
                ? this.sumberAirs 
                : this.sumberAirs.filter(s => s.jenis === this.kategoriAktif);

            list.forEach(sa => {
                let iconHtml = '';
                let markerClass = '';

                if (sa.jenis === 'titik_kumpul') {
                    iconHtml = `<div class='w-7 h-7 rounded-full bg-safe border-2 border-white text-white flex items-center justify-center font-bold text-xs shadow-md'>🏁</div>`;
                } else if (sa.jenis === 'pos_jaga') {
                    iconHtml = `<div class='w-7 h-7 rounded-lg bg-forest border-2 border-white text-white flex items-center justify-center font-bold text-xs shadow-md'>🏢</div>`;
                } else {
                    // embung / sumber_air
                    iconHtml = `<div class='w-7 h-7 rounded-full bg-water border-2 border-white text-white flex items-center justify-center shadow-md'><span class='text-xs'>💧</span></div>`;
                }

                const customIcon = window.L.divIcon({
                    className: 'marker-logistik',
                    html: iconHtml,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });

                const m = window.L.marker([sa.latitude, sa.longitude], { icon: customIcon })
                    .bindPopup(`
                        <div class='p-1 text-xs font-mono space-y-1'>
                            <span class='px-1.5 py-0.5 rounded text-[10px] uppercase font-bold text-white ${sa.jenis === 'titik_kumpul' ? 'bg-safe' : 'bg-water'}'>
                                ${sa.jenis.replace('_', ' ')}
                            </span>
                            <h4 class='font-bold text-ink text-sm'>${sa.nama}</h4>
                            <p class='text-ink-muted'>Debit/Kapasitas: ${sa.debit_air || '-'}</p>
                            <p class='text-ink font-semibold'>Status: ${sa.status_siaga || 'Siaga'}</p>
                            <a href='https://www.google.com/maps/dir/?api=1&destination=${sa.latitude},${sa.longitude}' target='_blank' class='inline-block text-moss underline font-bold mt-1'>
                                Petunjuk Arah Google Maps →
                            </a>
                        </div>
                    `)
                    .addTo(this.mapInstance);

                this.markers.push(m);
            });
        },

        filterJenis(jenis) {
            this.kategoriAktif = jenis;
            this.renderMarkers();
        },

        fokusLokasi(lat, lng) {
            if (this.mapInstance) {
                this.mapInstance.flyTo([lat, lng], 14, { duration: 1.2 });
                // Cari marker terdekat dan buka popupnya
                const targetMarker = this.markers.find(m => {
                    const pos = m.getLatLng();
                    return Math.abs(pos.lat - lat) < 0.0001 && Math.abs(pos.lng - lng) < 0.0001;
                });
                if (targetMarker) {
                    targetMarker.openPopup();
                }
            }
        },

        bagikanLokasiSaya() {
            if (!navigator.geolocation) {
                alert('Perangkat Anda tidak mendukung geolokasi GPS.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.userLocation = { lat, lng };

                    if (this.mapInstance) {
                        const userIcon = window.L.divIcon({
                            className: 'marker-user',
                            html: `
                                <div class='relative flex items-center justify-center w-8 h-8'>
                                    <div class='anim-denyut-api absolute w-8 h-8 bg-forest/30 rounded-full'></div>
                                    <div class='w-4 h-4 bg-forest border-2 border-white rounded-full shadow-lg'></div>
                                </div>
                            `,
                            iconSize: [32, 32],
                            iconAnchor: [16, 16],
                        });

                        window.L.marker([lat, lng], { icon: userIcon })
                            .bindPopup(`<div class='text-xs font-mono p-1 font-bold text-forest'>📍 Posisi Anda Saat Ini</div>`)
                            .addTo(this.mapInstance)
                            .openPopup();

                        this.mapInstance.flyTo([lat, lng], 13);
                    }

                    // Buat tautan bagikan WhatsApp/SMS
                    const mapsLink = 'https://maps.google.com/?q=' + lat + ',' + lng;
                    if (navigator.share) {
                        navigator.share({
                            title: 'Posisi Darurat Saya di Bromo',
                            text: 'Saya sedang berada di kawasan Bromo. Posisi GPS saya: ' + mapsLink,
                            url: mapsLink,
                        }).catch(() => {});
                    } else {
                        navigator.clipboard.writeText(mapsLink);
                        alert('Tautan koordinat Anda berhasil disalin ke clipboard: ' + mapsLink);
                    }
                },
                (err) => {
                    alert('Gagal mengakses GPS: ' + err.message + '. Pastikan izin lokasi diaktifkan.');
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    }"
    class="space-y-6 pb-24"
>
    {{-- HEADER HALAMAN & KOTAK ANGIN DOMINAN --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ url('/') }}" class="text-xs font-mono text-ink-muted hover:text-ink transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Kembali ke Pantauan</span>
                </a>
                <span class="text-xs text-ink-muted font-mono">•</span>
                <span class="text-xs font-mono text-evac font-bold uppercase tracking-wider">Logistik Siaga</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-ink tracking-tight">
                Jalur Evakuasi & Sumber Air
            </h1>
            <p class="text-sm text-ink-muted mt-0.5">
                Peta akses cepat pasokan air pemadam kebakaran lahan dan titik kumpul darurat kawasan Bromo.
            </p>
        </div>

        {{-- KOTAK PERHATIAN ANGIN DOMINAN KAWASAN (§7.5 design.md) --}}
        <div class="p-3 bg-paper-2 border border-line rounded-xl flex items-center gap-3 shrink-0">
            <div class="w-10 h-10 rounded-lg bg-forest/10 text-forest flex items-center justify-center font-bold text-lg">
                🧭
            </div>
            <div class="text-xs font-mono">
                <span class="text-ink-muted block text-[10px] uppercase font-bold">Angin Dominan Kawasan</span>
                <span class="font-display font-bold text-ink text-sm">
                    {{ $kecepatanAngin }} km/j · Dari {{ $arahAngin }}
                </span>
                <span class="text-[10px] text-danger block">Evakuasi berlawanan arah asap!</span>
            </div>
        </div>
    </div>

    {{-- PETA PENUH DENGAN LAPISAN WATER & EVAC (§7.5) --}}
    <div class="bg-paper-2 border border-line rounded-2xl overflow-hidden shadow-card">
        <div class="relative w-full aspect-[16/10] sm:aspect-[21/9] min-h-[340px] z-10" id="petaEvakuasi" tabindex="0" role="region" aria-label="Peta Interaktif Jalur Evakuasi dan Sumber Air Bromo">
            <div class="absolute inset-0 bg-paper-3 animate-pulse flex items-center justify-center text-xs font-mono text-ink-muted">
                Memuat peta jalur evakuasi Bromo...
            </div>
        </div>

        {{-- LEGENDA PETA --}}
        <div class="p-3 bg-paper border-t border-line flex flex-wrap items-center justify-between gap-3 text-xs font-mono">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3.5 h-0.5 bg-evac border-b border-dashed border-evac"></span>
                    <span>Jalur Evakuasi</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-water"></span>
                    <span>Embung & Sumber Air</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-safe"></span>
                    <span>Titik Kumpul Aman</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-lg bg-forest"></span>
                    <span>Pos Jaga TNBTS</span>
                </span>
            </div>
            <span class="text-[10px] text-ink-muted">Ketuk objek pada peta untuk rincian & petunjuk arah</span>
        </div>
    </div>

    {{-- CHIPS FILTER LOGISTIK (TARGET >= 44x44 px) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-mono no-scrollbar">
        <button 
            type="button" 
            @click="filterJenis('semua')"
            :class="kategoriAktif === 'semua' ? 'bg-forest text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-xl font-medium transition active:scale-95 shrink-0"
        >
            Semua Titik ({{ $sumberAirs->count() }})
        </button>

        <button 
            type="button" 
            @click="filterJenis('embung')"
            :class="kategoriAktif === 'embung' ? 'bg-water text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-xl font-medium transition active:scale-95 shrink-0 flex items-center gap-1.5"
        >
            <span>💧 Embung Air</span>
        </button>

        <button 
            type="button" 
            @click="filterJenis('sumber_air')"
            :class="kategoriAktif === 'sumber_air' ? 'bg-water text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-xl font-medium transition active:scale-95 shrink-0 flex items-center gap-1.5"
        >
            <span>🚰 Mata Air Alami</span>
        </button>

        <button 
            type="button" 
            @click="filterJenis('titik_kumpul')"
            :class="kategoriAktif === 'titik_kumpul' ? 'bg-safe text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-xl font-medium transition active:scale-95 shrink-0 flex items-center gap-1.5"
        >
            <span>🏁 Titik Kumpul</span>
        </button>

        <button 
            type="button" 
            @click="filterJenis('pos_jaga')"
            :class="kategoriAktif === 'pos_jaga' ? 'bg-forest text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-xl font-medium transition active:scale-95 shrink-0 flex items-center gap-1.5"
        >
            <span>🏢 Pos Logistik</span>
        </button>
    </div>

    {{-- DAFTAR TITIK LOGISTIK & SUMBER AIR --}}
    <div class="space-y-3">
        <h2 class="font-display font-bold text-lg text-ink">
            Daftar Logistik & Sumber Air di Kawasan Bromo
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($sumberAirs as $sa)
                <div 
                    @click="fokusLokasi({{ $sa->latitude }}, {{ $sa->longitude }})"
                    tabindex="0"
                    role="button"
                    class="p-4 bg-paper-2 hover:bg-paper-3 border border-line rounded-xl shadow-card transition duration-fast cursor-pointer flex flex-col justify-between gap-3 group"
                >
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-mono text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded {{ $sa->jenis === 'titik_kumpul' ? 'bg-safe text-white' : ($sa->jenis === 'pos_jaga' ? 'bg-forest text-white' : 'bg-water text-white') }}">
                                {{ str_replace('_', ' ', $sa->jenis) }}
                            </span>
                            <span class="text-xs font-mono text-ink-muted">{{ $sa->zona?->kode ?? 'Kawasan' }}</span>
                        </div>

                        <h3 class="font-display font-bold text-sm text-ink group-hover:text-forest transition">
                            {{ $sa->nama }}
                        </h3>

                        <div class="mt-2 text-xs font-mono space-y-1 text-ink-muted">
                            <div class="flex items-center justify-between">
                                <span>Debit / Kapasitas:</span>
                                <strong class="text-ink">{{ $sa->debit_air ?: 'Melimpah' }}</strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Status Logistik:</span>
                                <span class="font-semibold text-safe">{{ $sa->status_siaga }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-line flex items-center justify-between text-xs font-mono text-moss group-hover:underline">
                        <span>Lihat di peta</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- EMERGENCY BAR STICKY DI BAWAH (§6.7 & §7.5) --}}
    <div class="fixed bottom-16 lg:bottom-4 inset-x-4 max-w-xl mx-auto z-40 bg-paper/95 backdrop-blur-md p-3 rounded-2xl border-2 border-line shadow-sheet">
        <div class="grid grid-cols-2 gap-3">
            {{-- Tombol Telepon 112 (Outline 48px) --}}
            <a 
                href="tel:112"
                class="min-h-[48px] px-4 py-2.5 rounded-xl border-2 border-danger text-danger hover:bg-danger hover:text-white font-display font-bold text-sm flex items-center justify-center gap-2 transition active:scale-95 text-center shadow-sm"
                aria-label="Panggil Darurat BPBD 112"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span>Telepon 112</span>
            </a>

            {{-- Tombol Bagikan Lokasi Saya (Solid 48px) --}}
            <button 
                type="button"
                @click="bagikanLokasiSaya()"
                class="min-h-[48px] px-4 py-2.5 rounded-xl bg-forest hover:bg-moss text-white font-display font-bold text-sm flex items-center justify-center gap-2 transition active:scale-95 text-center shadow-sm"
                aria-label="Kirim koordinat GPS saya saat ini"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Bagikan lokasi</span>
            </button>
        </div>
    </div>
</div>
@endsection
