@extends('layouts.publik')

@section('judul', 'Jalur Evakuasi & Logistik Air Bromo')

@section('konten')
<div
    x-data="evakuasiPeta({{ Js::from($sumberAirs) }}, {{ Js::from($jalurEvakuasi) }}, '{{ $tabPilihan ?? 'semua' }}')"
    class="space-y-6 pb-24"
>
    {{-- HEADER HALAMAN & KOTAK ANGIN DOMINAN --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ url('/') }}" class="text-xs font-sans text-ink-muted hover:text-ink transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Kembali ke Pantauan</span>
                </a>
                <span class="text-xs text-ink-muted font-sans">•</span>
                <span class="text-xs font-sans text-evac font-bold tracking-normal">Logistik Siaga</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-ink tracking-tight">
                Jalur Evakuasi & Sumber Air
            </h1>
            <p class="text-sm text-ink-muted mt-0.5">
                Referensi logistik tersimpan. Lokasi dan jalur pada halaman ini belum diverifikasi sebagai rute evakuasi resmi.
            </p>
        </div>

        {{-- KOTAK PERHATIAN ANGIN DOMINAN KAWASAN (§7.5 design.md) --}}
        <div class="p-3 bg-paper-2 border border-line rounded-md flex items-center gap-3 shrink-0">

            <div class="text-xs font-sans">
                <span class="text-ink-muted block text-xs font-bold">Angin Dominan Kawasan</span>
                @if ($arahAngin && $kecepatanAngin !== null)
                    <span class="font-display font-bold text-ink text-sm">{{ $kecepatanAngin }} km/j · Dari {{ $arahAngin }}</span>
                    <span class="text-xs text-danger block font-semibold">Data tersimpan, bukan observasi terbaru.</span>
                @else
                    <span class="font-display font-bold text-ink text-sm">Data angin belum tersedia</span>
                @endif
            </div>
        </div>
    </div>

    @if ($adaDataContoh)
        <div class="p-3 rounded-md border border-line bg-paper-2 text-xs font-sans text-ink-muted" role="note">
            <strong class="text-ink">{{ $semuaContoh ? 'Seluruh titik di halaman ini masih data contoh.' : 'Sebagian titik di halaman ini masih data contoh.' }}</strong>
            Lokasi bertanda "Data contoh" belum diverifikasi instansi. Jangan dipakai sebagai rujukan evakuasi sebenarnya.
        </div>
    @endif

    {{-- TITIK TERDEKAT DARI LOKASI PENGGUNA --}}
    <section class="p-4 bg-paper-2 border border-line rounded-lg" aria-labelledby="judul-terdekat">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 id="judul-terdekat" class="font-display font-bold text-base text-ink">Terdekat dari lokasimu</h2>
                <p class="text-xs text-ink-muted mt-0.5">Cari sumber air, pos jaga, dan titik kumpul paling dekat. Lokasimu hanya diproses di perangkat ini.</p>
            </div>
            <button type="button" @click="cariTerdekat()" :disabled="memuatTerdekat" class="min-h-[44px] px-4 py-2 rounded-md bg-forest hover:bg-moss text-white font-display font-bold text-sm shrink-0 disabled:opacity-60">
                <span x-text="memuatTerdekat ? 'Mencari lokasi…' : (terdekat.length ? 'Perbarui lokasi' : 'Cari yang terdekat')"></span>
            </button>
        </div>
        <p class="text-xs text-danger font-semibold mt-3" x-show="kesalahanTerdekat" x-cloak role="alert" x-text="kesalahanTerdekat"></p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3" x-show="terdekat.length" x-cloak role="status" aria-live="polite">
            <template x-for="t in terdekat" :key="t.label">
                <div class="p-3 bg-paper border border-line rounded-md text-xs font-sans space-y-1">
                    <span class="text-ink-muted font-bold block" x-text="t.label"></span>
                    <template x-if="t.titik">
                        <div class="space-y-1">
                            <strong class="font-display text-sm text-ink block" x-text="t.titik.nama"></strong>
                            <span class="text-ink block" x-text="'± ' + t.jarak.toLocaleString('id-ID', {maximumFractionDigits: 1}) + ' km garis lurus'"></span>
                            <span class="text-danger font-semibold block" x-show="t.titik.adalah_contoh">Data contoh, belum diverifikasi</span>
                            <a :href="'https://www.google.com/maps/dir/?api=1&destination=' + t.titik.latitude + ',' + t.titik.longitude" target="_blank" rel="noopener" class="inline-block mt-1 text-forest font-semibold underline">Buka rute di Google Maps</a>
                        </div>
                    </template>
                    <span class="text-ink-muted" x-show="!t.titik">Belum ada data.</span>
                </div>
            </template>
        </div>
    </section>

    {{-- PETA PENUH DENGAN LAPISAN WATER & EVAC (§7.5) --}}
    <div class="bg-paper-2 border border-line rounded-lg overflow-hidden shadow-none">
        <div class="relative w-full aspect-[16/10] sm:aspect-[21/9] min-h-[340px] z-10" id="petaEvakuasi" tabindex="0" role="region" aria-label="Peta Interaktif Jalur Evakuasi dan Sumber Air Bromo">
            <div class="absolute inset-0 bg-paper-3 animate-pulse flex items-center justify-center text-xs font-sans text-ink-muted">
                Memuat peta jalur evakuasi Bromo...
            </div>
        </div>

        {{-- LEGENDA PETA --}}
        <div class="p-3 bg-paper border-t border-line flex flex-wrap items-center justify-between gap-3 text-xs font-sans">
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
                    <span class="w-3 h-3 rounded-md bg-forest"></span>
                    <span>Pos Jaga TNBTS</span>
                </span>
            </div>
            <span class="text-xs text-ink-muted">Ketuk objek pada peta untuk rincian & petunjuk arah</span>
        </div>
    </div>

    {{-- CHIPS FILTER LOGISTIK (TARGET >= 44x44 px) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-sans no-scrollbar">
        <button
            type="button"
            @click="filterJenis('semua')"
            :class="kategoriAktif === 'semua' ? 'bg-forest text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-md font-medium transition active:scale-[0.98] shrink-0"
        >
            Semua Titik ({{ $sumberAirs->count() }})
        </button>

        <button
            type="button"
            @click="filterJenis('embung')"
            :class="kategoriAktif === 'embung' ? 'bg-water text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-md font-medium transition active:scale-[0.98] shrink-0 flex items-center gap-1.5"
        >
            <span> Embung Air</span>
        </button>

        <button
            type="button"
            @click="filterJenis('sumber_air')"
            :class="kategoriAktif === 'sumber_air' ? 'bg-water text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-md font-medium transition active:scale-[0.98] shrink-0 flex items-center gap-1.5"
        >
            <span> Mata Air Alami</span>
        </button>

        <button
            type="button"
            @click="filterJenis('titik_kumpul')"
            :class="kategoriAktif === 'titik_kumpul' ? 'bg-safe text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-md font-medium transition active:scale-[0.98] shrink-0 flex items-center gap-1.5"
        >
            <span> Titik Kumpul</span>
        </button>

        <button
            type="button"
            @click="filterJenis('pos_jaga')"
            :class="kategoriAktif === 'pos_jaga' ? 'bg-forest text-white' : 'bg-paper-2 text-ink-muted hover:text-ink border border-line'"
            class="min-h-[44px] px-4 py-2 rounded-md font-medium transition active:scale-[0.98] shrink-0 flex items-center gap-1.5"
        >
            <span> Pos Logistik</span>
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
                    class="p-4 bg-paper-2 hover:bg-paper-3 border border-line rounded-md shadow-none transition duration-fast cursor-pointer flex flex-col justify-between gap-3 group active:scale-[0.98]"
                >
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-sans text-xs font-bold tracking-normal px-2 py-0.5 rounded-full {{ $sa->jenis === 'titik_kumpul' ? 'bg-safe text-white' : ($sa->jenis === 'pos_jaga' ? 'bg-forest text-white' : 'bg-water text-white') }}">
                                {{ str_replace('_', ' ', $sa->jenis) }}
                            </span>
                            <span class="text-xs font-sans text-ink-muted">{{ $sa->zona?->data_analisis['Label'] ?? $sa->zona?->nama ?? 'Kawasan' }}</span>
                        </div>

                        <h3 class="font-display font-bold text-sm text-ink group-hover:text-forest transition">
                            {{ $sa->nama }}
                        </h3>

                        <div class="mt-2 text-xs font-sans space-y-1 text-ink-muted">
                            <div class="flex items-center justify-between">
                                <span>Debit / Kapasitas:</span>
                                <strong class="text-ink">{{ $sa->debit_air ?: 'Melimpah' }}</strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Status Logistik:</span>
                                <span class="font-semibold text-safe">{{ $sa->status_siaga }}</span>
                            </div>
                            <div class="pt-1">
                                @if ($sa->adalah_contoh)
                                    <span class="inline-block px-2 py-0.5 rounded-full border border-danger text-danger font-semibold">Data contoh</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-full border border-line text-ink font-semibold">Sumber: {{ $sa->sumber_data }}@if ($sa->tanggal_verifikasi) · diverifikasi {{ $sa->tanggal_verifikasi->translatedFormat('j M Y') }}@endif</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-line flex items-center justify-between text-xs font-sans text-moss group-hover:underline">
                        <span>Lihat di peta</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- EMERGENCY BAR STICKY DI BAWAH (§6.7 & §7.5) --}}
    <div class="fixed bottom-16 lg:bottom-4 inset-x-4 max-w-xl mx-auto z-40 bg-paper/95 backdrop-blur-md p-3 rounded-lg border border-line shadow-sheet">
        <div class="grid grid-cols-2 gap-3">
            {{-- Tombol Telepon 112 (Outline 48px) --}}
            <a
                href="tel:112"
                class="min-h-[48px] px-4 py-2.5 rounded-md border-2 border-danger text-danger hover:bg-danger hover:text-white font-display font-bold text-sm flex items-center justify-center gap-2 transition active:scale-[0.98] text-center shadow-none"
                aria-label="Panggil Darurat BPBD 112"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span>Telepon 112</span>
            </a>

            {{-- Tombol Bagikan Lokasi Saya (Solid 48px) --}}
            <button
                type="button"
                @click="bagikanLokasiSaya()"
                class="min-h-[48px] px-4 py-2.5 rounded-md bg-forest hover:bg-moss text-white font-display font-bold text-sm flex items-center justify-center gap-2 transition active:scale-[0.98] text-center shadow-none"
                aria-label="Kirim koordinat GPS saya saat ini"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Bagikan lokasi</span>
            </button>
        </div>
    </div>
