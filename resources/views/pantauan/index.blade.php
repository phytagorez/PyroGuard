@extends('layouts.publik')
@section('judul', 'Pantauan peta')
@section('konten')
<div x-data="pantauanPeta({{ Js::from($zonas->values()) }}, {{ Js::from($hotspots) }}, {{ Js::from($sumberAirs) }}, {{ Js::from(route('api.dataset.index')) }})" class="monitor-workspace">
    <div class="screen-heading">
        <div><h1>Pantauan kawasan</h1><p>Baca bentang Bromo, telusuri zona, dan periksa asal setiap lapisan.</p></div>
        <a href="{{ route('lapor.index') }}" class="primary-button">＋ Lapor asap</a>
    </div>
    <div class="monitor-summary">
        <div><span>Wilayah pada lampiran</span><strong>10 <small>fitur administrasi</small></strong></div>
        <div><span>Referensi operasional</span><strong>{{ $zonas->count() }} <small>zona pemantauan</small></strong></div>
        <div><span>Analisis lingkungan</span><strong class="summary-text">Sentinel-2 / CHIRPS / MODIS / VIIRS</strong><small>Historis · periode kejadian 2023</small></div>
    </div>
    <div class="monitor-grid">
        <section class="map-workbench" aria-label="Peta kawasan Bromo">
            <div class="map-toolbar">
                <div class="segmented" aria-label="Peta dasar"><button type="button" @click="gantiPeta('topografi')" :aria-pressed="petaDasar === 'topografi'">Topografi</button><button type="button" @click="gantiPeta('jalan')" :aria-pressed="petaDasar === 'jalan'">Jalan</button></div>
                <button class="quiet-button" type="button" @click="fokusBromo()">Fokus Bromo <span aria-hidden="true">⌖</span></button>
            </div>
            <div class="map-stage"><div id="petaPantauan" class="monitor-map" role="region" aria-label="Peta interaktif batas administrasi dan referensi zona Bromo"></div>
                <div class="map-loading" x-show="memuat" role="status">Memuat batas dari dataset lampiran…</div>
                <div class="map-error" x-show="kesalahan" x-cloak role="status"><span x-text="kesalahan"></span><button type="button" @click="muatDataset()">Coba lagi</button></div>
                <div class="map-caption"><span class="map-boundary-key"></span><span>Batas administrasi · Bromo.zip</span></div>
            </div>
            <div class="map-layers" aria-label="Lapisan peta">
                <label>
                    <input type="checkbox"
                        x-model="lapisan.desa"
                        @change="aturLapisan('desa')">
                    Polygon desa
                </label>
                <label><input type="checkbox" x-model="lapisan.administrasi" @change="aturLapisan('administrasi')"> Batas administrasi</label>
                <label><input type="checkbox" x-model="lapisan.wilayah" @change="aturLapisan('wilayah')" :disabled="!wilayahTersedia"> Wilayah analisis GEE</label>
                <label><input type="checkbox" x-model="lapisan.zona" @change="aturLapisan('zona')"> Referensi zona</label>
                <label><input type="checkbox" x-model="lapisan.operasional" @change="aturLapisan('operasional')"> Logistik & titik panas tersimpan</label>
            </div>
            <div class="risk-legend">
    <span><i class="risk-low"></i> Rendah</span>
    <span><i class="risk-medium"></i> Sedang</span>
    <span><i class="risk-high"></i> Tinggi</span>
