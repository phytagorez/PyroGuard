@extends('layouts.publik')

@section('judul', 'Pengaturan Notifikasi & Peringatan Bromo')

@section('konten')
@php
    $zonas = \App\Models\Zona::all(['id', 'kode', 'nama']);
    $kodeTautan = auth()->user()?->kode_tautan_telegram ?? 'WST-' . strtoupper(Str::random(6));
@endphp

<div
    x-data="{
        statusPush: 'default', // 'default' | 'granted' | 'denied'
        zonaDipilih: ['BRM-04', 'BRM-03'],
        modalIosBuka: false,

        init() {
            if ('Notification' in window) {
                this.statusPush = Notification.permission;
            }
        },

        mintaIzinPush() {
            if (!('Notification' in window)) {
                alert('Browser Anda tidak mendukung Web Push Notification.');
                return;
            }

            Notification.requestPermission().then((permission) => {
                this.statusPush = permission;
                if (permission === 'granted') {
                    if (window.Swal) {
                        window.Swal.fire({
                            title: 'Izin notifikasi diberikan',
                            text: 'Browser mengizinkan notifikasi. Pengiriman siaran masih memerlukan langganan push dan konfigurasi kanal oleh pengelola.',
                            icon: 'success',
                            confirmButtonColor: '#173D2C',
                        });
                    } else {
                        alert('Izin browser diberikan. Pengiriman memerlukan langganan push yang aktif.');
                    }
                } else if (permission === 'denied') {
                    alert('Izin notifikasi diblokir browser. Anda dapat mengaktifkannya melalui pengaturan ikon gembok di bilah alamat.');
                }
            });
        },

        toggleZona(kode) {
            if (this.zonaDipilih.includes(kode)) {
                this.zonaDipilih = this.zonaDipilih.filter(z => z !== kode);
            } else {
                this.zonaDipilih.push(kode);
            }
        }
    }"
    class="notification-workspace"
