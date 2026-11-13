@extends('layouts.publik')

@section('judul', 'Status Laporan ' . $laporan->nomor_tiket)

@section('konten')
@php
    $labelJenis = [
        'asap_tipis' => 'Asap tipis',
        'asap_tebal' => 'Asap tebal',
        'api_terlihat' => 'Api terlihat',
        'bekas_terbakar' => 'Bekas terbakar',
    ][$laporan->jenis_temuan] ?? ucfirst(str_replace('_', ' ', $laporan->jenis_temuan));

    $desa = $laporan->zona?->data_analisis['Label'] ?? $laporan->zona?->nama ?? 'Kawasan Bromo';
    $kelas = $laporan->zona?->kelas_risiko;

    // Jumlah tahap yang sudah lewat; tahap berikutnya ditandai "sekarang".
    $selesaiTahap = match ($laporan->status_tiket) {
        'baru', 'ditugaskan' => 0,
        'menuju_lokasi' => 1,
        'terverifikasi', 'ditangani' => 2,
        'selesai' => 4,
        default => 0,
    };
    $akhirBukanApi = $laporan->hasil_verifikasi === 'bukan_api';
    $akhirGanda = $laporan->hasil_verifikasi === 'ganda';
    $final = $akhirBukanApi || $akhirGanda || $laporan->status_tiket === 'selesai';

    $tahap = [
        ['Laporan diterima', 'Laporanmu masuk ke antrean petugas.'],
        ['Petugas menuju lokasi', 'Petugas sedang bergerak untuk memeriksa lokasi.'],
        ['Terverifikasi petugas', $laporan->status_tiket === 'ditangani' ? 'Api dipastikan nyata dan sedang ditangani.' : 'Petugas sudah memeriksa laporanmu.'],
        ['Selesai', 'Penanganan selesai.'],
    ];
@endphp

<div class="max-w-xl mx-auto space-y-5 pb-24">
    <div>
        <a href="{{ route('lapor.index') }}" class="text-xs font-sans text-ink-muted hover:text-ink underline">← Kembali ke Lapor Asap</a>
        <h1 class="text-2xl font-display font-bold text-ink tracking-tight mt-2">Status laporan {{ $laporan->nomor_tiket }}</h1>
        <p class="text-sm text-ink-muted mt-1">Dikirim {{ $laporan->created_at->locale('id')->translatedFormat('j F Y, H:i') }} · pembaruan terakhir {{ $laporan->updated_at->locale('id')->diffForHumans() }}</p>
    </div>

    <div class="p-4 bg-paper-2 border border-line rounded-lg text-sm font-sans space-y-2">
        <div class="flex justify-between gap-3"><span class="text-ink-muted">Desa</span><strong class="text-ink text-right">{{ $desa }}</strong></div>
        @if ($kelas)
            <div class="flex justify-between gap-3"><span class="text-ink-muted">Kelas risiko desa</span><strong class="text-ink">Risiko {{ strtolower($kelas) }}</strong></div>
        @endif
        <div class="flex justify-between gap-3"><span class="text-ink-muted">Temuan</span><strong class="text-ink">{{ $labelJenis }}</strong></div>
    </div>

    @if ($akhirBukanApi)
        <div class="p-4 border-2 border-line rounded-lg bg-paper-2" role="status">
            <strong class="block text-ink">Petugas sudah memeriksa: bukan api.</strong>
            <p class="text-sm text-ink-muted mt-1">Laporan ditutup. Terima kasih sudah peduli, tetap lapor jika kamu melihat asap lagi.</p>
        </div>
    @elseif ($akhirGanda)
        <div class="p-4 border-2 border-line rounded-lg bg-paper-2" role="status">
            <strong class="block text-ink">Laporanmu digabung dengan laporan lain di lokasi yang sama.</strong>
            <p class="text-sm text-ink-muted mt-1">Petugas sudah menangani titik ini. Terima kasih.</p>
        </div>
    @else
        <ol class="space-y-3" aria-label="Tahapan penanganan laporan">
            @foreach ($tahap as $i => [$judul, $ket])
                @php $selesai = $i < $selesaiTahap; $aktif = ! $selesai && $i === $selesaiTahap; @endphp
                <li class="flex gap-3 items-start p-3 rounded-md border {{ $aktif ? 'border-forest bg-paper-2' : 'border-line' }} {{ ! $selesai && ! $aktif ? 'opacity-60' : '' }}">
                    <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full flex items-center justify-center text-xs font-bold {{ $selesai ? 'bg-safe text-white' : ($aktif ? 'bg-forest text-white' : 'bg-paper-3 text-ink-muted') }}" aria-hidden="true">{{ $selesai ? '✓' : $i + 1 }}</span>
                    <div>
                        <strong class="text-sm text-ink block">{{ $judul }}@if ($aktif) <span class="text-xs font-semibold text-forest">· sekarang</span>@endif</strong>
                        @if ($selesai || $aktif)<span class="text-xs text-ink-muted">{{ $ket }}</span>@endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    @unless ($final)
        <p class="text-xs text-ink-muted">Halaman ini memperbarui diri tiap 30 detik.</p>
        <script>setTimeout(() => location.reload(), 30000);</script>
    @endunless

    <div class="p-4 border-2 border-danger rounded-lg" role="note">
        <strong class="block text-danger text-sm">Api membesar atau ada orang dalam bahaya?</strong>
        <p class="text-xs text-ink mt-1">Jauhi lokasi dan telepon <a href="tel:112" class="font-bold underline">112</a> sekarang. Jangan menunggu status berubah.</p>
    </div>
</div>
@endsection
