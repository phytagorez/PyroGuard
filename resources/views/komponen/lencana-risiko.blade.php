@props([
    'status' => 'AMAN',
    'varian' => 'solid', // 'solid' | 'soft'
    'ukuran' => 'md',    // 'sm' (20px) | 'md' (24px)
])

@php
    $statusNormalized = strtoupper(str_replace('_', ' ', $status));
    if ($statusNormalized === 'NORMAL') {
        $statusNormalized = 'AMAN';
    }

    $baseClasses = "inline-flex items-center gap-1.5 font-sans tracking-normal rounded-sm select-none transition-colors duration-fast";
    $sizeClasses = $ukuran === 'sm' ? 'px-2 py-0.5 text-xs leading-tight font-bold' : 'px-2.5 py-1 text-xs leading-none font-bold';

    // Konfigurasi visual 3 lapis (§2 P2 & §5.1 & §6.2 design.md)
    // 1. Teks eksplisit kapital
    // 2. Ikon bentuk unik (Perisai, Titik, Garis Diagonal 45°, Pola Garis Rapat + Pita)
    // 3. Warna latar & teks WCAG min 4,5:1
    $config = match ($statusNormalized) {
        'AMAN' => [
            'label' => 'AMAN',
            'solid' => 'bg-safe text-white border border-safe',
            'soft' => 'bg-safe-fill text-[#14532D] border border-safe/30',
            'icon' => 'perisai',
        ],
        'WASPADA' => [
            'label' => 'WASPADA',
            'solid' => 'bg-warn text-white border border-warn',
            'soft' => 'bg-warn-fill text-[#5C3D06] border border-warn/40',
            'icon' => 'titik',
        ],
        'BAHAYA' => [
            'label' => 'BAHAYA',
            'solid' => 'bg-danger text-white border border-danger',
            'soft' => 'bg-[#faecea] text-[#8c2d28] border border-[#f0d6d3]',
            'icon' => 'garis_diagonal',
        ],
        'SIAGA 1', 'SIAGA OTOMATIS' => [
            'label' => $statusNormalized === 'SIAGA OTOMATIS' ? 'SIAGA AUTO' : 'SIAGA 1',
            'solid' => 'bg-siaga text-white border border-siaga ring-1 ring-white/40',
            'soft' => 'bg-[#faecea] text-[#8c2d28] border border-[#f0d6d3]',
            'icon' => 'siaga_pita',
        ],
        'DICABUT' => [
            'label' => 'DICABUT',
            'solid' => 'bg-paper-3 text-ink border border-line',
            'soft' => 'bg-paper-2 text-ink-muted border border-line',
            'icon' => 'centang',
        ],
        default => [
            'label' => $statusNormalized,
            'solid' => 'bg-forest text-white border border-forest',
            'soft' => 'bg-paper-2 text-ink border border-line',
            'icon' => 'titik',
        ],
    };

    $colorClasses = $config['soft'];
@endphp

<span {{ $attributes->merge(['class' => "{$baseClasses} {$sizeClasses} {$colorClasses}"]) }} role="status">
    {{-- Ikon Bentuk Unik (Warna Tidak Pernah Sendiri - §2 P2 design.md) --}}
    @if ($config['icon'] === 'perisai')
        {{-- 1. AMAN: Perisai Unik --}}
        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M8 1.25l-5.5 2.29v4.58c0 3.65 2.37 7.08 5.5 7.88 3.13-.8 5.5-4.23 5.5-7.88V3.54L8 1.25zm-.25 8.94l-2.09-2.09.88-.88 1.21 1.21 3.21-3.21.88.88-4.09 4.09z" clip-rule="evenodd"/>
        </svg>
    @elseif ($config['icon'] === 'titik')
        {{-- 2. WASPADA: Titik Lingkaran Padat --}}
        <svg class="w-3 h-3 shrink-0" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <circle cx="8" cy="8" r="5" />
        </svg>
    @elseif ($config['icon'] === 'garis_diagonal')
        {{-- 3. BAHAYA: Garis Diagonal 45° --}}
        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
            <line x1="2" y1="14" x2="14" y2="2" />
            <line x1="2" y1="8" x2="8" y2="2" />
            <line x1="8" y1="14" x2="14" y2="8" />
        </svg>
    @elseif ($config['icon'] === 'siaga_pita')
        {{-- 4. SIAGA 1: Pola Garis Rapat + Pita --}}
        <svg class="w-3.5 h-3.5 shrink-0 animate-pulse" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <rect x="2" y="4" width="12" height="10" rx="1" stroke-width="1.6" />
            <path d="M1 4h14" stroke-width="2.2" stroke-linecap="square" />
            <line x1="4" y1="12" x2="10" y2="6" stroke-width="1.8" />
            <line x1="7" y1="13" x2="13" y2="7" stroke-width="1.8" />
            <path d="M4 1.5L8 3.5L12 1.5" stroke-width="1.6" stroke-linejoin="round" />
        </svg>
    @else
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    @endif

    <span>{{ $config['label'] }}</span>
</span>