>
    {{-- HEADER DUA KALIMAT PENJELASAN (§7.4 design.md) --}}
    <div>
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ url('/') }}" class="text-xs font-sans text-ink-muted hover:text-ink transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Kembali ke Pantauan</span>
            </a>
            <span class="text-xs text-ink-muted font-sans">•</span>
            <span class="text-xs font-sans text-forest font-bold tracking-normal">Kanal Siaga</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-ink tracking-tight">
            Peringatan Dini & Notifikasi
        </h1>
        <p class="text-sm text-ink-muted mt-2 leading-relaxed">
            Dapatkan siaga bahaya kebakaran Bromo langsung ke ponsel Anda. Notifikasi hanya dikirim saat status Waspada sekali sehari per zona, status Siaga 1 disetujui otoritas BPBD, atau terdapat laporan titik api dalam radius 3 km dari lokasi Anda.
        </p>
    </div>

    {{-- 1. KARTU AKTIFKAN WEB PUSH BROWSER (TOMBOL 56 PX - §7.4) --}}
    <div class="p-5 sm:p-6 bg-paper-2 border border-line rounded-lg shadow-none space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="font-sans text-xs font-bold text-forest tracking-normal">Kanal Utama Browser</span>
                <h2 class="text-lg font-display font-bold text-ink mt-0.5">Notifikasi Web Browser</h2>
                <p class="text-xs text-ink-muted mt-1 leading-relaxed">
                    Menerima siaran darurat langsung di layar perangkat tanpa perlu mengunduh aplikasi tambahan di App Store atau Play Store.
                </p>
            </div>

        </div>

        {{-- Status Perizinan Saat Ini --}}
        <div class="p-3 bg-paper rounded-md border border-line flex items-center justify-between text-xs font-sans">
            <span class="text-ink-muted">Status Perangkat:</span>
            <template x-if="statusPush === 'granted'">
                <span class="text-safe font-bold flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-safe"></span> Aktif & Terdaftar
                </span>
            </template>
            <template x-if="statusPush === 'denied'">
                <span class="text-danger font-bold flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-danger"></span> Diblokir di Browser
                </span>
            </template>
            <template x-if="statusPush === 'default'">
                <span class="text-ink-muted font-bold flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-warn"></span> Belum Diaktifkan
                </span>
            </template>
        </div>

        {{-- Tombol 56 px --}}
        <button
            type="button"
            @click="mintaIzinPush()"
            :class="statusPush === 'granted' ? 'bg-forest/80 cursor-default' : 'bg-forest hover:bg-moss cursor-pointer active:scale-[0.98]'"
            class="w-full h-14 text-white rounded-md font-display font-bold text-base transition flex items-center justify-center gap-2 shadow-sheet"
        >
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span x-text="statusPush === 'granted' ? 'Notifikasi Web Push Aktif' : 'Aktifkan notifikasi peramban'"></span>
        </button>

        {{-- Panduan Pemasangan iOS PWA (§7.4 & §14.4) --}}
        <div class="pt-1 text-center">
            <button
                type="button"
                @click="modalIosBuka = true"
                class="text-xs font-sans text-ink-muted hover:text-ink underline min-h-[44px] inline-flex items-center"
            >
                Pengguna iPhone / iOS? Lihat panduan notifikasi PWA →
            </button>
        </div>
    </div>

    {{-- 2. PEMILIHAN LANGGANAN ZONA (§7.4) --}}
    <div class="p-5 sm:p-6 bg-paper-2 border border-line rounded-lg shadow-none space-y-4">
        <div>
            <span class="font-sans text-xs font-bold text-forest tracking-normal">Wilayah Pantauan</span>
            <h2 class="text-base font-display font-bold text-ink mt-0.5">Pilih Zona yang Ingin Dipantau</h2>
            <p class="text-xs text-ink-muted mt-0.5">
                Peringatan dini Waspada akan diprioritaskan untuk zona yang Anda centang:
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-sans">
            @foreach($zonas as $zona)
                <label
                    @click="toggleZona('{{ $zona->kode }}')"
                    :class="zonaDipilih.includes('{{ $zona->kode }}') ? 'bg-forest/10 border-forest text-forest font-bold' : 'bg-paper border-line text-ink'"
                    class="flex items-center justify-between p-3 rounded-md border cursor-pointer select-none transition min-h-[44px] active:scale-[0.98]"
                >
                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :checked="zonaDipilih.includes('{{ $zona->kode }}')"
                            class="rounded border-line text-forest focus:ring-forest"
                        >
                        <span>{{ $zona->kode }}</span>
                    </div>
                    <span class="text-xs font-sans truncate max-w-[120px] text-ink-muted">{{ $zona->nama }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- 3. HUBUNGKAN TELEGRAM (TAUTAN /START KODE - §7.4 & §14.3) --}}
    <div class="p-5 sm:p-6 bg-paper-2 border border-line rounded-lg shadow-none space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="font-sans text-xs font-bold text-[#1E749B] tracking-normal">Kanal Telegram</span>
                <h2 class="text-base font-display font-bold text-ink mt-0.5">Hubungkan ke Telegram Bot</h2>
                <p class="text-xs text-ink-muted mt-1 leading-relaxed">
                    Terima pesan darurat melalui bot privat dengan kode sekali pakai atau ikuti kanal publik siaran resmi.
                </p>
            </div>

        </div>

        <div class="p-3 bg-paper rounded-md border border-line text-xs font-sans space-y-1">
            <span class="text-ink-muted text-xs font-bold block">Tautan Penautan Bot Privat:</span>
            <code class="block text-forest font-bold break-all select-all">
                t.me/PyroGuardBot?start={{ $kodeTautan }}
            </code>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <a
                href="https://t.me/PyroGuardBot?start={{ $kodeTautan }}"
                target="_blank"
                class="min-h-[46px] bg-[#1E749B] hover:brightness-105 active:scale-[0.98] text-white rounded-md font-display font-semibold text-xs transition flex items-center justify-center gap-2 shadow-none text-center"
            >
                <span>Buka Bot Privat</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>

            <a
                href="https://t.me/PyroGuardBromo"
                target="_blank"
                class="min-h-[46px] bg-paper hover:bg-paper-3 border border-line text-ink rounded-md font-display font-semibold text-xs transition flex items-center justify-center gap-2 text-center active:scale-[0.98]"
            >
                <span>Kanal Publik @PyroGuard</span>
            </a>
        </div>
    </div>

    {{-- MODAL PANDUAN NOTIFIKASI IOS PWA (§7.4 & §14.4) --}}
    <div
        x-show="modalIosBuka"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/60 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-paper-2 border border-line p-6 rounded-lg max-w-sm w-full space-y-4 shadow-sheet" @click.away="modalIosBuka = false">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-base text-ink">Panduan Notifikasi di iPhone (iOS)</h3>
                <button type="button" @click="modalIosBuka = false" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-paper-3 text-ink-muted hover:text-ink transition"></button>
            </div>

            <p class="text-xs text-ink-muted leading-relaxed font-sans">
                Apple mewajibkan aplikasi web dipasang ke Layar Utama (*Home Screen*) sebelum dapat mengirim notifikasi:
            </p>

            <ol class="list-decimal pl-5 space-y-2 text-xs font-sans text-ink">
                <li>Buka Safari, ketuk tombol <strong>Bagikan / Share</strong> (ikon kotak berpanah ke atas) di bilah bawah.</li>
                <li>Gulir ke bawah dan pilih <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong>.</li>
                <li>Buka ikon PyroGuard dari layar utama Anda, lalu ketuk tombol <strong>"Aktifkan notifikasi"</strong> di halaman ini.</li>
            </ol>

            <button
                type="button"
                @click="modalIosBuka = false"
                class="w-full min-h-[44px] rounded-md bg-forest text-white text-xs font-sans font-bold hover:bg-moss transition active:scale-[0.98]"
            >
                Mengerti
            </button>
        </div>
    </div>
</div>
@endsection
