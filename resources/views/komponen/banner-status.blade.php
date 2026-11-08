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

    // 1 Kalimat Larangan Tegas (Maksimal 12 kata sesuai §13 copy rules & prompt)
    $instruksi = match (true) {
        $isSiaga => 'Dilarang keras menyalakan api terbuka sampai status dicabut.',
        $isBahaya => 'Dilarang membuat api terbuka, membakar sampah, atau menyalakan flare.',
        $isWaspada => 'Jangan membakar semak dan pastikan puntung rokok padam sempurna.',
        default => 'Kawasan relatif aman. Tetap waspada dan jangan membakar semak.',
    };

    $cdd = $risiko?->hari_tanpa_hujan ?? 0;
    $fdi = $risiko?->indeks_bahaya_api ?? 0;
    $angin = round($risiko?->kecepatan_angin_kmj ?? 0);
    $arahAngin = strtolower($risiko?->arah_angin ?? 'tenggara');
    $waktuPembaruan = $risiko?->dihitung_pada ? \Carbon\Carbon::parse($risiko->dihitung_pada)->format('H:i') : '07:00';
    $durasiJam = $kesegaran['durasi_jam'] ?? 0;
@endphp

<div class="relative w-full rounded-lg p-5 sm:p-6 shadow-none overflow-hidden transition-all duration-base {{ $bgClass }}">
    {{-- Pita Peringatan Kedaluwarsa Data jika > 2 jam atau > 6 jam (§6.1 & §6.6 design.md) --}}
    @if ($durasiJam > 6)
        <div class="mb-4 -mt-2 -mx-2 px-3.5 py-2 bg-black/60 border border-white/40 rounded-md flex items-center justify-between text-xs font-sans text-white">
            <span class="flex items-center gap-2 font-bold tracking-wide">
                <svg class="w-4 h-4 shrink-0 text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>DATA TERAKHIR {{ $durasiJam }} JAM LALU · KEDALUWARSA</span>
            </span>
            <button onclick="window.location.reload()" class="underline font-bold hover:text-amber-200 transition min-h-[36px] flex items-center">Muat ulang</button>
        </div>
    @elseif ($durasiJam >= 2)
        <div class="mb-4 -mt-2 -mx-2 px-3.5 py-2 bg-warn-fill text-[#5C3D06] border border-warn/50 rounded-md text-xs font-sans flex items-center justify-between font-bold">
            <span class="flex items-center gap-1.5">
                <span></span>
                <span>DATA {{ $durasiJam }} JAM LALU · PENGAMBILAN BERIKUTNYA DICOBA</span>
            </span>
            <button onclick="window.location.reload()" class="underline hover:text-black transition min-h-[36px] flex items-center">Segarkan</button>
        </div>
    @endif

    {{-- Baris 1: Header metadata mono --}}
    <div class="flex items-center justify-between text-xs font-sans tracking-normal opacity-90 mb-1.5">
        <span class="uppercase font-bold">
            STATUS KAWASAN TERTINGGI · {{ $zonaTertinggi?->kode ?? 'BRM-04' }}
        </span>
        <span class="px-2.5 py-1 rounded-sm bg-black/30 text-white font-semibold">
            DIPERBARUI {{ $waktuPembaruan }} WIB
        </span>
    </div>

    {{-- Baris 2: Judul Status Display 34-40px bold (§6.1) --}}
    <div class="flex items-baseline justify-between mt-1">
        <h2 class="text-3xl sm:text-4xl font-display font-bold tracking-tight leading-none text-white">
            {{ $status }}
        </h2>
        <x-komponen.lencana-risiko :status="$status" varian="solid" ukuran="md" class="border border-white/60 shadow-none" />
    </div>

    {{-- Baris 3: Lokasi & Hari Tanpa Hujan (Inter 16px) --}}
    <p class="text-base font-medium opacity-95 mt-2.5">
        {{ $zonaTertinggi?->nama ?? 'Savana & Jemplang' }} ·
        <strong class="font-bold underline decoration-white/50 underline-offset-4">{{ $cdd }} hari tanpa hujan</strong>
    </p>

    {{-- Baris 4: Metrik FDI & Angin (mono tabular) --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-sans opacity-90 mt-1.5 tabular-nums">
        <span>FDI <strong>{{ $fdi }}/100</strong></span>
        <span>•</span>
        <span>Angin <strong>{{ $angin }} km/j</strong> dari {{ $arahAngin }}</span>
    </div>

    {{-- Baris 5: Larangan tegas (Teks Instruksi 16px mudah dibaca di bawah terik matahari Bromo) --}}
    <div class="mt-4 pt-3.5 border-t border-white/20">
        <p class="text-sm sm:text-base font-semibold leading-snug">
             {{ $instruksi }}
        </p>
    </div>

    {{-- Baris 6: Tombol Tindakan Outline 44px --}}
    <div class="mt-4 flex items-center justify-between gap-3">
        <button
            type="button"
            @click="$dispatch('buka-panel-zona', { kode: '{{ $zonaTertinggi?->kode ?? 'BRM-04' }}' })"
            class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 border-2 border-white text-white hover:bg-white hover:text-ink font-semibold rounded-md text-sm transition active:scale-[0.98]"
            aria-label="Lihat tindakan dan detail risiko untuk zona {{ $zonaTertinggi?->nama ?? 'Savana' }}"
        >
            <span>Lihat tindakan yang harus dilakukan</span>
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>

        @if ($isSiaga || $isBahaya)
            <span class="text-xs font-sans opacity-90 hidden sm:inline-block font-bold">Darurat: 112</span>
        @endif
    </div>
</div>
