@props([
    'zonaTertinggi' => null, // Instance Zona dengan risiko tertinggi
    'kesegaran' => null,     // Array status kesegaran data
])

@php
    $risiko = $zonaTertinggi?->risikoTerbaru;
    $status = $risiko?->status_siaga === 'SIAGA_1' ? 'SIAGA 1' : ($risiko?->kategori_risiko ?? 'AMAN');

    $isSiaga = in_array($status, ['SIAGA 1', 'SIAGA OTOMATIS']);
    $isBahaya = $status === 'BAHAYA';
    $isWaspada = $status === 'WASPADA';

    // Warna latar banner mengikuti status tertinggi kawasan (§6.1 design.md)
    $bgClass = match (true) {
        $isSiaga => 'bg-siaga text-white',
        $isBahaya => 'bg-danger text-white',
        $isWaspada => 'bg-warn text-white',
        default => 'bg-forest text-white',
    };

    // Instruksi satu kalimat tegas (maksimal 12 kata sesuai §13 copy rules)
    $instruksi = match (true) {
        $isSiaga => 'Dilarang keras menyalakan api terbuka. Ketahui jalur evakuasi terdekat.',
        $isBahaya => 'Dilarang membuat api terbuka, membakar sampah, atau menyalakan flare.',
        $isWaspada => 'Jangan membakar semak dan pastikan puntung rokok padam sepenuhnya.',
        default => 'Kawasan relatif aman. Tetap waspada dan laporkan bila melihat asap.',
    };

    $cdd = $risiko?->hari_tanpa_hujan ?? 0;
    $fdi = $risiko?->indeks_bahaya_api ?? 0;
    $angin = round($risiko?->kecepatan_angin_kmj ?? 0);
    $arahAngin = strtolower($risiko?->arah_angin ?? 'tenggara');
    $waktuPembaruan = $risiko?->dihitung_pada ? \Carbon\Carbon::parse($risiko->dihitung_pada)->format('H:i') : '07:00';
    $durasiJam = $kesegaran['durasi_jam'] ?? 0;
@endphp

<div class="relative w-full rounded-[18px] p-5 shadow-sheet overflow-hidden transition-all duration-300 {{ $bgClass }}">
    {{-- Pita Peringatan Kedaluwarsa Data jika > 2 jam atau > 6 jam (§6.1 & §6.6 design.md) --}}
    @if ($durasiJam > 6)
        <div class="mb-3 -mt-2 -mx-2 px-3 py-1.5 bg-black/40 border border-white/30 rounded-lg flex items-center justify-between text-xs font-mono">
            <span class="flex items-center gap-1.5 font-bold text-amber-300">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                DATA TERAKHIR {{ $durasiJam }} JAM LALU
            </span>
            <button onclick="window.location.reload()" class="underline hover:text-white transition">Muat ulang</button>
        </div>
    @elseif ($durasiJam >= 2)
        <div class="mb-3 -mt-2 -mx-2 px-3 py-1 bg-amber-500/30 border border-amber-400/40 rounded-lg text-xs font-mono text-amber-100 flex items-center justify-between">
            <span>DATA {{ $durasiJam }} JAM LALU · PENGAMBILAN BERIKUTNYA DICOBA</span>
            <button onclick="window.location.reload()" class="underline hover:text-white">Segarkan</button>
        </div>
    @endif

    {{-- Baris 1: Header metadata mono --}}
    <div class="flex items-center justify-between text-[11px] font-mono tracking-wider opacity-90 mb-1">
        <span class="uppercase font-semibold">
            STATUS KAWASAN TERTINGGI · {{ $zonaTertinggi?->kode ?? 'BRM-04' }}
        </span>
        <span class="px-2 py-0.5 rounded bg-black/20 text-white/90">
            PEMBARUAN {{ $waktuPembaruan }} WIB
        </span>
    </div>

    {{-- Baris 2: Judul Status Display 34px bold (§6.1) --}}
    <div class="flex items-baseline justify-between mt-0.5">
        <h2 class="text-3xl sm:text-4xl font-display font-bold tracking-tight leading-none text-white">
            {{ $status }}
        </h2>
        <x-komponen.lencana-risiko :status="$status" varian="solid" ukuran="sm" class="border border-white/40" />
    </div>

    {{-- Baris 3: Lokasi & Hari Tanpa Hujan --}}
    <p class="text-sm sm:text-base font-medium opacity-95 mt-2">
        {{ $zonaTertinggi?->nama ?? 'Savana & Jemplang' }} · 
        <strong class="font-bold underline decoration-white/40 underline-offset-2">{{ $cdd }} hari tanpa hujan</strong>
    </p>

    {{-- Baris 4: Metrik FDI & Angin (mono) --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-mono opacity-90 mt-1">
        <span>FDI <strong>{{ $fdi }}/100</strong></span>
        <span>•</span>
        <span>Angin <strong>{{ $angin }} km/j</strong> dari {{ $arahAngin }}</span>
    </div>

    {{-- Baris 5: Larangan tegas --}}
    <div class="mt-3.5 pt-3 border-t border-white/20">
        <p class="text-xs sm:text-sm font-medium leading-snug">
            ⚠️ {{ $instruksi }}
        </p>
    </div>

    {{-- Baris 6: Tombol Tindakan Outline --}}
    <div class="mt-4 flex items-center justify-between">
        <button 
            type="button"
            @click="$dispatch('buka-panel-zona', { kode: '{{ $zonaTertinggi?->kode ?? 'BRM-04' }}' })"
            class="inline-flex items-center gap-2 px-4 py-2 border-2 border-white text-white hover:bg-white hover:text-ink font-semibold rounded-xl text-xs sm:text-sm transition-colors active:scale-95"
            aria-label="Lihat tindakan dan detail risiko untuk zona {{ $zonaTertinggi?->nama ?? 'Savana' }}"
        >
            <span>Lihat tindakan yang harus dilakukan</span>
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>

        @if ($isSiaga || $isBahaya)
            <span class="text-[11px] font-mono opacity-80 hidden sm:inline-block">Darurat: 112</span>
        @endif
    </div>
</div>
