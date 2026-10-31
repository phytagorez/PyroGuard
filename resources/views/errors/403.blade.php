@extends('layouts.publik')

@section('judul', 'Akses Khusus Petugas (403)')

@section('konten')
<div class="max-w-md mx-auto py-12 px-4 text-center space-y-6">
    <div class="w-20 h-20 rounded-3xl bg-warn/15 text-warn border-2 border-warn/30 mx-auto flex items-center justify-center text-4xl shadow-card">
        🔒
    </div>

    <div class="space-y-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-paper-3 text-ink-muted border border-line">
            KODE: 403 · HAK AKSES TERBATAS
        </span>
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-ink tracking-tight">
            Akses Khusus Petugas Lapangan
        </h1>
        <p class="text-sm text-ink-muted leading-relaxed">
            {{ $pesan ?? 'Halaman ini hanya untuk petugas. Anda masih bisa melihat status kawasan.' }}
        </p>
    </div>

    <div class="p-4 bg-paper-2 rounded-2xl border border-line text-xs font-mono text-ink-muted text-left space-y-2">
        <div class="flex items-center justify-between">
            <span>Status Anda:</span>
            <strong class="text-ink uppercase">{{ auth()->check() ? (auth()->user()->peran . ' (' . auth()->user()->nama . ')') : 'Tamu / Belum Masuk' }}</strong>
        </div>
        <div class="flex items-center justify-between">
            <span>Peran yang Diperlukan:</span>
            <span class="text-forest font-semibold">Petugas TNBTS / Admin BPBD</span>
        </div>
    </div>

    <div class="space-y-3 pt-2">
        <a 
            href="{{ url('/') }}" 
            class="w-full h-12 bg-forest hover:bg-moss active:scale-98 text-white rounded-xl font-display font-bold text-sm transition flex items-center justify-center gap-2 shadow-sm"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Pantauan Kawasan</span>
        </a>

        @if(!auth()->check() || auth()->user()->isPublik())
            <a 
                href="{{ route('masuk') }}" 
                class="w-full h-12 bg-paper-2 hover:bg-paper-3 text-ink border border-line rounded-xl font-display font-semibold text-sm transition flex items-center justify-center gap-2"
            >
                <span>Masuk dengan Akun Petugas</span>
            </a>
        @endif
    </div>
</div>
@endsection
