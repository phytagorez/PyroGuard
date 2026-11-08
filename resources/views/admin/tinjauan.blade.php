@extends('layouts.admin')

@section('judul', 'Tinjauan Prediksi 6 Zona Bromo')

@section('konten')
<div class="space-y-6">
    {{-- HEADER LAYAR & FILTER PERLU TINJAUAN --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>

            <h1 class="text-2xl font-display font-bold text-ink tracking-tight mt-0.5">
                Tinjauan Prediksi Risiko 6 Zona Bromo
            </h1>
            <p class="text-xs text-ink-muted mt-1">
                Matriks evaluasi kekeringan bahan bakar (CDD) dan bahaya perambatan api harian kawasan.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.tinjauan', ['perlu_tinjauan' => $hanyaPerluTinjauan ? 0 : 1]) }}"
                class="min-h-[44px] px-3.5 py-2 rounded-md text-xs font-sans font-semibold border transition duration-fast active:scale-[0.98] flex items-center gap-2 {{ $hanyaPerluTinjauan ? 'bg-danger text-white border-danger shadow-none' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
            >
                <span> Filter: Perlu Tinjauan Saja</span>
                @if($hanyaPerluTinjauan)
                    <span class="w-2 h-2 rounded-full bg-white"></span>
                @endif
            </a>

            <a
                href="{{ route('admin.siaga') }}"
                class="min-h-[44px] px-4 py-2 rounded-md text-xs font-sans font-bold bg-forest hover:bg-moss text-white transition duration-fast active:scale-[0.98] flex items-center gap-1.5 shadow-none"
            >
                <span>Persetujuan Siaga →</span>
            </a>
        </div>
    </div>

    {{-- TABEL 6 ZONA MATRIKS PREDIKSI LENGKAP (§7.8) --}}
    <div class="bg-paper-2 border border-line rounded-lg overflow-hidden shadow-none">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
                <thead class="bg-paper border-b border-line text-ink-muted text-xs">
                    <tr>
                        <th class="p-4">Kode & Zona</th>
                        <th class="p-4">Kategori Risiko</th>
                        <th class="p-4">CDD Aktual</th>
                        <th class="p-4 text-center">Proyeksi (+24j / +48j / +72j)</th>
                        <th class="p-4">FDI</th>
                        <th class="p-4">Status Siaga</th>
                        <th class="p-4">Pembaruan</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($zonas as $zona)
                        @php
                            $r = $zona->risikoTerbaru;
                            $kategori = $r?->kategori_risiko ?? 'AMAN';
                            $cdd = $r?->hari_tanpa_hujan ?? 0;
                            $fdi = $r?->indeks_bahaya_api ?? 0;
                            $p24 = $r?->proyeksi_24j ?? ($cdd + 1);
                            $p48 = $r?->proyeksi_48j ?? ($cdd + 2);
                            $p72 = $r?->proyeksi_72j ?? ($cdd + 3);
                            $siaga = $r?->status_siaga ?? 'AMAN';
                            $isBahaya = $kategori === 'BAHAYA' || in_array($siaga, ['SIAGA_1', 'SIAGA_OTOMATIS']);
                        @endphp
                        <tr class="hover:bg-paper-3 transition duration-fast {{ $isBahaya ? 'bg-danger/5' : '' }}">
                            {{-- 1. Kode & Nama --}}
                            <td class="p-4">
                                <div class="font-bold text-ink text-sm">{{ $zona->kode }}</div>
                                <div class="text-ink-muted text-xs font-sans">{{ $zona->nama }}</div>
                            </td>

                            {{-- 2. Kategori Badge --}}
                            <td class="p-4">
                                <x-komponen.lencana-risiko :status="$kategori" varian="solid" ukuran="sm" />
                            </td>

                            {{-- 3. CDD Aktual --}}
                            <td class="p-4">
                                <span class="font-bold text-ink text-sm tabular-nums">{{ $cdd }}</span>
                                <span class="text-ink-muted text-xs">hari</span>
                            </td>

                            {{-- 4. Tiga Chip Proyeksi --}}
                            <td class="p-4 text-center">
                                <div class="inline-flex items-center gap-1.5 bg-paper px-2.5 py-1 rounded-md border border-line text-xs">
                                    <span class="text-ink-muted tabular-nums">+24j: <strong class="text-ink">{{ $p24 }}h</strong></span>
                                    <span>•</span>
                                    <span class="text-ink-muted tabular-nums">+48j: <strong class="text-ink">{{ $p48 }}h</strong></span>
                                    <span>•</span>
                                    <span class="text-ink-muted tabular-nums">+72j: <strong class="text-danger">{{ $p72 }}h</strong></span>
                                </div>
                            </td>

                            {{-- 5. FDI Meter --}}
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold tabular-nums text-sm {{ $fdi >= 80 ? 'text-danger' : ($fdi >= 41 ? 'text-warn' : 'text-forest') }}">
                                        {{ $fdi }}
                                    </span>
                                    <span class="text-ink-muted text-xs">/100</span>
                                </div>
                                <div class="w-16 h-1.5 bg-paper-3 rounded-full overflow-hidden mt-1">
                                    <div class="h-full rounded-full {{ $fdi >= 80 ? 'bg-danger' : ($fdi >= 41 ? 'bg-warn' : 'bg-safe') }}" style="width: {{ $fdi }}%;"></div>
                                </div>
                            </td>

                            {{-- 6. Status Siaga --}}
                            <td class="p-4">
                                @if($siaga === 'SIAGA_1')
                                    <span class="px-2 py-0.5 rounded text-xs bg-danger text-white font-bold animate-pulse">
                                        SIAGA 1 (AKTIF)
                                    </span>
                                @elseif($siaga === 'SIAGA_OTOMATIS')
                                    <span class="px-2 py-0.5 rounded text-xs bg-warn-fill text-[#5C3D06] border border-warn/40 font-bold">
                                        SIAGA AUTO (PERLU BPBD)
                                    </span>
                                @else
                                    <span class="text-ink-muted text-xs">-</span>
                                @endif
                            </td>

                            {{-- 7. Waktu Update --}}
                            <td class="p-4 text-ink-muted text-xs tabular-nums">
                                {{ $r?->dihitung_pada ? \Carbon\Carbon::parse($r->dihitung_pada)->format('H:i') . ' WIB' : '07:00 WIB' }}
                            </td>

                            {{-- 8. Aksi --}}
                            <td class="p-4 text-right">
                                @if($siaga === 'SIAGA_OTOMATIS' || $isBahaya)
                                    <a
                                        href="{{ route('admin.siaga') }}"
                                        class="inline-block px-3 py-1.5 bg-danger hover:brightness-110 text-white rounded-md text-xs font-bold transition duration-fast shadow-none min-h-[36px] leading-[24px]"
                                    >
                                        Tinjau Siaga
                                    </a>
                                @else
                                    <span class="text-ink-muted text-xs">Stabil</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-ink-muted">
                                Tidak ada zona yang memenuhi kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
