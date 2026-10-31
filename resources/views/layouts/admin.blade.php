<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', 'Dasbor Otoritas') — Admin BPBD PyroGuard</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    {{-- Asset bundling Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-paper text-ink min-h-screen flex flex-col antialiased font-sans selection:bg-moss selection:text-white">

    {{-- HEADER ATAS ADMIN DENGAN SAKELAR MODE DEMO (§15 & §7.8) --}}
    <header class="sticky top-0 z-40 h-14 bg-paper/95 backdrop-blur-md border-b border-line px-4 sm:px-6 flex items-center justify-between shrink-0">
        {{-- Kiri: Brand Admin --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.tinjauan') }}" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-forest text-white flex items-center justify-center font-display font-bold text-lg shadow-sm">
                    P
                </span>
                <span class="font-display font-bold text-lg text-ink tracking-tight">
                    Pyro<span class="text-ember">Guard</span> <span class="text-xs font-mono px-2 py-0.5 rounded bg-forest/10 text-forest ml-1 uppercase">BPBD Admin</span>
                </span>
            </a>

            @if(session('mode_demo'))
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-amber-500 text-white animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-white"></span>
                    <span>MODE DEMO AKTIF</span>
                </span>
            @endif
        </div>

        {{-- Kanan: Sakelar Mode Demo, Status Integrasi, Profil & Keluar --}}
        <div class="flex items-center gap-3">
            {{-- Sakelar Global Mode Demo (FR-DEM-01) --}}
            <form action="{{ route('admin.toggle-demo') }}" method="POST">
                @csrf
                <button 
                    type="submit" 
                    title="Beralih antara data cuaca aktual vs skenario simulasi kemarau"
                    class="h-9 px-3 rounded-xl border text-xs font-mono font-semibold flex items-center gap-2 transition {{ session('mode_demo') ? 'bg-amber-500 text-white border-amber-600 shadow-sm' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    <span>⚡ {{ session('mode_demo') ? 'Matikan Simulasi' : 'Aktifkan Mode Demo' }}</span>
                </button>
            </form>

            {{-- Pintasan Kembali ke Pantauan Publik --}}
            <a 
                href="{{ url('/') }}" 
                target="_blank"
                class="hidden md:inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-line bg-paper-2 hover:bg-paper-3 text-xs font-mono text-ink transition"
            >
                <span>Lihat Publik</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>

            {{-- Profil & Logout --}}
            <div class="flex items-center gap-2 pl-2 border-l border-line text-xs font-mono">
                <span class="hidden sm:inline-block font-semibold text-ink">{{ auth()->user()->nama }}</span>
                <form action="{{ route('keluar') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 text-ink-muted hover:text-danger rounded-lg transition" title="Keluar Akun">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- CONTAINER SHELL ADMIN (SIDEBAR GELAP + KONTEN PADAT) --}}
    <div class="flex-1 flex max-w-[1600px] w-full mx-auto">
        {{-- SIDEBAR GELAP BG-FOREST (250px) --}}
        <aside class="w-64 bg-forest text-white p-5 flex flex-col justify-between shrink-0 hidden md:flex border-r border-line">
            <div class="space-y-6">
                <div>
                    <span class="text-[10px] font-mono uppercase tracking-wider text-white/60 font-bold block mb-3 px-3">
                        Menu Otoritas BPBD
                    </span>
                    <nav class="space-y-1 text-sm font-medium">
                        <a 
                            href="{{ route('admin.tinjauan') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.tinjauan') ? 'bg-white/15 text-white font-semibold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span>Tinjauan Prediksi</span>
                        </a>

                        <a 
                            href="{{ route('admin.siaga') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.siaga') ? 'bg-white/15 text-white font-semibold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Persetujuan Siaga</span>
                        </a>

                        <a 
                            href="{{ route('admin.siaran') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.siaran') ? 'bg-white/15 text-white font-semibold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            <span>Editor Siaran</span>
                        </a>

                        <a 
                            href="{{ route('admin.log-siaran') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.log-siaran') ? 'bg-white/15 text-white font-semibold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Log & Audit Siaran</span>
                        </a>

                        <a 
                            href="{{ route('admin.laporan') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.laporan') ? 'bg-white/15 text-white font-semibold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Laporan Warga</span>
                        </a>
                    </nav>
                </div>
            </div>

            {{-- KOTAK KESEHATAN INTEGRASI API (§7.8) --}}
            <div class="p-3 bg-white/5 rounded-2xl border border-white/10 text-xs font-mono space-y-2">
                <span class="text-white/60 block text-[10px] font-bold uppercase">Status Layanan Eksternal:</span>
                
                <div class="flex items-center justify-between">
                    <span class="text-white/80">Open-Meteo:</span>
                    <span class="text-emerald-400 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Normal
                    </span>
                </div>

                @php
                    $botTelegram = app(\App\Services\LayananBotTelegram::class);
                    $pushService = app(\App\Services\LayananNotifikasiPush::class);
                @endphp

                <div class="flex items-center justify-between">
                    <span class="text-white/80">Bot Telegram:</span>
                    <span class="font-bold flex items-center gap-1 {{ $botTelegram->isTerkonfigurasi() ? 'text-emerald-400' : 'text-amber-300' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $botTelegram->isTerkonfigurasi() ? 'bg-emerald-400' : 'bg-amber-300' }}"></span>
                        {{ $botTelegram->isTerkonfigurasi() ? 'Aktif' : 'Simulasi' }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-white/80">Web-Push:</span>
                    <span class="font-bold flex items-center gap-1 {{ $pushService->isTerkonfigurasi() ? 'text-emerald-400' : 'text-amber-300' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $pushService->isTerkonfigurasi() ? 'bg-emerald-400' : 'bg-amber-300' }}"></span>
                        {{ $pushService->isTerkonfigurasi() ? 'Aktif' : 'Simulasi' }}
                    </span>
                </div>
            </div>
        </aside>

        {{-- AREA KONTEN UTAMA PADAT ADMIN --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8 min-w-0 overflow-y-auto">
            @if(session('pesan'))
                <div class="mb-5 p-3.5 bg-safe/10 border border-safe/30 rounded-xl text-xs font-mono text-safe font-semibold flex items-center gap-2">
                    <span>✓</span>
                    <span>{{ session('pesan') }}</span>
                </div>
            @endif

            @if(session('peringatan'))
                <div class="mb-5 p-3.5 bg-amber-500/10 border border-amber-600/30 rounded-xl text-xs font-mono text-warn font-semibold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('peringatan') }}</span>
                </div>
            @endif

            @yield('konten')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
