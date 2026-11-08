<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#eef2f0">
    <title>@yield('judul', 'Pantauan') — PyroGuard Bromo</title>
    <meta name="description" content="Ruang pemantauan kebakaran kawasan Bromo. Jelajahi peta, periksa sumber data, dan laporkan tanda kebakaran.">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
@php($areaAdmin = $areaAdmin ?? false)
<body class="app-shell" x-data="{ menuBuka: false }" @keydown.escape.window="menuBuka = false">
    <a class="skip-link" href="#konten-utama">Lewati navigasi</a>
    <header class="app-topbar">
        <a href="{{ route('pantauan') }}" class="app-brand" aria-label="PyroGuard Bromo, beranda">
            <svg viewBox="0 0 36 36" fill="none" aria-hidden="true"><path d="M5 29 14 13l5 8 4-6 8 14H5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M19 3c4 5 7 6 7 10a6 6 0 0 1-9 5c4-2 1-7 2-15Z" fill="currentColor"/></svg>
            <span>PyroGuard<small>Bromo · Tengger · Semeru</small></span>
        </a>
        <div class="app-context"><span class="context-dot"></span>{{ $areaAdmin ? 'Ruang otoritas' : 'Pemantauan kawasan' }}</div>
        <div class="app-top-actions">
            <button type="button" class="icon-button" onclick="window.toggleModeTerangTinggi()" aria-label="Alihkan kontras tinggi" title="Kontras tinggi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2"/></svg></button>
            @auth
                <span class="account-name">{{ auth()->user()->nama }}</span>
                <form method="POST" action="{{ route('keluar') }}">@csrf<button class="quiet-button" type="submit">Keluar</button></form>
            @else
                <a href="{{ route('masuk') }}" class="quiet-button">Masuk petugas <span aria-hidden="true">↗</span></a>
            @endauth
            <button class="icon-button mobile-menu" type="button" @click="menuBuka = !menuBuka" :aria-expanded="menuBuka" aria-controls="navigasi-aplikasi" aria-label="Buka navigasi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        </div>
    </header>
    <div class="app-frame">
        <aside id="navigasi-aplikasi" class="app-sidebar" :class="{ 'is-open': menuBuka }">
            <div class="workspace-label"><span class="workspace-symbol">B</span><div>Kawasan Bromo<small>{{ $areaAdmin ? 'Administrasi & koordinasi' : 'Pemantauan kebakaran lahan' }}</small></div></div>
            @include('komponen.navigasi', ['areaAdmin' => $areaAdmin])
            <div class="sidebar-bottom">
                <a href="{{ route('demo') }}" class="sidebar-demo"><span>Ruang simulasi</span><span aria-hidden="true">↗</span><small>Uji skenario tanpa mengubah observasi.</small></a>
                <div class="sidebar-footnote">PyroGuard / Bromo<br>Data untuk kesiapsiagaan kawasan</div>
            </div>
        </aside>
        <main id="konten-utama" class="app-sheet {{ $areaAdmin ? 'admin-sheet' : '' }}" tabindex="-1">
            <div class="page-location"><span>{{ $areaAdmin ? 'Otoritas BPBD' : 'Kawasan Bromo' }}</span><span aria-hidden="true">/</span><span>@yield('judul', 'Pantauan')</span></div>
            @if(session('mode_demo') || request()->routeIs('demo'))
                <div class="data-notice"><strong>Mode simulasi.</strong> Angka skenario bukan observasi lapangan.</div>
            @endif
            @if(session('pesan'))<div class="feedback-success" role="status">{{ session('pesan') }}</div>@endif
            @if(session('peringatan'))<div class="data-notice" role="status">{{ session('peringatan') }}</div>@endif
            <details class="provenance-notice">
                <summary><span class="context-dot"></span>Asal data: batas administrasi dari lampiran · data operasional perlu verifikasi <span aria-hidden="true">＋</span></summary>
                <p>Batas peta berasal dari Bromo.zip (atribut Lapak GIS - 2024), bukan batas resmi TNBTS. Cuaca, risiko, titik panas, sumber air, dan jalur yang tersimpan belum memiliki bukti asal observasi; jangan menganggapnya sebagai kondisi aktual. Hasil GEE ditampilkan hanya setelah CSV dan poligon diimpor; analisis historis tidak menunjukkan keadaan saat ini. <a href="{{ route('metode') }}#dataset">Periksa sumber & dataset</a>.</p>
            </details>
            <div class="page-content">@yield('konten')</div>
            <footer class="app-footer"><span>PyroGuard · Kawasan Bromo</span><a href="{{ route('metode') }}#dataset">Sumber data & keterbatasan ↗</a></footer>
        </main>
    </div>
    <nav class="mobile-bottom" aria-label="Navigasi cepat">
        <a href="{{ route('pantauan') }}" @if(request()->routeIs('pantauan')) aria-current="page" @endif>Peta</a>
        <a href="{{ route('evakuasi') }}" @if(request()->routeIs('evakuasi')) aria-current="page" @endif>Logistik</a>
        <a href="{{ route('lapor.index') }}" @if(request()->routeIs('lapor.*')) aria-current="page" @endif>Lapor asap</a>
        <button type="button" @click="menuBuka = !menuBuka" :aria-expanded="menuBuka" aria-controls="navigasi-aplikasi">Menu</button>
    </nav>
    @include('komponen.empty-state-offline')
    @stack('scripts')
</body>
</html>
