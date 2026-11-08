@extends('layouts.admin')
@section('judul', 'Dataset & integrasi')
@section('konten')
<div class="space-y-8">
    <div class="screen-heading"><div><h1>Dataset & integrasi</h1><p>Hubungkan hasil GEE ke peta tanpa membuat proyek Laravel terpisah.</p></div><a class="outline-button" href="{{ route('pantauan') }}">Lihat peta ↗</a></div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <section class="border border-line rounded-lg p-5 space-y-5"><h2 class="text-lg">Impor hasil analisis</h2><p class="text-sm text-ink-muted">Unggah CSV hasil analisis dan GeoJSON aset Wilayah. Wilayah tetap poligon; koordinat kejadian dari CSV ditampilkan sebagai point historis.</p>
            @if($errors->any())<div class="data-notice" role="alert"><ul class="space-y-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form action="{{ route('admin.dataset.impor') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div><label for="dataset-csv" class="block text-sm font-medium mb-2">CSV dataset GEE</label><input class="block w-full border border-line rounded-md p-3 text-sm" id="dataset-csv" type="file" name="dataset_csv" accept=".csv,text/csv" aria-describedby="csv-help"><p id="csv-help" class="text-xs text-ink-muted mt-2">Maksimal 2 MB, 1.000 baris. Kolom mengikuti skrip ekspor; nilai observasi kosong tetap kosong.</p></div>
                <div><label for="wilayah-geojson" class="block text-sm font-medium mb-2">GeoJSON poligon Wilayah</label><input class="block w-full border border-line rounded-md p-3 text-sm" id="wilayah-geojson" type="file" name="wilayah_geojson" accept=".geojson,.json,application/geo+json,application/json" aria-describedby="geojson-help"><p id="geojson-help" class="text-xs text-ink-muted mt-2">Maksimal 15 MB. FeatureCollection WGS 84, Polygon / MultiPolygon dengan ring tertutup.</p></div>
                <p class="text-xs text-ink-muted">Pilih minimal satu file. Impor mengganti hasil sebelumnya untuk jenis file yang diunggah. Data ini akan terlihat di peta publik sebagai unggahan pengelola.</p>
                <button type="submit" class="primary-button">Impor ke peta</button>
            </form>
        </section>
        <div class="space-y-6"><section class="border border-line rounded-lg p-5 space-y-4"><h2 class="text-lg">Dari GEE ke PyroGuard</h2><ol class="list-decimal pl-5 space-y-3 text-sm"><li>Unduh skrip ekspor, lalu jalankan pada Code Editor dengan akses ke aset Wilayah.</li><li>Jalankan Tasks untuk Dataset_PyroGuard_Bromo_2023 dan Wilayah_PyroGuard.</li><li>Unduh CSV dan GeoJSON dari Drive, lalu impor melalui formulir ini.</li></ol><a class="outline-button" href="{{ asset('data/pyroguard-gee.js') }}" download>Unduh skrip GEE ↓</a><p class="text-xs text-ink-muted">Skrip menyediakan ekspor GeoTIFF opsional untuk QGIS. Layer raster belum ditayangkan di web; integrasi tile GEE membutuhkan akses proyek Google Cloud.</p></section>
        <section class="border border-line rounded-lg p-5 space-y-4"><h2 class="text-lg">Status dataset</h2><dl class="zone-facts"><div><dt>Batas administrasi lampiran</dt><dd>{{ $metadata['jumlah_fitur'] }} fitur · {{ $metadata['crs'] }}</dd></div><div><dt>Wilayah analisis</dt><dd>{{ isset($dataset['wilayah']) ? count($dataset['wilayah']['features']).' poligon / multipoligon diimpor' : 'Belum diimpor dari aset Wilayah' }}</dd></div><div><dt>Analisis GEE historis</dt><dd>{{ isset($dataset['analisis']) ? count($dataset['analisis']['baris']).' baris · '.$dataset['analisis']['diimpor_pada'] : 'Menunggu CSV hasil ekspor' }}</dd></div></dl><p class="text-xs text-ink-muted">Unggahan tersimpan di server. Autentisitas dan kualitas hasil tetap perlu diperiksa terhadap sumber GEE.</p></section></div>
    </div>
</div>
@endsection
