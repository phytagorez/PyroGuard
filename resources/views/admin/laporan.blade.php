@extends('layouts.admin')

@section('judul', 'Manajemen Laporan Asap Warga')

@section('konten')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider">MODUL FR-REP-07</span>
            <h1 class="text-2xl font-display font-bold text-ink tracking-tight mt-0.5">
                Manajemen Laporan Lapangan & Skor Kepercayaan
            </h1>
            <p class="text-xs text-ink-muted mt-1">
                Data laporan partisipatif warga dan wisatawan dengan koordinat presisi dan riwayat verifikasi petugas.
            </p>
        </div>

        {{-- Filter Status --}}
        <div class="flex items-center gap-1.5 text-xs font-mono">
            <a href="{{ route('admin.laporan') }}" class="px-3 py-1.5 rounded-lg border {{ !request('status') ? 'bg-forest text-white' : 'bg-paper-2 text-ink border-line' }}">Semua</a>
            <a href="{{ route('admin.laporan', ['status' => 'baru']) }}" class="px-3 py-1.5 rounded-lg border {{ request('status') === 'baru' ? 'bg-amber-500 text-white' : 'bg-paper-2 text-ink border-line' }}">Baru</a>
            <a href="{{ route('admin.laporan', ['status' => 'terverifikasi']) }}" class="px-3 py-1.5 rounded-lg border {{ request('status') === 'terverifikasi' ? 'bg-danger text-white' : 'bg-paper-2 text-ink border-line' }}">Terverifikasi</a>
            <a href="{{ route('admin.laporan', ['status' => 'selesai']) }}" class="px-3 py-1.5 rounded-lg border {{ request('status') === 'selesai' ? 'bg-forest text-white' : 'bg-paper-2 text-ink border-line' }}">Selesai</a>
        </div>
    </div>

    {{-- TABEL LAPORAN LENGKAP --}}
    <div class="bg-paper-2 border border-line rounded-3xl overflow-hidden shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-paper border-b border-line uppercase text-ink-muted text-[11px]">
                    <tr>
                        <th class="p-4">Nomor Tiket</th>
                        <th class="p-4">Zona & Koordinat</th>
                        <th class="p-4">Gejala Dilaporkan</th>
                        <th class="p-4">Pelapor & Skor</th>
                        <th class="p-4">Foto Bukti</th>
                        <th class="p-4">Status & Hasil</th>
                        <th class="p-4">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($laporans as $lp)
                        <tr class="hover:bg-paper-3 transition">
                            <td class="p-4 font-bold text-ink whitespace-nowrap">
                                {{ $lp->nomor_tiket }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-ink">{{ $lp->zona?->kode }} · {{ $lp->zona?->nama }}</div>
                                <div class="text-[10px] text-ink-muted tabular-nums">{{ $lp->latitude }}, {{ $lp->longitude }}</div>
                            </td>
                            <td class="p-4 uppercase text-ember font-semibold whitespace-nowrap">
                                {{ str_replace('_', ' ', $lp->jenis_temuan) }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-ink">{{ $lp->pelapor?->nama ?? 'Tamu (Tanpa Akun)' }}</div>
                                <div class="text-[10px] text-ink-muted">
                                    Skor Kepercayaan: <strong class="text-forest">{{ $lp->pelapor?->skor_kepercayaan ?? 50 }}</strong>/100
                                </div>
                            </td>
                            <td class="p-4">
                                @if($lp->foto_1)
                                    <a href="{{ Storage::url($lp->foto_1) }}" target="_blank" class="w-10 h-10 rounded-lg overflow-hidden border border-line inline-block">
                                        <img src="{{ Storage::url($lp->foto_1) }}" class="w-full h-full object-cover" alt="Foto">
                                    </a>
                                @else
                                    <span class="text-ink-muted text-[10px]">Tanpa foto</span>
                                @endif
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold {{ $lp->status_tiket === 'terverifikasi' ? 'bg-danger text-white' : ($lp->status_tiket === 'baru' ? 'bg-amber-500 text-white' : 'bg-paper-3 text-ink') }}">
                                    {{ str_replace('_', ' ', $lp->status_tiket) }}
                                </span>
                                @if($lp->hasil_verifikasi)
                                    <div class="text-[10px] text-ink-muted mt-0.5 font-bold">
                                        Hasil: {{ strtoupper(str_replace('_', ' ', $lp->hasil_verifikasi)) }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 whitespace-nowrap text-ink-muted">
                                {{ $lp->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-ink-muted">
                                Belum ada laporan warga yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($laporans->hasPages())
            <div class="p-4 bg-paper border-t border-line">
                {{ $laporans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
