import L from 'leaflet';

const textNode = (text) => { const el = document.createElement('span'); el.textContent = text; return el; };

export default function pantauanPeta(zonas, hotspots, sumberAir, datasetUrl) {
    // Leaflet objects stay outside Alpine's reactive proxies.
    let map;
    const groups = {};
    const markers = {};
    const bases = {};
    let requests = 0;
    return {
        zonas, terpilih: zonas[0] || null, pencarian: '', petaDasar: 'topografi',
        memuat: true, kesalahan: '', wilayahTersedia: false, analisis: [],
        lapisan: { administrasi: true, wilayah: true, zona: true, operasional: false },
        kolomAnalisis: [['NDVI_sebelum', 'NDVI sebelum', ''], ['NDVI_sesudah', 'NDVI sesudah', ''], ['NDMI_sebelum', 'NDMI sebelum', ''], ['NDMI_sesudah', 'NDMI sesudah', ''], ['NBR_sebelum', 'NBR sebelum', ''], ['NBR_sesudah', 'NBR sesudah', ''], ['Hujan_Harian', 'Hujan harian', 'mm'], ['Hujan_7_Hari', 'Hujan 7 hari', 'mm'], ['Suhu_Permukaan', 'Suhu permukaan', '°C'], ['Jumlah_Hotspot_Piksel', 'Deteksi piksel VIIRS', '']],
        get zonaTerfilter() { const q = this.pencarian.toLocaleLowerCase('id'); return this.zonas.filter(z => `${z.kode} ${z.nama}`.toLocaleLowerCase('id').includes(q)); },
        get risiko() { return this.terpilih?.risiko_terbaru || null; },
        get koordinatZona() { return this.terpilih ? `${Number(this.terpilih.titik_tengah_lat).toFixed(5)}, ${Number(this.terpilih.titik_tengah_lng).toFixed(5)}` : '—'; },
        init() {
            this.$nextTick(() => {
                map = L.map('petaPantauan', { center: [-7.95, 112.95], zoom: 11, zoomControl: false, scrollWheelZoom: false });
                L.control.zoom({ position: 'topright' }).addTo(map);
                L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(map);
                bases.topografi = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { maxZoom: 17, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> · <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA)' });
                bases.jalan = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' });
                bases.topografi.on('tileerror', () => { if (this.petaDasar === 'topografi') { this.gantiPeta('jalan'); this.kesalahan = 'Peta topografi tidak tersedia. Peta jalan ditampilkan.'; } });
                bases.jalan.on('tileerror', () => { this.kesalahan = 'Sebagian peta dasar gagal dimuat. Batas dan referensi tetap dapat dilihat.'; });
                bases.topografi.addTo(map);
                groups.zona = L.featureGroup().addTo(map);
                groups.operasional = L.featureGroup();
                zonas.forEach(z => {
                    const marker = L.circleMarker([z.titik_tengah_lat, z.titik_tengah_lng], { radius: 7, color: '#ffffff', weight: 2, fillColor: '#1b6c63', fillOpacity: 1 }).addTo(groups.zona);
                    marker.bindTooltip(textNode(`${z.kode} · ${z.nama}`), { direction: 'top' });
                    marker.on('click', () => this.pilihZona(z.kode, false));
                    markers[z.kode] = marker;
                });
                hotspots.forEach(h => L.circleMarker([h.latitude, h.longitude], { radius: 5, color: '#a93632', fillOpacity: 0.7 }).bindTooltip(textNode('Titik panas tersimpan · asal belum diverifikasi')).addTo(groups.operasional));
                sumberAir.forEach(s => L.circleMarker([s.latitude, s.longitude], { radius: 5, color: '#276a8d', fillOpacity: 0.8 }).bindTooltip(textNode(`${s.nama} · data tersimpan belum diverifikasi`)).addTo(groups.operasional));
                this.muatDataset();
                if (zonas[0]) { this.pilihZona(zonas[0].kode, false); }
            });
        },
        async muatDataset() {
            const request = ++requests;
            this.memuat = true; this.kesalahan = '';
            try {
                const response = await fetch(datasetUrl, { headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(20000) });
                if (!response.ok) { throw new Error('metadata'); }
                const payload = await response.json();
                const [boundaryResponse, areaResponse] = await Promise.all([fetch(payload.tautan.geojson, { signal: AbortSignal.timeout(30000) }), payload.tautan.wilayah ? fetch(payload.tautan.wilayah, { signal: AbortSignal.timeout(30000) }) : null]);
                if (!boundaryResponse.ok || (areaResponse && !areaResponse.ok)) { throw new Error('boundary'); }
                const boundary = await boundaryResponse.json();
                const area = areaResponse ? await areaResponse.json() : null;
                if (request !== requests) { return; }
                const createLayer = (geojson, color, label) => L.geoJSON(geojson, { style: { color, weight: 1.5, fillColor: color, fillOpacity: 0.035 }, onEachFeature: (f, layer) => { layer.bindTooltip(textNode(f.properties?.nama || f.properties?.WADMKK || label)); } });
                if (groups.administrasi) { groups.administrasi.remove(); }
                groups.administrasi = createLayer(boundary, '#697d77', 'Batas administrasi lampiran');
                this.aturLapisan('administrasi');
                if (groups.wilayah) { groups.wilayah.remove(); }
                this.wilayahTersedia = Boolean(area);
                if (area) { groups.wilayah = createLayer(area, '#1b6c63', 'Wilayah analisis GEE'); this.aturLapisan('wilayah'); }
                this.analisis = payload.analisis?.baris || [];
                if (groups.kejadian) { groups.kejadian.remove(); }
                groups.kejadian = L.featureGroup().addTo(map);
                this.analisis.forEach(r => L.circleMarker([r.Latitude, r.Longitude], { radius: 8, color: '#a93632', fillColor: '#a93632', fillOpacity: 0.8 }).bindTooltip(textNode(`${r.Lokasi} · referensi historis ${r.Tanggal_Kebakaran}`)).addTo(groups.kejadian));
            } catch (error) {
                if (request === requests) { this.kesalahan = 'Dataset gagal dimuat. Periksa koneksi lalu coba lagi.'; }
            } finally { if (request === requests) { this.memuat = false; } }
        },
        aturLapisan(nama) { const layer = groups[nama]; if (layer && map) { if (this.lapisan[nama]) { layer.addTo(map); } else { layer.remove(); } } },
        gantiPeta(nama) { if (!map || !bases[nama]) { return; } Object.values(bases).forEach(l => l.remove()); this.petaDasar = nama; bases[nama].addTo(map); },
        fokusBromo() { if (groups.wilayah && groups.wilayah.getBounds().isValid()) { map.fitBounds(groups.wilayah.getBounds(), { padding: [24, 24], maxZoom: 12 }); } else if (groups.zona?.getBounds().isValid()) { map.fitBounds(groups.zona.getBounds(), { padding: [38, 38], maxZoom: 12 }); } else { map.setView([-7.95, 112.95], 11); } },
        pilihZona(kode, geser = true) { this.terpilih = this.zonas.find(z => z.kode === kode) || null; Object.entries(markers).forEach(([key, marker]) => marker.setStyle({ radius: key === kode ? 10 : 7, fillColor: key === kode ? '#132b27' : '#1b6c63' })); if (geser && this.terpilih) { map.setView([this.terpilih.titik_tengah_lat, this.terpilih.titik_tengah_lng], 13); } },
        formatAngka(value) { return value === null || value === undefined ? '—' : Number(value).toLocaleString('id-ID', { maximumFractionDigits: 3 }); },
        destroy() { requests++; map?.remove(); },
    };
}
