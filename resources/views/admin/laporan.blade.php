@extends('layouts.admin')

@section('judul', 'Manajemen Laporan Asap Warga')

@section('konten')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>

            <h1 class="text-2xl font-display font-bold text-ink tracking-tight mt-0.5">
                Manajemen Laporan Lapangan & Skor Kepercayaan
            </h1>
            <p class="text-xs text-ink-muted mt-1">
                Data laporan partisipatif warga dan wisatawan dengan koordinat presisi dan riwayat verifikasi petugas.
            </p>
        </div>

        {{-- Filter Status --}}
        <div class="flex items-center gap-1.5 text-xs font-sans">
            <a href="{{ route('admin.laporan') }}" class="min-h-[44px] px-3.5 py-2 rounded-md border flex items-center transition duration-fast active:scale-[0.98] {{ !request('status') ? 'bg-forest text-white border-forest font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}">Semua</a>
            <a href="{{ route('admin.laporan', ['status' => 'baru']) }}" class="min-h-[44px] px-3.5 py-2 rounded-md border flex items-center transition duration-fast active:scale-[0.98] {{ request('status') === 'baru' ? 'bg-warn text-white border-warn font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}">Baru</a>
            <a href="{{ route('admin.laporan', ['status' => 'terverifikasi']) }}" class="min-h-[44px] px-3.5 py-2 rounded-md border flex items-center transition duration-fast active:scale-[0.98] {{ request('status') === 'terverifikasi' ? 'bg-danger text-white border-danger font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}">Terverifikasi</a>
            <a href="{{ route('admin.laporan', ['status' => 'selesai']) }}" class="min-h-[44px] px-3.5 py-2 rounded-md border flex items-center transition duration-fast active:scale-[0.98] {{ request('status') === 'selesai' ? 'bg-forest text-white border-forest font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}">Selesai</a>
        </div>
    </div>

    {{-- TABEL LAPORAN LENGKAP --}}
    <div class="bg-paper-2 border border-line rounded-lg overflow-hidden shadow-none">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
                <thead class="bg-paper border-b border-line text-ink-muted text-xs">
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
                        <tr class="hover:bg-paper-3 transition duration-fast">
                            <td class="p-4 font-bold text-ink whitespace-nowrap">
                                {{ $lp->nomor_tiket }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-ink">{{ $lp->zona?->kode }} · {{ $lp->zona?->nama }}</div>
                                <div class="text-xs text-ink-muted tabular-nums">{{ $lp->latitude }}, {{ $lp->longitude }}</div>
                            </td>
                            <td class="p-4 text-ember font-semibold whitespace-nowrap">
                                {{ str_replace('_', ' ', $lp->jenis_temuan) }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-ink">{{ $lp->pelapor?->nama ?? 'Tamu (Tanpa Akun)' }}</div>
                                <div class="text-xs text-ink-muted">
                                    Skor Kepercayaan: <strong class="text-forest tabular-nums">{{ $lp->pelapor?->skor_kepercayaan ?? 50 }}</strong>/100
                                </div>
                            </td>
                            <td class="p-4">
                                @if($lp->foto_1)
                                    <a href="{{ Storage::url($lp->foto_1) }}" target="_blank" class="w-10 h-10 rounded-md overflow-hidden border border-line inline-block shadow-none">
                                        <img src="{{ Storage::url($lp->foto_1) }}" class="w-full h-full object-cover" alt="Foto">
                                    </a>
                                @else
                                    <span class="text-ink-muted text-xs">Tanpa foto</span>
                                @endif
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $lp->status_tiket === 'terverifikasi' ? 'bg-danger text-white' : ($lp->status_tiket === 'baru' ? 'bg-warn-fill text-[#5C3D06] border border-warn/40' : 'bg-paper-3 text-ink border border-line') }}">
                                    {{ str_replace('_', ' ', $lp->status_tiket) }}
                                </span>
                                @if($lp->hasil_verifikasi)
                                    <div class="text-xs text-ink-muted mt-0.5 font-bold">
                                        Hasil: {{ strtoupper(str_replace('_', ' ', $lp->hasil_verifikasi)) }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 whitespace-nowrap text-ink-muted tabular-nums">
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
