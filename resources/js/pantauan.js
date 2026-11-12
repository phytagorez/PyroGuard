import L from 'leaflet';

const textNode = (text) => { const el = document.createElement('span'); el.textContent = text; return el; };

function titikDalamRing(titik, ring) {
    const [lng, lat] = titik;
    let dalam = false;

    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [xi, yi] = ring[i];
        const [xj, yj] = ring[j];

        const memotong =
            (yi > lat) !== (yj > lat) &&
            lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi;

        if (memotong) dalam = !dalam;
    }

    return dalam;
}

function titikDalamPolygon(titik, polygon) {
    if (!titikDalamRing(titik, polygon[0])) return false;

    // Ring berikutnya adalah lubang di dalam polygon
    for (let i = 1; i < polygon.length; i++) {
        if (titikDalamRing(titik, polygon[i])) return false;
    }

    return true;
}

function titikDalamGeoJSON(titik, geometry) {
    if (!geometry) return false;

    if (geometry.type === 'Polygon') {
        return titikDalamPolygon(titik, geometry.coordinates);
    }

    if (geometry.type === 'MultiPolygon') {
        return geometry.coordinates.some(polygon =>
            titikDalamPolygon(titik, polygon)
        );
    }

    return false;
}

const WARNA_KELAS = { Tinggi: '#c43d22', Sedang: '#dd9f17', Rendah: '#3f8f68' };
const WARNA_NETRAL = '#7b8a86';
const URUTAN_KELAS = ['Tinggi', 'Sedang', 'Rendah'];
const CATATAN_KELAS = { Tinggi: 'Skor relatif 0,66 ke atas', Sedang: 'Skor relatif 0,40–0,65', Rendah: 'Skor relatif di bawah 0,40' };

const PANDUAN = {
    Tinggi: {
        judul: 'Desa ini termasuk yang paling rawan di kawasan. Ekstra hati-hati.',
        langkah: [
            'Jangan membuat api unggun, menyalakan flare, atau kembang api.',
            'Jangan merokok di area rumput atau semak. Jika terpaksa, matikan puntung sampai benar-benar padam dan bawa pulang.',
            'Bawa pulang semua sampah. Lihat asap atau api kecil? Segera lapor.',
        ],
    },
    Sedang: {
        judul: 'Risiko sedang. Api kecil pun bisa merambat jika rumput kering.',
        langkah: [
            'Jangan membuat api unggun kecuali di tempat yang diizinkan petugas.',
            'Matikan puntung rokok sepenuhnya; jangan membuangnya ke rumput atau semak kering.',
            'Lihat asap atau api kecil? Laporkan sebelum membesar.',
        ],
    },
    Rendah: {
        judul: 'Risiko relatif rendah, bukan berarti aman. Rumput kering tetap mudah terbakar.',
        langkah: [
            'Jangan membuang puntung rokok sembarangan.',
            'Hindari membakar sampah atau membuat api terbuka.',
            'Lihat asap? Laporkan lewat tombol Lapor asap.',
        ],
    },
};

const PANDUAN_UMUM = {
    judul: 'Kelas risiko desa ini belum tersedia. Tetap berhati-hati.',
    langkah: [
        'Jangan membuat api unggun atau membakar sampah.',
        'Matikan puntung rokok sepenuhnya.',
        'Lihat asap atau api? Segera lapor.',
    ],
};

