@extends('layouts.publik')

@section('judul', 'Masuk Petugas & Pengguna')

@section('konten')
<div class="max-w-md mx-auto space-y-6 pb-20" x-data="{
    isiAkun(email, pass) {
        document.getElementById('inputLogin').value = email;
        document.getElementById('inputPassword').value = pass;
    }
}">
    {{-- HEADER --}}
    <div class="text-center space-y-1">
        <div class="flex items-center justify-center gap-2 mb-2">
            <span class="w-8 h-8 rounded-lg bg-forest text-white flex items-center justify-center font-display font-bold text-lg shadow-sm">
                P
            </span>
            <span class="font-display font-bold text-xl text-ink">
                Pyro<span class="text-ember">Guard</span>
            </span>
        </div>
        <h1 class="text-2xl font-display font-bold text-ink tracking-tight">
            Masuk ke Sistem
        </h1>
        <p class="text-xs text-ink-muted">
            Akses verifikasi lapangan petugas TNBTS & kendali siaga BPBD
        </p>
    </div>

    {{-- ALERT GOOGLE OAUTH / NOTIFIKASI LAINNYA --}}
    @if(session('peringatan_google'))
        <div class="p-4 bg-amber-500/10 border border-amber-600/30 rounded-2xl text-xs font-mono text-warn space-y-1" role="alert">
            <strong class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Informasi Integrasi Google</span>
            </strong>
            <p class="text-[11px] leading-relaxed text-ink">
                {{ session('peringatan_google') }}
            </p>
        </div>
    @endif

    @if(session('peringatan'))
        <div class="p-3 bg-amber-500/10 border border-amber-600/30 rounded-xl text-xs font-mono text-warn">
            {{ session('peringatan') }}
        </div>
    @endif

    @if($errors->has('login'))
        <div class="p-3 bg-danger/10 border border-danger/30 rounded-xl text-xs font-mono text-danger">
            {{ $errors->first('login') }}
        </div>
    @endif

    {{-- KARTU FORM MASUK --}}
    <div class="p-6 bg-paper-2 border border-line rounded-3xl shadow-card space-y-5">
        {{-- TOMBOL GOOGLE OAUTH --}}
        <a 
            href="{{ route('auth.google') }}" 
            class="w-full h-12 bg-white hover:bg-gray-50 border border-line text-ink rounded-xl font-display font-semibold text-sm transition flex items-center justify-center gap-3 shadow-sm active:scale-98"
        >
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Masuk dengan Google</span>
        </a>

        <div class="relative flex items-center justify-center">
            <div class="border-t border-line w-full"></div>
            <span class="bg-paper-2 px-3 text-[11px] font-mono uppercase text-ink-muted shrink-0">atau email / ponsel</span>
            <div class="border-t border-line w-full"></div>
        </div>

        {{-- FORM MANUAL --}}
        <form action="{{ route('masuk.post') }}" method="POST" class="space-y-4">
            @csrf

            <div class="space-y-1">
                <label for="inputLogin" class="block text-xs font-mono font-bold uppercase text-ink">
                    Email atau Nomor Ponsel
                </label>
                <input 
                    type="text" 
                    id="inputLogin"
                    name="login" 
                    value="{{ old('login') }}"
                    placeholder="nama@tnbts.go.id atau 08123456789" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest"
                >
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="inputPassword" class="block text-xs font-mono font-bold uppercase text-ink">
                        Kata Sandi
                    </label>
                </div>
                <input 
                    type="password" 
                    id="inputPassword"
                    name="password" 
                    placeholder="••••••••" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest"
                >
            </div>

            <div class="flex items-center justify-between text-xs font-mono">
                <label class="flex items-center gap-2 cursor-pointer select-none text-ink-muted">
                    <input type="checkbox" name="ingat" value="1" class="rounded border-line text-forest focus:ring-forest">
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <button 
                type="submit" 
                class="w-full h-12 bg-forest hover:bg-moss active:scale-98 text-white rounded-xl font-display font-bold text-sm transition flex items-center justify-center gap-2 shadow-sm"
            >
                <span>Masuk Sekarang</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>

        {{-- TAUTAN DAFTAR PETUGAS --}}
        <div class="pt-2 text-center text-xs font-mono text-ink-muted">
            <span>Petugas lapangan baru? </span>
            <a href="{{ route('daftar-petugas') }}" class="text-forest hover:underline font-bold">
                Daftar akun dinas di sini
            </a>
        </div>
    </div>

    {{-- KOTAK AKUN DEMO CEPAT (UNTUK PENJURIAN / PENGUJIAN) --}}
    <div class="p-4 bg-paper-2 border border-line rounded-2xl text-xs font-mono space-y-2.5">
        <div class="flex items-center justify-between">
            <span class="text-ink font-bold uppercase text-[11px] flex items-center gap-1.5">
                <span>⚡</span>
                <span>Akun Pengujian Cepat</span>
            </span>
            <span class="text-[10px] text-ink-muted">Klik untuk isi otomatis</span>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button 
                type="button" 
                @click="isiAkun('agus@tnbts.go.id', 'password')"
                class="p-2.5 bg-paper hover:bg-paper-3 border border-line rounded-xl text-left transition active:scale-95 space-y-0.5"
            >
                <strong class="block text-ink text-[11px]">Pak Agus (Petugas)</strong>
                <span class="block text-[10px] text-ink-muted">agus@tnbts.go.id</span>
            </button>

            <button 
                type="button" 
                @click="isiAkun('admin@bpbd.go.id', 'password')"
                class="p-2.5 bg-paper hover:bg-paper-3 border border-line rounded-xl text-left transition active:scale-95 space-y-0.5"
            >
                <strong class="block text-ink text-[11px]">Ibu Sri (Admin BPBD)</strong>
                <span class="block text-[10px] text-ink-muted">admin@bpbd.go.id</span>
            </button>
        </div>
    </div>
</div>
@endsection