</div>

<script>
function evakuasiPeta(sumberAirsData, jalurData, tabPilihan) {
    return {
        kategoriAktif: tabPilihan || 'semua',
        sumberAirs: sumberAirsData || [],
        jalurData: jalurData || [],
        mapInstance: null,
        userLocation: null,
        terdekat: [],
        memuatTerdekat: false,
        kesalahanTerdekat: '',
        markers: [],
        lines: [],

        init() {
            this.$nextTick(() => {
                this.inisialisasiPeta();
            });
        },

        inisialisasiPeta() {
            if (!window.L) return;
            const mapEl = document.getElementById('petaEvakuasi');
            if (!mapEl) return;

            // Inisialisasi peta berpusat di Kaldera Bromo
            const map = window.L.map('petaEvakuasi', {
                center: [-7.948, 112.946],
                zoom: 12,
                zoomControl: false,
                attributionControl: true,
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
                }).bindTooltip('Jalur Evakuasi: ' + j.nama, {
                    permanent: false,
                    className: 'font-sans text-xs',
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

                if (sa.jenis === 'titik_kumpul') {
                    iconHtml = '<div class="w-7 h-7 rounded-full bg-safe border-2 border-white text-white flex items-center justify-center font-bold text-xs shadow-md"></div>';
                } else if (sa.jenis === 'pos_jaga') {
                    iconHtml = '<div class="w-7 h-7 rounded-md bg-forest border-2 border-white text-white flex items-center justify-center font-bold text-xs shadow-md"></div>';
                } else {
                    iconHtml = '<div class="w-7 h-7 rounded-full bg-water border-2 border-white text-white flex items-center justify-center shadow-md"><span class="text-xs"></span></div>';
                }

                const customIcon = window.L.divIcon({
                    className: 'marker-logistik',
                    html: iconHtml,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });

                const badgeClass = sa.jenis === 'titik_kumpul' ? 'bg-safe' : (sa.jenis === 'pos_jaga' ? 'bg-forest' : 'bg-water');
                const m = window.L.marker([sa.latitude, sa.longitude], { icon: customIcon })
                    .bindPopup(`
                        <div class="p-1 text-xs font-sans space-y-1">
                            <span class="px-1.5 py-0.5 rounded text-xs font-bold text-white ${badgeClass}">
                                ${sa.jenis.replace('_', ' ')}
                            </span>
                            <h4 class="font-bold text-ink text-sm">${sa.nama}</h4>
                            <p class="text-ink-muted">Debit/Kapasitas: ${sa.debit_air || '-'}</p>
                            <p class="text-ink font-semibold">Status: ${sa.status_siaga || 'Siaga'}</p>
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${sa.latitude},${sa.longitude}" target="_blank" class="inline-block text-moss underline font-bold mt-1">
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
                const targetMarker = this.markers.find(m => {
                    const pos = m.getLatLng();
                    return Math.abs(pos.lat - lat) < 0.0001 && Math.abs(pos.lng - lng) < 0.0001;
                });
                if (targetMarker) {
                    targetMarker.openPopup();
                }
            }
        },

        jarakKm(lat1, lng1, lat2, lng2) {
            const rad = (d) => d * Math.PI / 180;
            const a = Math.sin(rad(lat2 - lat1) / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(rad(lng2 - lng1) / 2) ** 2;
            return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },

        cariTerdekat() {
            this.kesalahanTerdekat = '';
            if (!navigator.geolocation) { this.kesalahanTerdekat = 'Perangkat ini tidak mendukung GPS.'; return; }
            this.memuatTerdekat = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const { latitude: lat, longitude: lng } = pos.coords;
                    const kelompok = [
                        ['Sumber air terdekat', ['embung', 'sumber_air']],
                        ['Pos jaga terdekat', ['pos_jaga']],
                        ['Titik kumpul terdekat', ['titik_kumpul']],
                    ];
                    this.terdekat = kelompok.map(([label, jenis]) => {
                        let terbaik = null; let min = Infinity;
                        this.sumberAirs.filter(s => jenis.includes(s.jenis)).forEach(s => {
                            const d = this.jarakKm(lat, lng, Number(s.latitude), Number(s.longitude));
                            if (d < min) { min = d; terbaik = s; }
                        });
                        return { label, titik: terbaik, jarak: min };
                    });
                    this.memuatTerdekat = false;
                },
                (err) => {
                    this.memuatTerdekat = false;
                    this.kesalahanTerdekat = { 1: 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser.', 2: 'Lokasi tidak tersedia. Coba pindah ke area terbuka.', 3: 'Pencarian lokasi terlalu lama. Coba lagi.' }[err.code] || 'Gagal mendapatkan lokasi.';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
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
                                <div class="relative flex items-center justify-center w-8 h-8">
                                    <div class="anim-denyut-api absolute w-8 h-8 bg-forest/30 rounded-full"></div>
                                    <div class="w-4 h-4 bg-forest border-2 border-white rounded-full shadow-none"></div>
                                </div>
                            `,
                            iconSize: [32, 32],
                            iconAnchor: [16, 16],
                        });

                        window.L.marker([lat, lng], { icon: userIcon })
                            .bindPopup('<div class="text-xs font-sans p-1 font-bold text-forest"> Posisi Anda Saat Ini</div>')
                            .addTo(this.mapInstance)
                            .openPopup();

                        this.mapInstance.flyTo([lat, lng], 13);
                    }

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
    };
}
</script>
@endsection