// Faktor yang dibandingkan antar desa.
// arah 'tinggi' = makin besar makin rawan, 'rendah' = makin kecil makin rawan.
const FAKTOR = [
    { kunci: 'Fraksi_Rumput', arah: 'tinggi', judul: 'Banyak tutupan rumput/savana', jelas: 'Rumput kering cepat menyala dan merambat.' },
    { kunci: 'NDMI_sebelum', arah: 'rendah', judul: 'Vegetasi relatif kering', jelas: 'Kandungan air pada vegetasi (NDMI) rendah sebelum kebakaran.' },
    { kunci: 'Hujan_7_Hari_mm', arah: 'rendah', judul: 'Hujan sebelumnya sedikit', jelas: 'Curah hujan 7 hari (CHIRPS) rendah sehingga lahan kering.' },
    { kunci: 'Suhu_Permukaan_C', arah: 'tinggi', judul: 'Permukaan tanah panas', jelas: 'Suhu permukaan (MODIS) tinggi mempercepat pengeringan.' },
    { kunci: 'Lereng_derajat', arah: 'tinggi', judul: 'Lereng curam', jelas: 'Api merambat lebih cepat ke arah atas lereng.' },
    { kunci: 'Hotspot_Piksel', arah: 'tinggi', judul: 'Banyak titik panas terdeteksi', jelas: 'Satelit (VIIRS) merekam banyak deteksi panas saat kejadian 2023.' },
];

const gerakDikurangi = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const angka = (v) => (v === null || v === undefined || v === '' || Number.isNaN(Number(v))) ? null : Number(v);

function siapkanDesa(zona) {
    const a = zona.data_analisis || {};
    const kelas = zona.kelas || a.Kelas || null;

    return {
        kode: zona.kode,
        nama: zona.nama,
        label: a.Label || zona.nama,
        kecamatan: a.Kecamatan || null,
        kabupaten: a.Kabupaten || null,
        kelas: URUTAN_KELAS.includes(kelas) ? kelas : null,
        skor: angka(a.Skor_Gabungan),
        kerentanan: angka(a.Skor_Kerentanan),
        dampak: angka(a.Skor_Dampak),
        a,
        lat: Number(zona.titik_tengah_lat),
        lng: Number(zona.titik_tengah_lng),
        deskripsi: zona.deskripsi_lokasi || '',
        jenisLahan: zona.jenis_lahan || '',
        geometri: zona.poligon_geojson || null,
    };
}

