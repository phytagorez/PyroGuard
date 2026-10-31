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

    $baseClasses = "inline-flex items-center gap-1.5 font-mono uppercase tracking-wider rounded-full transition-colors select-none";
    $sizeClasses = $ukuran === 'sm' ? 'px-2 py-0.5 text-[10px] leading-tight' : 'px-2.5 py-1 text-[11px] leading-none';

    // Konfigurasi visual warna dan bentuk (§5.1 & §6.2 design.md)
    $config = match ($statusNormalized) {
        'AMAN' => [
            'label' => 'AMAN',
            'solid' => 'bg-safe text-white',
            'soft' => 'bg-safe-fill text-[#14532D] border border-safe/30',
            'icon' => 'perisai',
        ],
        'WASPADA' => [
            'label' => 'WASPADA',
            'solid' => 'bg-warn text-white',
            'soft' => 'bg-warn-fill text-[#5C3D06] border border-warn/30',
            'icon' => 'titik',
        ],
        'BAHAYA' => [
            'label' => 'BAHAYA',
            'solid' => 'bg-danger text-white',
            'soft' => 'bg-danger-fill text-white border border-danger/40',
            'icon' => 'garis_diagonal',
        ],
        'SIAGA 1', 'SIAGA OTOMATIS' => [
            'label' => $statusNormalized === 'SIAGA OTOMATIS' ? 'SIAGA AUTO' : 'SIAGA 1',
            'solid' => 'bg-siaga text-white ring-1 ring-white/30',
            'soft' => 'bg-siaga text-white border border-white/20',
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
            'solid' => 'bg-ink text-white',
            'soft' => 'bg-paper-2 text-ink border border-line',
            'icon' => 'titik',
        ],
    };

    $colorClasses = $varian === 'soft' ? $config['soft'] : $config['solid'];
@endphp

<span {{ $attributes->merge(['class' => "{$baseClasses} {$sizeClasses} {$colorClasses}"]) }} role="status">
    {{-- Ikon bentuk berbeda (§2 P2: Warna tidak pernah sendiri) --}}
    @if ($config['icon'] === 'perisai')
        {{-- Bentuk Perisai untuk AMAN --}}
        <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" />
        </svg>
    @elseif ($config['icon'] === 'titik')
        {{-- Bentuk Titik Bulat untuk WASPADA --}}
        <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0" aria-hidden="true"></span>
    @elseif ($config['icon'] === 'garis_diagonal')
        {{-- Bentuk Garis Diagonal / Hazard untuk BAHAYA --}}
        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    @elseif ($config['icon'] === 'siaga_pita')
        {{-- Pola Striped / Alarm untuk SIAGA 1 --}}
        <svg class="w-3 h-3 shrink-0 animate-pulse" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
        </svg>
    @else
        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
    @endif

    <span>{{ $config['label'] }}</span>
</span>