</div>
            <div class="map-footnote"><span>Poligon = wilayah · Point = lokasi / referensi zona</span><span>WGS 84 / EPSG:4326</span></div>
        </section>
        <aside class="zone-inspector" aria-label="Detail zona">
            <div class="inspector-heading"><span>Referensi zona</span><span class="muted" x-text="terpilih?.kode || '—'"></span></div>
            <div class="inspector-body">
                <span class="neutral-tag">Titik operasional · bukan batas wilayah</span>
                <h2 x-text="terpilih?.nama || 'Belum ada zona'"></h2>
                <p x-text="terpilih?.deskripsi_lokasi || 'Data referensi zona belum tersedia.'"></p>
                <dl class="zone-facts"><div><dt>Jenis lahan</dt><dd x-text="terpilih?.jenis_lahan || '—'"></dd></div><div><dt>Koordinat referensi</dt><dd x-text="koordinatZona"></dd></div><div><dt>Catatan risiko tersimpan</dt><dd x-text="risiko?.kategori_risiko || 'Belum tersedia'"></dd></div></dl>
                <div class="stored-risk"><div><span>Indeks bahaya api</span><strong><span x-text="risiko?.indeks_bahaya_api ?? '—'"></span><small>/100</small></strong></div><div><span>Hari tanpa hujan</span><strong><span x-text="risiko?.hari_tanpa_hujan ?? '—'"></span><small>hari</small></strong></div></div>
                <p class="inspector-disclaimer">Angka tersimpan perlu verifikasi asal dan waktu observasi; bukan status kawasan saat ini.</p>
                <a href="{{ route('evakuasi') }}" class="outline-button">Lihat logistik & akses <span aria-hidden="true">→</span></a>
            </div>
            <div class="inspector-source"><span class="context-dot"></span><span>Analisis GEE <strong x-text="analisis.length ? 'sudah diimpor' : 'menunggu ekspor'"></strong></span></div>
        </aside>
    </div>
    <section class="zone-directory">
        <div class="section-heading"><div><h2>Zona pemantauan</h2><p>Enam referensi kerja di sekitar kaldera dan pintu masuk kawasan.</p></div><label class="search-field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 5 5"/></svg><input type="search" x-model="pencarian" aria-label="Cari zona" placeholder="Cari nama atau kode zona"></label></div>
        <div class="zone-list" role="list">
            <template x-for="zona in zonaTerfilter" :key="zona.kode"><button class="zone-row" type="button" @click="pilihZona(zona.kode)" :aria-pressed="terpilih?.kode === zona.kode" role="listitem"><span class="zone-code" x-text="zona.kode"></span><span class="zone-name" x-text="zona.nama"></span><span class="zone-coordinate" x-text="Number(zona.titik_tengah_lat).toFixed(4) + ', ' + Number(zona.titik_tengah_lng).toFixed(4)"></span><span class="zone-row-action"><span class="zone-row-label">Lihat di peta </span>↗</span></button></template>
            <p class="empty-inline" x-show="!zonaTerfilter.length" x-cloak>Tidak ada zona yang cocok dengan pencarian.</p>
        </div>
    </section>
    <section class="dataset-strip"><div><span class="neutral-tag">Dataset & metode</span><h2>Kenali apa yang dipetakan.</h2><p>Batas administrasi lampiran dan hasil analisis satelit punya fungsi dan periode yang berbeda.</p></div><div class="dataset-links"><a href="{{ route('metode') }}#dataset">Periksa sumber data <span>↗</span></a><a href="{{ asset('data/bromo-qgis-portabel.zip') }}" download>Unduh proyek QGIS portabel <span>↓</span></a><a href="{{ route('api.dataset.batas') }}" download="bromo-administrasi.geojson">Unduh batas GeoJSON <span>↓</span></a></div></section>
    <section class="historical-analysis" x-show="analisis.length" x-cloak><div class="section-heading"><div><h2>Analisis historis GEE</h2><p>Nilai dari CSV yang diunggah pengelola; belum diverifikasi independen. Hotspot adalah jumlah deteksi piksel, bukan jumlah kejadian.</p></div></div><template x-for="(baris, index) in analisis" :key="index"><div class="analysis-result"><h3><span x-text="baris.Lokasi"></span> · <span x-text="baris.Tanggal_Kebakaran"></span></h3><dl class="analysis-metrics"><template x-for="kolom in kolomAnalisis" :key="kolom[0]"><div><dt x-text="kolom[1]"></dt><dd><span x-text="formatAngka(baris[kolom[0]])"></span> <small x-text="kolom[2]"></small></dd></div></template></dl></div></template></section>
</div>
@endsection
