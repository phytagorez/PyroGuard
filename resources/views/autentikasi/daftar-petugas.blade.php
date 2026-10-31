@extends('layouts.publik')

@section('judul', 'Pendaftaran Petugas Lapangan TNBTS')

@section('konten')
<div class="max-w-md mx-auto space-y-6 pb-20">
    {{-- HEADER --}}
    <div class="text-center space-y-1">
        <a href="{{ route('masuk') }}" class="inline-flex items-center gap-1 text-xs font-mono text-ink-muted hover:text-ink transition mb-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>Kembali ke Halaman Masuk</span>
        </a>
        <h1 class="text-2xl font-display font-bold text-ink tracking-tight">
            Pendaftaran Petugas Lapangan
        </h1>
        <p class="text-xs text-ink-muted leading-relaxed">
            Khusus personel Balai Besar TNBTS, Masyarakat Peduli Api (MPA), dan relawan terdaftar.
        </p>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-danger/10 border border-danger/30 rounded-2xl text-xs font-mono text-danger space-y-1">
            <strong class="block font-bold">Harap perbaiki data pendaftaran:</strong>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KARTU FORM DAFTAR --}}
    <div class="p-6 bg-paper-2 border border-line rounded-3xl shadow-card space-y-5">
        <form action="{{ route('daftar-petugas.post') }}" method="POST" class="space-y-4">
            @csrf

            <div class="space-y-1">
                <label for="nama" class="block text-xs font-mono font-bold uppercase text-ink">
                    Nama Lengkap & Pangkat / Jabatan
                </label>
                <input 
                    type="text" 
                    id="nama"
                    name="nama" 
                    value="{{ old('nama') }}"
                    placeholder="Contoh: Agus Santoso (Polhut TNBTS)" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest"
                >
            </div>

            <div class="space-y-1">
                <label for="email" class="block text-xs font-mono font-bold uppercase text-ink">
                    Email Dinas / Pribadi
                </label>
                <input 
                    type="email" 
                    id="email"
                    name="email" 
                    value="{{ old('email') }}"
                    placeholder="agus@tnbts.go.id" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest"
                >
            </div>

            <div class="space-y-1">
                <label for="nomor_telepon" class="block text-xs font-mono font-bold uppercase text-ink">
                    Nomor WhatsApp / Telepon Siaga
                </label>
                <input 
                    type="text" 
                    id="nomor_telepon"
                    name="nomor_telepon" 
                    value="{{ old('nomor_telepon') }}"
                    placeholder="081234567890" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest"
                >
            </div>

            <div class="space-y-1">
                <label for="password" class="block text-xs font-mono font-bold uppercase text-ink">
                    Kata Sandi (Minimal 6 Karakter)
                </label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    placeholder="••••••••" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest"
                >
            </div>

            <div class="space-y-1">
                <label for="password_confirmation" class="block text-xs font-mono font-bold uppercase text-ink">
                    Ulangi Kata Sandi
                </label>
                <input 
                    type="password" 
                    id="password_confirmation"
                    name="password_confirmation" 
                    placeholder="••••••••" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-paper text-sm text-ink focus:outline-none focus:ring-2 focus:ring-forest"
                >
            </div>

            <div class="p-3 bg-paper rounded-xl border border-line text-xs font-mono text-ink-muted">
                🛡️ Akun Anda akan otomatis terdaftar sebagai petugas verifikator lapangan kawasan Bromo.
            </div>

            <button 
                type="submit" 
                class="w-full h-12 bg-forest hover:bg-moss active:scale-98 text-white rounded-xl font-display font-bold text-sm transition flex items-center justify-center gap-2 shadow-sm"
            >
                <span>Daftar sebagai Petugas</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>

        <div class="pt-2 text-center text-xs font-mono text-ink-muted">
            <span>Sudah memiliki akun? </span>
            <a href="{{ route('masuk') }}" class="text-forest hover:underline font-bold">
                Masuk di sini
            </a>
        </div>
    </div>
</div>
@endsection