export default function pantauanPeta(zonas, hotspots, sumberAir, datasetUrl) {
    // Objek Leaflet disimpan di luar proxy reaktif Alpine.
    let map;
    const groups = {};
    const bases = {};
    const layerDesa = {};
    let markerLokasiPengguna = null;
    let requests = 0;

    return {
        desa: zonas.map(siapkanDesa),
        desaTerpilih: null,
        kelasAktif: null,
        pencarian: '',
        petaDasar: 'topografi',

        memuat: true,
        kesalahan: '',
        wilayahTersedia: false,
        analisis: [],

        lokasiPengguna: null,
        desaPengguna: null,
        memuatLokasi: false,
        luarWilayah: false,
        kesalahanLokasi: '',
        modeSimulasiKlik: false,
        jenisLokasi: 'gps',

        tampilHitung: { Tinggi: 0, Sedang: 0, Rendah: 0 },

        kelasList: URUTAN_KELAS.map(nama => ({
            nama,
            kunci: nama.toLowerCase(),
            catatan: CATATAN_KELAS[nama],
        })),

        lapisan: {
            administrasi: false,
            wilayah: false,
            desa: true,
            titik: false,
            hotspot: false,
            sumberAir: false,
        },

        kolomAnalisis: [
            ['NDVI_sebelum', 'NDVI sebelum', ''],
            ['NDVI_sesudah', 'NDVI sesudah', ''],
            ['NDMI_sebelum', 'NDMI sebelum', ''],
            ['NDMI_sesudah', 'NDMI sesudah', ''],
            ['NBR_sebelum', 'NBR sebelum', ''],
            ['NBR_sesudah', 'NBR sesudah', ''],
            ['Hujan_Harian', 'Hujan harian', 'mm'],
            ['Hujan_7_Hari', 'Hujan 7 hari', 'mm'],
            ['Suhu_Permukaan', 'Suhu permukaan', '°C'],
            ['Jumlah_Hotspot_Piksel', 'Deteksi piksel VIIRS', ''],
        ],

        get hitung() {
            const h = { Tinggi: 0, Sedang: 0, Rendah: 0 };
            this.desa.forEach(d => {
                if (d.kelas) h[d.kelas]++;
            });
            return h;
        },

        get tanpaKelas() {
            return this.desa.filter(d => !d.kelas).length;
        },

        get daftarTampil() {
            const q = this.pencarian.trim().toLocaleLowerCase('id');

            return this.desa
                .filter(d => (!this.kelasAktif || d.kelas === this.kelasAktif))
                .filter(d => !q || `${d.nama} ${d.label} ${d.kode}`.toLocaleLowerCase('id').includes(q))
                .sort((a, b) => (b.skor ?? -1) - (a.skor ?? -1));
        },

        init() {
            this.$nextTick(() => {
                map = L.map('petaPantauan', {
                    center: [-7.95, 112.95],
                    zoom: 11,
                    zoomControl: false,
                    scrollWheelZoom: false,
                });

                L.control.zoom({ position: 'topright' }).addTo(map);
                L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(map);

                bases.topografi = L.tileLayer(
                    'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
                    {
                        maxZoom: 17,
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> · <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA)',
                    }
                );

                bases.jalan = L.tileLayer(
                    'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                    {
                        maxZoom: 19,
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    }
                );

                bases.topografi.on('tileerror', () => {
                    if (this.petaDasar === 'topografi') {
                        this.gantiPeta('jalan');
                        this.kesalahan = 'Peta topografi tidak tersedia. Peta jalan ditampilkan.';
                    }
                });

                bases.jalan.on('tileerror', () => {
                    this.kesalahan = 'Sebagian peta dasar gagal dimuat. Batas dan desa tetap dapat dilihat.';
                });

                bases.topografi.addTo(map);

                // Zoom dengan scroll baru aktif setelah peta diklik.
                map.on('click', (e) => {
    map.scrollWheelZoom.enable();

    if (this.modeSimulasiKlik) {
        this.modeSimulasiKlik = false;

        this.prosesLokasi(
            e.latlng.lat,
            e.latlng.lng,
            0,
            'simulasi'
        );
    }
});
                map.on('mouseout', () => map.scrollWheelZoom.disable());

                groups.desa = L.featureGroup().addTo(map);
                groups.titik = L.featureGroup();
                groups.hotspot = L.featureGroup();
                groups.sumberAir = L.featureGroup();

                this.desa.forEach(d => {
                    const m = L.circleMarker(
                        [d.lat, d.lng],
                        {
                            radius: 5,
                            color: '#fff',
                            weight: 2,
                            fillColor: '#172023',
                            fillOpacity: 1,
                        }
                    ).addTo(groups.titik);

                    m.bindTooltip(textNode(d.nama), { direction: 'top' });
                    m.on('click', () => this.pilihDesa(d.kode));
                });

                // Layer titik panas dibuat terpisah.
                hotspots.forEach(h => {
                    const lat = Number(h.latitude);
                    const lng = Number(h.longitude);

                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    L.circleMarker(
                        [lat, lng],
                        {
                            radius: 5,
                            color: '#a93632',
                            weight: 1,
                            fillColor: '#e34b43',
                            fillOpacity: 0.8,
                        }
                    )
                        .bindTooltip(textNode('Titik panas tersimpan · asal belum diverifikasi'))
                        .addTo(groups.hotspot);
                });

                // Layer sumber air dibuat terpisah.
                sumberAir.forEach(s => {
                    const lat = Number(s.latitude);
                    const lng = Number(s.longitude);

                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    L.circleMarker(
                        [lat, lng],
                        {
                            radius: 5,
                            color: '#276a8d',
                            weight: 1,
                            fillColor: '#4aa3cf',
                            fillOpacity: 0.8,
                        }
                    )
                        .bindTooltip(textNode(`${s.nama || 'Sumber air'} · data tersimpan belum diverifikasi`))
                        .addTo(groups.sumberAir);
                });

                this.aturLapisan('hotspot');
                this.aturLapisan('sumberAir');

                this.muatDataset();
                this.siapkanPoligon();
            });

            this.animasiHitung();
        },

        // Hitung naik sekali saat halaman dibuka.
        // Dilewati bila pengguna meminta gerak dikurangi.
        animasiHitung() {
            const target = this.hitung;

            if (gerakDikurangi()) {
                this.tampilHitung = { ...target };
                return;
            }

            const mulai = performance.now();
            const durasi = 900;

            const langkah = (t) => {
                const p = Math.min(1, (t - mulai) / durasi);
                const e = 1 - Math.pow(1 - p, 3);

                this.tampilHitung = {
                    Tinggi: Math.round(target.Tinggi * e),
                    Sedang: Math.round(target.Sedang * e),
                    Rendah: Math.round(target.Rendah * e),
                };

                if (p < 1) {
                    requestAnimationFrame(langkah);
                }
            };

            requestAnimationFrame(langkah);
        },

        async siapkanPoligon() {
            try {
                if (this.desa.some(d => !d.geometri)) {
                    const response = await fetch('/api/v1/zones', {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('API desa gagal');
                    }

                    const payload = await response.json();
                    const peta = Object.fromEntries(
                        (payload.data || []).map(z => [z.kode, z.poligon_geojson])
                    );

                    this.desa.forEach(d => {
                        d.geometri = d.geometri || peta[d.kode] || null;
                    });
                }

                this.gambarDesa();
            } catch (error) {
                this.kesalahan = 'Poligon desa gagal dimuat. Daftar desa tetap bisa dipakai.';
            }
        },

        gambarDesa() {
            groups.desa.clearLayers();

            this.desa.forEach(d => {
                if (!d.geometri) return;

                const layer = L.geoJSON(
                    { type: 'Feature', geometry: d.geometri, properties: {} },
                    { style: this.gaya(d) }
                );

                layer.eachLayer(l => {
                    l.on('click', () => this.pilihDesa(d.kode));

                    l.on('mouseover', () => {
                        if (this.desaTerpilih?.kode !== d.kode) {
                            l.setStyle({ weight: 2.5, color: '#172023' });
                        }
                    });

                    l.on('mouseout', () => l.setStyle(this.gaya(d)));

                    l.bindTooltip(
                        textNode(`${d.nama}${d.kelas ? ' · risiko ' + d.kelas.toLowerCase() : ''}`),
                        { sticky: true }
                    );
                });

                layer.addTo(groups.desa);
                layerDesa[d.kode] = layer;
            });

            this.perbaruiGaya();
            this.fokusBromo();
            this.aturLapisan('desa');
        },

        gaya(d) {
            const warna = WARNA_KELAS[d.kelas] || WARNA_NETRAL;
            const terpilih = this.desaTerpilih?.kode === d.kode;
            const redup = this.kelasAktif && d.kelas !== this.kelasAktif;

            if (terpilih) {
                return { color: '#172023', weight: 3, fillColor: warna, fillOpacity: 0.72 };
            }

            if (redup) {
                return { color: '#9aa7a2', weight: 0.6, fillColor: '#9aa7a2', fillOpacity: 0.07 };
            }

            return { color: warna, weight: 1.4, fillColor: warna, fillOpacity: 0.42 };
        },

        perbaruiGaya() {
            this.desa.forEach(d => {
                const layer = layerDesa[d.kode];

                if (!layer) return;

                layer.setStyle(this.gaya(d));

                if (this.desaTerpilih?.kode === d.kode) {
                    layer.bringToFront();
                }
            });
        },

        saring(kelas) {
            this.kelasAktif = this.kelasAktif === kelas ? null : kelas;

            if (this.desaTerpilih && this.kelasAktif && this.desaTerpilih.kelas !== this.kelasAktif) {
                this.desaTerpilih = null;
            }

            this.perbaruiGaya();

            const sasaran = this.desa
                .filter(d => !this.kelasAktif || d.kelas === this.kelasAktif)
                .map(d => layerDesa[d.kode])
                .filter(Boolean);

            if (sasaran.length) {
                this.sesuaikan(L.featureGroup(sasaran).getBounds(), 13);
            }
        },

        pilihDesa(kode, geser = true, gulir = true) {
            this.desaTerpilih = this.desa.find(d => d.kode === kode) || null;

            this.perbaruiGaya();

            if (geser && this.desaTerpilih && layerDesa[kode]) {
                this.sesuaikan(layerDesa[kode].getBounds(), 14);
            }

            this.$nextTick(() => {
                if (gulir && window.matchMedia('(max-width: 1050px)').matches) {
                    this.$refs.rail?.scrollIntoView({
                        behavior: gerakDikurangi() ? 'auto' : 'smooth',
                        block: 'start',
                    });
                }
            });
        },

        tutupDetail() {
            this.desaTerpilih = null;
            this.perbaruiGaya();
        },

        sesuaikan(bounds, maksimum) {
            if (!map || !bounds.isValid()) return;

            if (gerakDikurangi()) {
                map.fitBounds(bounds, {
                    padding: [40, 40],
                    maxZoom: maksimum,
                    animate: false,
                });
            } else {
                map.flyToBounds(bounds, {
                    padding: [40, 40],
                    maxZoom: maksimum,
                    duration: 0.8,
                });
            }
        },

        persen(v) {
            return v === null || v === undefined
                ? 0
                : Math.round(Math.max(0, Math.min(1, v)) * 100);
        },

        skor2(v) {
            return v === null || v === undefined
                ? '—'
                : Number(v).toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
        },

        warna(kelas) {
            return WARNA_KELAS[kelas] || WARNA_NETRAL;
        },

        fakta(d) {
            const a = d?.a || {};

            return [
                ['Suhu permukaan', a.Suhu_Permukaan_C, '°C'],
                ['Kemiringan lereng', a.Lereng_derajat, '°'],
                ['Hujan 7 hari', a.Hujan_7_Hari_mm, 'mm'],
                ['NDMI sebelum', a.NDMI_sebelum, ''],
                ['Perubahan NBR (dNBR)', a.dNBR, ''],
                ['Deteksi piksel VIIRS', a.Hotspot_Piksel, ''],
            ];
        },

        async muatDataset() {
            const request = ++requests;

            this.memuat = true;
            this.kesalahan = this.kesalahan || '';

            try {
                const response = await fetch(datasetUrl, {
                    headers: { Accept: 'application/json' },
                    signal: AbortSignal.timeout(20000),
                });

                if (!response.ok) {
                    throw new Error('metadata');
                }

                const payload = await response.json();

                if (!payload.data || !Array.isArray(payload.data)) {
                    throw new Error('Format API tidak valid');
                }

                const [boundaryResponse, areaResponse] = await Promise.all([
                    fetch(payload.tautan.geojson, {
                        signal: AbortSignal.timeout(30000),
                    }),
                    payload.tautan.wilayah
                        ? fetch(payload.tautan.wilayah, {
                            signal: AbortSignal.timeout(30000),
                        })
                        : null,
                ]);

                if (!boundaryResponse.ok || (areaResponse && !areaResponse.ok)) {
                    throw new Error('boundary');
                }

                const boundary = await boundaryResponse.json();
                const area = areaResponse ? await areaResponse.json() : null;

                if (request !== requests) return;

                const buat = (geojson, color, label) => L.geoJSON(geojson, {
                    style: {
                        color,
                        weight: 1.5,
                        fillColor: color,
                        fillOpacity: 0.035,
                    },
                    interactive: false,
                });

                if (groups.administrasi) {
                    groups.administrasi.remove();
                }

                groups.administrasi = buat(boundary, '#697d77', 'Batas administrasi lampiran');
                this.aturLapisan('administrasi');

                if (groups.wilayah) {
                    groups.wilayah.remove();
                }

                const areaValid = Boolean(
                    area && (
                        area.type === 'FeatureCollection' ||
                        area.type === 'Feature' ||
                        area.type === 'Polygon' ||
                        area.type === 'MultiPolygon'
                    )
                );

                this.wilayahTersedia = areaValid;

                if (areaValid) {
                    groups.wilayah = buat(area, '#1b6c63', 'Wilayah analisis GEE');
                    this.aturLapisan('wilayah');
                } else {
                    groups.wilayah = null;
                    this.lapisan.wilayah = false;
                }

                this.analisis = payload.analisis?.baris || [];

                if (groups.kejadian) {
                    groups.kejadian.remove();
                }

                groups.kejadian = L.featureGroup().addTo(map);

                this.analisis.forEach(r => {
                    L.circleMarker(
                        [r.Latitude, r.Longitude],
                        {
                            radius: 8,
                            color: '#172023',
                            weight: 2,
                            fillColor: '#c43d22',
                            fillOpacity: 0.9,
                        }
                    )
                        .bindTooltip(textNode(`${r.Lokasi} · referensi historis ${r.Tanggal_Kebakaran}`))
                        .addTo(groups.kejadian);
                });

            } catch (error) {
                if (request === requests) {
                    this.kesalahan = 'Dataset gagal dimuat. Periksa koneksi lalu coba lagi.';
                }
            } finally {
                if (request === requests) {
                    this.memuat = false;
                }
            }
        },

                        
            
lokasiSaya() {
    this.kesalahanLokasi = '';

    if (!navigator.geolocation) {
        this.kesalahanLokasi =
            'Browser ini tidak mendukung GPS. Cari desamu lewat kolom pencarian atau klik peta.';
        return;
    }

    if (!Object.keys(layerDesa).length) {
        this.kesalahanLokasi =
            'Peta desa masih dimuat. Tunggu sebentar lalu coba lagi.';
        return;
    }

    this.memuatLokasi = true;

    navigator.geolocation.getCurrentPosition(
        (posisi) => {
            this.prosesLokasi(
                posisi.coords.latitude,
                posisi.coords.longitude,
                Math.round(posisi.coords.accuracy),
                'gps'
            );

            this.memuatLokasi = false;
        },

        (error) => {
            this.memuatLokasi = false;

            this.kesalahanLokasi = {
                1: 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, atau cari desamu secara manual.',
                2: 'Lokasi tidak tersedia. Coba pindah ke area terbuka.',
                3: 'Pencarian lokasi terlalu lama. Coba lagi.',
            }[error.code] || 'Gagal mendapatkan lokasi.';
        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
        }
    );
},



aktifkanPilihLokasi() {
    this.modeSimulasiKlik = true;
    this.kesalahanLokasi = 'Mode simulasi aktif. Klik titik pada peta.';
},

batalkanPilihLokasi() {
    this.modeSimulasiKlik = false;
},

simulasikanBantengan() {
    this.prosesLokasi(
        -7.9425,
        112.9530,
        0,
        'simulasi'
    );
},

prosesLokasi(lat, lng, akurasi = 0, jenis = 'gps') {
    this.kesalahanLokasi = '';
    this.jenisLokasi = jenis;

    this.lokasiPengguna = {
        lat,
        lng,
        akurasi,
        simulasi: jenis === 'simulasi',
    };

    if (markerLokasiPengguna) {
        markerLokasiPengguna.setLatLng([lat, lng]);
    } else {
        markerLokasiPengguna = L.circleMarker(
            [lat, lng],
            {
                radius: 9,
                color: '#ffffff',
                weight: 3,
                fillColor: '#2563eb',
                fillOpacity: 1,
            }
        ).addTo(map);
    }

    const ditemukan = this.desa.find(
        d => d.geometri &&
            titikDalamGeoJSON([lng, lat], d.geometri)
    ) || null;

    this.desaPengguna = ditemukan;
    this.luarWilayah = !ditemukan;

    const popup = document.createElement('div');

    const judul = document.createElement('strong');
    judul.textContent = jenis === 'simulasi'
        ? 'Lokasi Simulasi'
        : 'Lokasi GPS';

    popup.appendChild(judul);

    const info = document.createElement('p');

    if (ditemukan) {
        info.textContent =
            `${ditemukan.nama} · Risiko ${ditemukan.kelas || 'belum tersedia'}`;
    } else {
        info.textContent =
            'Lokasi berada di luar polygon desa yang dianalisis PyroGuard.';
    }

    popup.appendChild(info);

    if (ditemukan?.kelas === 'Tinggi') {
        const peringatan = document.createElement('p');
        peringatan.textContent =
            '⚠️ PERINGATAN: Desa ini memiliki kelas risiko relatif tinggi.';
        popup.appendChild(peringatan);
    }

    markerLokasiPengguna
        .bindPopup(popup)
        .openPopup();

    map.setView([lat, lng], 14);

    if (ditemukan) {
        this.pilihDesa(ditemukan.kode, false, false);
    }
},


        panduan(kelas) {
            return PANDUAN[kelas] || PANDUAN_UMUM;
        },

        penyebab(d) {
            if (!d) return [];

            const hasil = [];

            FAKTOR.forEach(f => {
                const nilai = angka(d.a?.[f.kunci]);

                if (nilai === null) return;

                const semua = this.desa
                    .map(x => angka(x.a?.[f.kunci]))
                    .filter(v => v !== null);

                if (semua.length < 5) return;

                const lebihRawan = semua.filter(v =>
                    f.arah === 'tinggi'
                        ? v < nilai
                        : v > nilai
                ).length;

                const persen = Math.round(
                    (lebihRawan / (semua.length - 1)) * 100
                );

                if (persen >= 66) {
                    hasil.push({
                        judul: f.judul,
                        jelas: f.jelas,
                        persen: Math.min(persen, 100),
                    });
                }
            });

            return hasil
                .sort((a, b) => b.persen - a.persen)
                .slice(0, 3);
        },

        waktu(d) {
            const tgl = d?.a?.Tanggal_Kebakaran;

            if (!tgl) return null;

            const waktu = new Date(`${tgl}T00:00:00`);

            if (Number.isNaN(waktu.getTime())) return null;

            const bulan = waktu.getMonth() + 1;
            const luas = angka(d.a.Luas_dNBR_ge_0_27_ha);
            const fraksi = angka(d.a.Fraksi_dNBR_ge_0_27);

            return {
                tanggal: waktu.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                }),

                musim: bulan >= 6 && bulan <= 10
                    ? 'musim kemarau'
                    : 'musim hujan',

                luas: luas === null
                    ? null
                    : Math.round(luas).toLocaleString('id-ID'),

                persenLuas: fraksi === null
                    ? null
                    : Math.round(fraksi * 100),
            };
        },

        aturLapisan(nama) {
            const layer = groups[nama];

            if (!map || !layer) return;

            if (this.lapisan[nama]) {
                layer.addTo(map);
            } else {
                layer.remove();
            }
        },

        gantiPeta(nama) {
            if (!map || !bases[nama]) return;

            Object.values(bases).forEach(l => l.remove());

            this.petaDasar = nama;
            bases[nama].addTo(map);
        },

        fokusBromo() {
            const b = groups.desa?.getBounds();

            if (b && b.isValid()) {
                this.sesuaikan(b, 12);
            } else {
                map?.setView([-7.95, 112.95], 11);
            }
        },

        formatAngka(value) {
            return value === null || value === undefined
                ? '—'
                : Number(value).toLocaleString('id-ID', {
                    maximumFractionDigits: 3,
                });
        },

        destroy() {
            requests++;
            map?.remove();
        },
    };
}