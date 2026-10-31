@extends('layouts.admin')

@section('judul', 'Log & Audit Siaran Peringatan Dini')

@section('konten')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider">MODUL FR-NOT-08</span>
            <h1 class="text-2xl font-display font-bold text-ink tracking-tight mt-0.5">
                Log Siaran & Jejak Audit Multi-Kanal
            </h1>
            <p class="text-xs text-ink-muted mt-1">
                Catatan seluruh siaran darurat, waktu transmisi, jumlah penerima, dan otoritas penanggung jawab.
            </p>
        </div>

        {{-- Tombol Ekspor CSV (FR-NOT-08) --}}
        <div>
            <a 
                href="{{ route('admin.log-siaran.ekspor') }}"
                class="min-h-[40px] px-4 py-2 bg-paper-2 hover:bg-paper-3 border border-line rounded-xl text-xs font-mono font-semibold text-ink flex items-center gap-2 transition active:scale-95 shadow-sm"
            >
                <svg class="w-4 h-4 text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh Berkas CSV</span>
            </a>
        </div>
    </div>

    {{-- TABEL RIWAYAT LOG SIARAN --}}
    <div class="bg-paper-2 border border-line rounded-3xl overflow-hidden shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-paper border-b border-line uppercase text-ink-muted text-[11px]">
                    <tr>
                        <th class="p-4">Waktu Transmisi</th>
                        <th class="p-4">Jenis Siaran</th>
                        <th class="p-4">Kanal</th>
                        <th class="p-4">Zona Sasaran</th>
                        <th class="p-4">Judul Pesan</th>
                        <th class="p-4">Penyetuju</th>
                        <th class="p-4 text-center">Penerima</th>
                        <th class="p-4 text-center">Mode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($logs as $log)
                        <tr class="hover:bg-paper-3 transition">
                            <td class="p-4 text-ink-muted whitespace-nowrap">
                                {{ $log->dikirim_pada ? $log->dikirim_pada->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ in_array($log->jenis_siaran, ['siaga_1', 'titik_api']) ? 'bg-danger text-white' : ($log->jenis_siaran === 'waspada' ? 'bg-warn text-white' : 'bg-forest text-white') }}">
                                    {{ str_replace('_', ' ', $log->jenis_siaran) }}
                                </span>
                            </td>
                            <td class="p-4 whitespace-nowrap text-ink-muted">
                                {{ str_replace('_', ' ', $log->kanal) }}
                            </td>
                            <td class="p-4 whitespace-nowrap font-bold text-ink">
                                {{ $log->zona?->kode ?? 'SEMUA' }}
                            </td>
                            <td class="p-4 max-w-xs truncate text-ink font-sans" title="{{ $log->judul_pesan }}">
                                {{ $log->judul_pesan }}
                            </td>
                            <td class="p-4 whitespace-nowrap text-ink-muted">
                                {{ $log->penyetuju?->nama ?? 'Sistem Otomatis' }}
                            </td>
                            <td class="p-4 text-center font-bold text-ink tabular-nums">
                                {{ number_format($log->jumlah_penerima, 0, ',', '.') }} orang
                            </td>
                            <td class="p-4 text-center whitespace-nowrap">
                                @if($log->adalah_simulasi)
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-warn border border-warn/30 font-bold">
                                        SIMULASI
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-safe/20 text-safe border border-safe/30 font-bold">
                                        AKTUAL
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-ink-muted">
                                Belum ada riwayat siaran darurat yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 bg-paper border-t border-line">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
