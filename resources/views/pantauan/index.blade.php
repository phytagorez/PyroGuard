@extends('layouts.publik')
@section('judul', 'Pantauan peta')
@section('konten')
<div x-data="pantauanPeta({{ Js::from($zonas->values()) }}, {{ Js::from($hotspots) }}, {{ Js::from($sumberAirs) }}, {{ Js::from(route('api.dataset.index')) }})" class="monitor-workspace">
    <div class="screen-heading">
        <div>
            <h1>Pantauan kawasan Bromo</h1>
            <p>Pilih kelas risiko atau klik desa di peta untuk melihat apa yang membuatnya rawan.</p>
        </div>
        <a href="{{ route('lapor.index') }}" class="primary-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Lapor asap</a>
    </div>

    <div class="class-tiles" role="group" aria-label="Saring desa menurut kelas risiko">
        <template x-for="k in kelasList" :key="k.nama">
            <button type="button" class="class-tile" :class="'is-' + k.kunci" :aria-pressed="kelasAktif === k.nama" @click="saring(k.nama)">
                <span class="class-tile-head"><span class="risk-glyph" :data-kelas="k.nama" aria-hidden="true"></span><span class="class-name" x-text="'Risiko ' + k.nama.toLowerCase()"></span></span>
                <span class="class-count"><span x-text="tampilHitung[k.nama]"></span><small>desa</small></span>
                <span class="class-hint" x-text="k.catatan"></span>
            </button>
        </template>
    </div>
    <p class="class-note">Kelas bersifat relatif: desa dibandingkan satu sama lain dari analisis satelit historis (Sentinel-2, CHIRPS, MODIS, VIIRS; kejadian 2023). Ini alat bantu prioritas pengecekan, bukan prediksi.<span x-show="tanpaKelas > 0" x-cloak x-text="' ' + tanpaKelas + ' desa belum memiliki kelas.'"></span></p>

    <div class="monitor-grid">
        <section class="map-workbench" aria-label="Peta kawasan Bromo">
            <div class="map-toolbar">
                <div class="segmented" role="group" aria-label="Peta dasar"><button type="button" @click="gantiPeta('topografi')" :aria-pressed="petaDasar === 'topografi'">Topografi</button><button type="button" @click="gantiPeta('jalan')" :aria-pressed="petaDasar === 'jalan'">Jalan</button></div>
                <div class="map-actions">
                    <button class="quiet-button" type="button" @click="fokusBromo()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>Seluruh kawasan</button>
                    <button class="locate-button" type="button" @click="lokasiSaya()" :disabled="memuatLokasi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3.5"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg><span x-text="memuatLokasi ? 'Mencari lokasi…' : 'Lokasi saya'"></span></button>
                </div>
            </div>
            <div class="map-stage">
                <div id="petaPantauan" class="monitor-map" role="region" aria-label="Peta interaktif desa di kawasan Bromo menurut kelas risiko"></div>
                <div class="map-loading" x-show="memuat" role="status">Memuat batas dari dataset lampiran…</div>
                <div class="map-error" x-show="kesalahan" x-cloak role="status"><span x-text="kesalahan"></span><button type="button" @click="kesalahan = ''; muatDataset()">Coba lagi</button></div>
                <div class="map-legend" aria-label="Legenda kelas risiko">
                    <span><i class="risk-glyph" data-kelas="Tinggi"></i>Tinggi</span><span><i class="risk-glyph" data-kelas="Sedang"></i>Sedang</span><span><i class="risk-glyph" data-kelas="Rendah"></i>Rendah</span>
                </div>
                <div class="location-card" x-show="lokasiPengguna" x-cloak role="status">
                    <template x-if="desaPengguna"><div>
                        <span class="location-label">Anda berada di</span>
                        <strong x-text="desaPengguna.nama"></strong>
                        <span class="location-class"><i class="risk-glyph" :data-kelas="desaPengguna.kelas" aria-hidden="true"></i><span x-text="desaPengguna.kelas ? 'Risiko ' + desaPengguna.kelas.toLowerCase() : 'Kelas risiko belum tersedia'"></span></span>
                    </div></template>
                    <template x-if="!desaPengguna && luarWilayah"><div><strong>Di luar wilayah analisis</strong><span class="location-label">Lokasimu tidak berada di desa yang dianalisis PyroGuard.</span></div></template>
                </div>
            </div>
            <div class="map-layers" role="group" aria-label="Lapisan peta">
                <label><input type="checkbox" x-model="lapisan.desa" @change="aturLapisan('desa')"> Desa menurut risiko</label>
                <label><input type="checkbox" x-model="lapisan.titik" @change="aturLapisan('titik')"> Titik pusat desa</label>
                <label><input type="checkbox" x-model="lapisan.administrasi" @change="aturLapisan('administrasi')"> Batas administrasi</label>
                <label><input type="checkbox" x-model="lapisan.wilayah" @change="aturLapisan('wilayah')" :disabled="!wilayahTersedia"> Wilayah analisis GEE</label>
                <label><input type="checkbox" x-model="lapisan.operasional" @change="aturLapisan('operasional')"> Logistik & titik panas tersimpan</label>
            </div>
            <div class="map-footnote"><span>Warna desa = kelas risiko relatif · lingkaran, segitiga, dan belah ketupat menandai kelas tanpa bergantung pada warna</span><span>WGS 84 / EPSG:4326</span></div>
        </section>

        <aside class="desa-rail" x-ref="rail" aria-label="Daftar dan detail desa" @keydown.escape="tutupDetail()">
            <div class="rail-list" x-show="!desaTerpilih">
                <div class="rail-head">
                    <div><h2>Desa</h2><p><span x-text="daftarTampil.length"></span> dari <span x-text="desa.length"></span> desa<span x-show="kelasAktif" x-cloak>, risiko <span x-text="kelasAktif?.toLowerCase()"></span></span>, urut skor tertinggi</p></div>
                    <button type="button" class="chip-clear" x-show="kelasAktif" x-cloak @click="saring(kelasAktif)">Hapus saringan</button>
                </div>
                <label class="search-field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 5 5"/></svg><input type="search" x-model="pencarian" aria-label="Cari desa" placeholder="Cari nama desa"></label>
                <div class="desa-list">
                    <template x-for="(d, i) in daftarTampil" :key="d.kode">
                        <button type="button" class="desa-row" @click="pilihDesa(d.kode)">
                            <span class="desa-rank" x-text="i + 1"></span>
                            <span class="desa-main"><span class="desa-name" x-text="d.nama"></span><span class="desa-sub"><i class="risk-glyph" :data-kelas="d.kelas" aria-hidden="true"></i><span x-text="d.kelas ? 'Risiko ' + d.kelas.toLowerCase() : 'Kelas belum tersedia'"></span></span></span>
                            <span class="desa-score"><span class="meter" :style="`--v:${persen(d.skor)}%; --c:${warna(d.kelas)}`"><i></i></span><span x-text="skor2(d.skor)"></span></span>
                        </button>
                    </template>
                    <p class="empty-inline" x-show="!daftarTampil.length" x-cloak>Tidak ada desa yang cocok. Hapus saringan atau ubah kata kunci.</p>
                </div>
            </div>

            <div class="rail-detail" x-show="desaTerpilih" x-cloak>
                <template x-if="desaTerpilih"><div>
                    <button type="button" class="back-link" @click="tutupDetail()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>Semua desa</button>
                    <h2 x-text="desaTerpilih.nama"></h2>
                    <p class="detail-sub" x-text="[desaTerpilih.kecamatan, desaTerpilih.kabupaten].filter(Boolean).join(', ') || desaTerpilih.label"></p>
                    <div class="class-badge" :class="'is-' + (desaTerpilih.kelas || 'none').toLowerCase()"><i class="risk-glyph" :data-kelas="desaTerpilih.kelas" aria-hidden="true"></i><span x-text="desaTerpilih.kelas ? 'Risiko ' + desaTerpilih.kelas.toLowerCase() : 'Kelas risiko belum tersedia'"></span></div>
                    <div class="meters">
                        <div class="meter-row"><div><span>Skor gabungan</span><strong x-text="skor2(desaTerpilih.skor)"></strong></div><span class="meter is-big" :style="`--v:${persen(desaTerpilih.skor)}%; --c:${warna(desaTerpilih.kelas)}`"><i></i></span></div>
                        <div class="meter-row"><div><span>Kerentanan sebelum kebakaran</span><strong x-text="skor2(desaTerpilih.kerentanan)"></strong></div><span class="meter" :style="`--v:${persen(desaTerpilih.kerentanan)}%; --c:#4a5d58`"><i></i></span></div>
                        <div class="meter-row"><div><span>Dampak teramati</span><strong x-text="skor2(desaTerpilih.dampak)"></strong></div><span class="meter" :style="`--v:${persen(desaTerpilih.dampak)}%; --c:#4a5d58`"><i></i></span></div>
                    </div>
                    <dl class="zone-facts"><template x-for="f in fakta(desaTerpilih)" :key="f[0]"><div><dt x-text="f[0]"></dt><dd><span x-text="formatAngka(f[1])"></span> <small x-text="f[2]"></small></dd></div></template></dl>
                    <p class="inspector-disclaimer">Skor relatif antar desa dari analisis historis; nilai kosong berarti data tidak tersedia, bukan nol.</p>
                    <div class="detail-actions"><button type="button" class="outline-button" @click="pilihDesa(desaTerpilih.kode)">Perbesar ke desa</button><a href="{{ route('evakuasi') }}" class="outline-button">Logistik & akses</a></div>
                </div></template>
            </div>
        </aside>
    </div>

    <section class="dataset-strip"><div><span class="neutral-tag">Dataset & metode</span><h2>Kenali apa yang dipetakan.</h2><p>Batas administrasi lampiran dan hasil analisis satelit punya fungsi dan periode yang berbeda.</p></div><div class="dataset-links"><a href="{{ route('metode') }}#dataset">Periksa sumber data <span>↗</span></a><a href="{{ asset('data/bromo-qgis-portabel.zip') }}" download>Unduh proyek QGIS portabel <span>↓</span></a><a href="{{ route('api.dataset.batas') }}" download="bromo-administrasi.geojson">Unduh batas GeoJSON <span>↓</span></a></div></section>
    <section class="historical-analysis" x-show="analisis.length" x-cloak><div class="section-heading"><div><h2>Analisis historis GEE</h2><p>Nilai dari CSV yang diunggah pengelola; belum diverifikasi independen. Hotspot adalah jumlah deteksi piksel, bukan jumlah kejadian.</p></div></div><template x-for="(baris, index) in analisis" :key="index"><div class="analysis-result"><h3><span x-text="baris.Lokasi"></span> · <span x-text="baris.Tanggal_Kebakaran"></span></h3><dl class="analysis-metrics"><template x-for="kolom in kolomAnalisis" :key="kolom[0]"><div><dt x-text="kolom[1]"></dt><dd><span x-text="formatAngka(baris[kolom[0]])"></span> <small x-text="kolom[2]"></small></dd></div></template></dl></div></template></section>
</div>
@endsection
