<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#173D2C">
    <title>@yield('judul', 'Pantauan Risiko') — PyroGuard Bromo</title>
    <meta name="description" content="Sistem Peringatan Dini Kebakaran Lahan Kawasan Bromo Tengger Semeru. Pantau indeks bahaya api, kekeringan savana, dan jalur evakuasi secara langsung.">

    {{-- PWA Manifest & Icons (§12 & §18 design.md) --}}
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    {{-- Asset bundling Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-paper text-ink min-h-screen flex flex-col antialiased font-sans selection:bg-moss selection:text-white pb-24 lg:pb-0">

    {{-- HEADER RINGKAS STICKY 52px (§7.1 design.md) --}}
    <header class="sticky top-0 z-40 h-[52px] bg-paper/95 backdrop-blur-sm border-b border-line px-4 sm:px-6 flex items-center justify-between shrink-0">
        {{-- Kiri: Logo & Chip Kawasan Bromo --}}
        <div class="flex items-center gap-2.5">
            <a href="{{ url('/') }}" class="flex items-center gap-2 group" aria-label="Ke Halaman Utama Pantauan PyroGuard">
                <span class="w-8 h-8 rounded-lg bg-forest text-white flex items-center justify-center font-display font-bold text-lg shadow-sm group-hover:bg-moss transition">
                    P
                </span>
                <span class="font-display font-bold text-lg text-ink tracking-tight">
                    Pyro<span class="text-ember">Guard</span>
                </span>
            </a>

            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-paper-3 text-forest font-semibold border border-line">
                Bromo
            </span>

            @if(request()->is('demo*') || session('mode_demo'))
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-amber-500 text-ink font-bold animate-pulse">
                    MODE DEMO
                </span>
            @endif
        </div>

        {{-- Kanan: Aksi Cepat (Mode Demo, Mode Terang Tinggi, Notifikasi, Masuk) --}}
        <div class="flex items-center gap-1.5 sm:gap-2">
            {{-- Tombol Mode Demo (§6.13) --}}
            <a 
                href="{{ url('/demo') }}" 
                title="Buka Mode Demo Interaktif & Penggeser Hari Kemarau (§6.13)"
                class="px-2.5 py-1.5 flex items-center gap-1 rounded-lg border border-line text-[11px] font-mono font-bold transition {{ request()->is('demo*') || session('mode_demo') ? 'bg-amber-500 text-ink border-amber-600 shadow-xs' : 'text-ink-muted hover:text-ink hover:bg-paper-2' }}"
            >
                <span>🎚️ Demo</span>
            </a>

            {{-- Tombol Mode Terang Tinggi (§11) --}}
            <button 
                type="button" 
                onclick="window.toggleModeTerangTinggi()"
                title="Alihkan Mode Terang Tinggi (Kontras Terik Matahari)"
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-line text-ink-muted hover:text-ink hover:bg-paper-2 transition text-xs"
                aria-label="Mode Terang Tinggi"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>

            {{-- Ikon Notifikasi Lonceng --}}
            <a 
                href="{{ url('/notifikasi') }}" 
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-line text-ink-muted hover:text-ink hover:bg-paper-2 transition relative"
                aria-label="Pengaturan Peringatan & Notifikasi"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-ember"></span>
            </a>

            {{-- Masuk / Akun --}}
            @auth
                <a 
                    href="{{ auth()->user()->isPetugas() ? url('/petugas') : (auth()->user()->isAdmin() ? url('/admin') : url('/')) }}" 
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-mono font-medium rounded-lg bg-paper-2 border border-line text-ink hover:bg-paper-3 transition"
                >
                    <span class="w-2 h-2 rounded-full bg-forest"></span>
                    <span class="truncate max-w-[120px]">{{ auth()->user()->nama }}</span>
                </a>
            @else
                <a 
                    href="{{ route('masuk') }}" 
                    class="hidden sm:inline-flex items-center px-3 py-1.5 text-xs font-mono font-medium rounded-lg border border-line text-ink hover:bg-paper-2 transition"
                >
                    Petugas
                </a>
            @endauth
        </div>
    </header>

    {{-- KONTEN UTAMA DENGAN STRUKTUR DESKTOP 240px NAV + 8 KOLOM PETA + 4 KOLOM PANEL (§4 & §12) --}}
    <div class="flex-1 max-w-7xl w-full mx-auto flex">
        {{-- DESKTOP SIDEBAR NAV (≥ 1024 px) --}}
        <nav class="hidden lg:flex flex-col w-60 border-r border-line p-5 space-y-6 shrink-0 bg-paper">
            <div class="space-y-1">
                <span class="text-[10px] font-mono uppercase tracking-wider text-ink-muted font-bold block mb-2 px-3">
                    Navigasi Kawasan
                </span>
                <a 
                    href="{{ url('/') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->is('/') ? 'bg-forest text-white font-semibold' : 'text-ink hover:bg-paper-2' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>Pantauan Risiko</span>
                </a>

                <a 
                    href="{{ url('/evakuasi') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->is('evakuasi*') ? 'bg-forest text-white font-semibold' : 'text-ink hover:bg-paper-2' }}"
                >
                    <svg class="w-5 h-5 text-evac" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Jalur & Embung</span>
                </a>

                <a 
                    href="{{ url('/lapor') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->is('lapor*') ? 'bg-forest text-white font-semibold' : 'text-ink hover:bg-paper-2' }}"
                >
                    <svg class="w-5 h-5 text-ember" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Lapor Asap</span>
                </a>

                <a 
                    href="{{ url('/notifikasi') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->is('notifikasi*') ? 'bg-forest text-white font-semibold' : 'text-ink hover:bg-paper-2' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span>Notifikasi</span>
                </a>

                <a 
                    href="{{ url('/metode') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->is('metode*') ? 'bg-forest text-white font-semibold' : 'text-ink hover:bg-paper-2' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Metode & Formula</span>
                </a>
            </div>

            {{-- Info Kontak Darurat Bromo --}}
            <div class="mt-auto p-3.5 bg-paper-2 rounded-xl border border-line text-xs space-y-1.5 font-mono">
                <span class="text-ink-muted block text-[10px] font-bold uppercase">Panggilan Darurat</span>
                <div class="flex items-center justify-between">
                    <span class="text-ink font-bold">BPBD Jatim</span>
                    <a href="tel:112" class="text-danger font-bold underline">112</a>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-ink font-bold">Pos TNBTS</span>
                    <span class="text-ink-muted">0341-491828</span>
                </div>
            </div>
        </nav>

        {{-- AREA KONTEN TENGAH / HALAMAN --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8 min-w-0">
            @yield('konten')
        </main>
    </div>

    {{-- NAVIGASI BAWAH MOBILE (4 ITEM TARGET >= 44x44 px - §4 & §11) --}}
    <nav class="fixed bottom-0 inset-x-0 z-40 h-16 bg-paper/95 backdrop-blur-md border-t border-line flex items-center justify-around px-2 lg:hidden">
        <a 
            href="{{ url('/') }}" 
            class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] text-xs font-medium transition {{ request()->is('/') ? 'text-forest font-bold' : 'text-ink-muted hover:text-ink' }}"
        >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span>Pantauan</span>
        </a>

        <a 
            href="{{ url('/lapor') }}" 
            class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] text-xs font-medium transition {{ request()->is('lapor*') ? 'text-ember font-bold' : 'text-ink-muted hover:text-ink' }}"
        >
            <svg class="w-5 h-5 mb-0.5 text-ember" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Lapor</span>
        </a>

        <a 
            href="{{ url('/notifikasi') }}" 
            class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] text-xs font-medium transition {{ request()->is('notifikasi*') ? 'text-forest font-bold' : 'text-ink-muted hover:text-ink' }}"
        >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span>Notifikasi</span>
        </a>

        <a 
            href="{{ url('/metode') }}" 
            class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] text-xs font-medium transition {{ request()->is('metode*') || request()->is('evakuasi*') ? 'text-forest font-bold' : 'text-ink-muted hover:text-ink' }}"
        >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Lainnya</span>
        </a>
    </nav>

    {{-- Komponen Luring PWA (§6.15 design.md) --}}
    @include('komponen.empty-state-offline')

    @stack('scripts')
</body>
</html>
