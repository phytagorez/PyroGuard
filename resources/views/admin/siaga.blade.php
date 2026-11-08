@extends('layouts.admin')

@section('judul', 'Persetujuan Otoritas Status Siaga 1 BPBD')

@section('konten')
<div
    x-data="{
        modalSetujuiBuka: false,
        modalCabutBuka: false,
        catatanPersetujuan: '',
        catatanPencabutan: '',
        tabKanal: 'telegram',
    }"
    class="space-y-6"
>
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>

            <h1 class="text-2xl font-display font-bold text-ink tracking-tight mt-0.5">
                Persetujuan Status Siaga 1 & Rantai Komando
            </h1>
            <p class="text-xs text-ink-muted mt-1 leading-relaxed">
                Pemberian wewenang eskalasi status darurat kebakaran lahan Savana Bromo dengan pola konfirmasi dua langkah dan jejak audit resmi.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-sans text-ink-muted">Penyetuju Otoritas:</span>
            <span class="px-3 py-1.5 rounded-xl bg-forest/10 border border-forest/20 text-forest font-sans text-xs font-bold">
                {{ auth()->user()->nama }} (BPBD)
            </span>
        </div>
    </div>

    {{-- KOMPONEN APPROVALCARD (§6.10 design.md) --}}
    <div class="p-6 sm:p-7 bg-paper-2 border-2 {{ $zonaSiaga?->risikoTerbaru?->status_siaga === 'SIAGA_1' ? 'border-danger/40' : 'border-warn/40' }} rounded-lg shadow-sheet space-y-6">

        {{-- Baris 1: Status Banner & Lokasi --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-line">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-sans text-xs font-bold text-forest tracking-normal">{{ $zonaSiaga?->kode ?? 'BRM-04' }}</span>
                    <span class="text-xs text-ink-muted font-sans">•</span>
                    <span class="text-xs font-sans text-ink-muted">Tengger Semeru</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-display font-bold text-ink">
                    {{ $zonaSiaga?->nama ?? 'Savana & Jemplang' }}
                </h2>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                @if($zonaSiaga?->risikoTerbaru?->status_siaga === 'SIAGA_1')
                    <div class="text-right">
                        <span class="px-3 py-1 rounded-full text-xs font-sans font-bold bg-danger text-white border border-danger shadow-none animate-pulse block">
                            SIAGA 1 AKTIF
                        </span>
                        <span class="text-xs font-sans text-ink-muted block mt-1">Disetujui Otoritas BPBD</span>
                    </div>
                @else
                    <div class="text-right">
                        <span class="px-3 py-1 rounded-full text-xs font-sans font-bold bg-warn-fill text-[#5C3D06] border border-warn/40 shadow-none block">
                            SIAGA OTOMATIS
                        </span>
                        <span class="text-xs font-sans text-warn font-semibold block mt-1">Menunggu Persetujuan BPBD</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Baris 2: Ringkasan 4 Bukti Ilmiah Lapangan (§6.10) --}}
        <div>
            <span class="text-xs font-sans font-bold text-ink-muted block mb-3">
                Ringkasan Bukti Kebakaran Lahan (Decision Evidence):
            </span>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-sans">
                <div class="p-3.5 bg-paper rounded-md border border-line">
                    <span class="text-ink-muted block text-xs">Akumulasi Kekeringan:</span>
                    <div class="text-2xl font-display font-bold text-ink mt-1 tabular-nums">
                        {{ $zonaSiaga?->risikoTerbaru?->hari_tanpa_hujan ?? 19 }}
                        <span class="text-xs font-sans font-normal text-ink-muted">hari (CDD)</span>
                    </div>
                    <span class="text-xs text-danger font-semibold block mt-0.5">Ambang Bahaya &gt; 14h</span>
                </div>

                <div class="p-3.5 bg-paper rounded-md border border-line">
                    <span class="text-ink-muted block text-xs">Indeks Bahaya Api (FDI):</span>
                    <div class="text-2xl font-display font-bold text-danger mt-1 tabular-nums">
                        {{ $zonaSiaga?->risikoTerbaru?->indeks_bahaya_api ?? 86 }}
                        <span class="text-xs font-sans font-normal text-ink-muted">/100</span>
                    </div>
                    <span class="text-xs text-danger font-semibold block mt-0.5">Ambang Kritis &gt; 80</span>
                </div>

                <div class="p-3.5 bg-paper rounded-md border border-line">
                    <span class="text-ink-muted block text-xs">Titik Panas (7 Hari):</span>
                    <div class="text-2xl font-display font-bold text-danger mt-1 tabular-nums">
                        {{ $bukti['titik_panas_7_hari'] }}
                        <span class="text-xs font-sans font-normal text-ink-muted">satelit</span>
                    </div>
                    <span class="text-xs text-ink-muted block mt-0.5">NASA FIRMS MODIS/VIIRS</span>
                </div>

                <div class="p-3.5 bg-paper rounded-md border border-line">
                    <span class="text-ink-muted block text-xs">Laporan Terverifikasi:</span>
                    <div class="text-2xl font-display font-bold text-forest mt-1 tabular-nums">
                        {{ $bukti['laporan_terverifikasi'] }}
                        <span class="text-xs font-sans font-normal text-ink-muted">kejadian</span>
                    </div>
                    <span class="text-xs text-ink-muted block mt-0.5">Petugas Pos Jaga TNBTS</span>
                </div>
            </div>
        </div>

        {{-- Baris 3: Komponen MessagePreview Multi-Kanal (§6.11 design.md) --}}
        <div class="p-4 bg-paper rounded-md border border-line space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs font-sans">
                <span class="font-bold text-ink flex items-center gap-1.5">
                    <span></span>
                    <span>Pratinjau Pesan yang Akan Disiarkan (Multi-Channel Live Preview):</span>
                </span>

                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click="tabKanal = 'telegram'"
                        :class="tabKanal === 'telegram' ? 'bg-[#229ED9] text-white font-bold shadow-none' : 'bg-paper-2 text-ink-muted border border-line hover:text-ink'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-md transition duration-fast text-xs flex items-center gap-1.5 cursor-pointer active:scale-[0.98]"
                    >
                        <span></span>
                        <span>Kanal Telegram</span>
                    </button>
                    <button
                        type="button"
                        @click="tabKanal = 'webpush'"
                        :class="tabKanal === 'webpush' ? 'bg-forest text-white font-bold shadow-none' : 'bg-paper-2 text-ink-muted border border-line hover:text-ink'"
                        class="min-h-[44px] px-3.5 py-1.5 rounded-md transition duration-fast text-xs flex items-center gap-1.5 cursor-pointer active:scale-[0.98]"
                    >
                        <span></span>
                        <span>Web-Push Browser</span>
                    </button>
                </div>
            </div>

            {{-- 1. Gaya Obrolan Telegram (Authentic Telegram Channel Bubble) --}}
            <div x-show="tabKanal === 'telegram'" class="p-4 rounded-md bg-[#8EAF9D]/20 border border-line text-xs font-sans">
                <div class="max-w-lg bg-white p-4 rounded-lg rounded-tl-xs shadow-none border border-black/10 space-y-2 text-ink">
                    <div class="flex items-center justify-between text-xs pb-1.5 border-b border-line/60">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-[#229ED9] text-white flex items-center justify-center font-bold text-xs">

                            </div>
                            <span class="font-bold text-[#229ED9]">PyroGuard Bromo — Peringatan Dini</span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] bg-paper-3 text-ink-muted font-bold">BOT</span>
                        </div>
                        <span class="text-xs text-ink-muted font-sans tabular-nums">{{ now()->format('H:i') }}</span>
                    </div>

                    <div class="p-2.5 rounded-md bg-danger/10 border-l-4 border-danger text-xs space-y-1">
                        <div class="font-bold text-danger flex items-center gap-1">
                            <span></span>
                            <span>STATUS SIAGA 1 DARURAT KEBAKARAN LAHAN</span>
                        </div>
                        <div class="text-xs text-ink font-semibold">
                            Kawasan: {{ $zonaSiaga?->kode ?? 'BRM-04' }} — {{ $zonaSiaga?->nama ?? 'Savana & Jemplang' }}
                        </div>
                    </div>

                    <pre class="font-sans text-xs whitespace-pre-wrap text-ink font-medium leading-relaxed bg-paper/60 p-2.5 rounded-md border border-line/50">{{ $pratinjauPesan }}</pre>

                    <div class="flex items-center justify-between text-xs text-ink-muted pt-1">
                        <span class="flex items-center gap-1">
                            <span></span>
                            <span class="tabular-nums font-semibold">1.420 dilihat</span>
                        </span>
                        <span class="text-[#229ED9] font-bold"> Terkirim</span>
                    </div>
                </div>
            </div>

            {{-- 2. Gaya Web-Push Card (Authentic OS Browser Notification) --}}
            <div x-show="tabKanal === 'webpush'" class="p-4 rounded-md bg-paper-3 border border-line text-xs font-sans" style="display: none;">
                <div class="max-w-lg bg-paper p-4 rounded-lg shadow-sheet border-2 border-danger/40 space-y-2.5">
                    {{-- Header Push --}}
                    <div class="flex items-center justify-between text-xs text-ink-muted">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-md bg-danger text-white flex items-center justify-center font-bold text-xs">P</span>
                            <span class="font-bold text-ink tracking-wide">PyroGuard Bromo · Google Chrome</span>
                        </div>
                        <span class="text-xs">Baru saja</span>
                    </div>

                    {{-- Isi Notifikasi --}}
                    <div class="space-y-1">
                        <strong class="text-sm font-display font-bold text-danger block">
                             SIAGA 1 — {{ $zonaSiaga?->nama ?? 'Savana & Jemplang' }}
                        </strong>
                        <p class="text-xs text-ink leading-relaxed">
                            Hari ke-{{ $zonaSiaga?->risikoTerbaru?->hari_tanpa_hujan ?? 19 }} tanpa hujan (FDI {{ $zonaSiaga?->risikoTerbaru?->indeks_bahaya_api ?? 86 }}/100). Dilarang menyalakan api terbuka di area savana!
                        </p>
                    </div>

                    {{-- Tombol Aksi Web-Push --}}
                    <div class="flex items-center gap-2 pt-1 border-t border-line/60">
                        <div class="px-3 py-1.5 rounded-md bg-danger text-white font-bold text-xs shadow-none">
                            Buka Peta Evakuasi
                        </div>
                        <div class="px-3 py-1.5 rounded-md bg-paper-2 text-ink border border-line text-xs">
                            Hubungi Pos Jaga 112
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs font-sans text-ink-muted pt-1 gap-1">
                <span>Estimasi Penerima: <strong class="text-ink">±128 pelanggan</strong> web-push & <strong class="text-ink">3 grup</strong> pos jaga TNBTS</span>
                <span>Protokol Siaran Bertingkat §14.2</span>
            </div>
        </div>

        {{-- Baris 4: Aksi Otoritas & Jejak Audit 2 Langkah (§6.10) --}}
        <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            @if($zonaSiaga?->risikoTerbaru?->status_siaga === 'SIAGA_1')
                {{-- Keadaan Siaga 1 Telah Aktif --}}
                <div class="text-xs font-sans text-forest flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-forest animate-ping"></span>
                    <span>Status Siaga 1 sedang berlangsung. Protokol pembatasan akses savana diberlakukan.</span>
                </div>

                <button
                    type="button"
                    @click="modalCabutBuka = true"
                    class="min-h-[48px] px-5 py-2.5 rounded-md border-2 border-forest text-forest hover:bg-forest hover:text-white font-display font-bold text-sm transition duration-fast active:scale-[0.98] flex items-center gap-2 shadow-none cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Cabut Status Siaga 1</span>
                </button>
            @else
                {{-- Keadaan Menunggu Persetujuan --}}
                <div class="text-xs font-sans text-warn">
                     Memerlukan tanda tangan digital otoritas BPBD sebelum siaran diluncurkan.
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="modalSetujuiBuka = true"
                        class="min-h-[48px] px-6 py-2.5 rounded-md bg-danger hover:brightness-105 active:scale-[0.98] text-white font-display font-bold text-sm transition duration-fast flex items-center gap-2 shadow-none cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Setujui Status Siaga 1</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL KONFIRMASI 2 LANGKAH PERSETUJUAN SIAGA 1 (§6.10) --}}
    <div
        x-show="modalSetujuiBuka"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/60 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-paper-2 border-2 border-danger/40 p-6 rounded-lg max-w-lg w-full space-y-4 shadow-sheet" @click.away="modalSetujuiBuka = false">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-md bg-danger/10 text-danger flex items-center justify-center text-2xl font-bold shrink-0">

                </div>
                <div>
                    <span class="text-xs font-sans font-bold text-danger">Langkah 2 dari 2 · Verifikasi Otoritas</span>
                    <h3 class="text-lg font-display font-bold text-ink">Konfirmasi Peluncuran Siaga 1</h3>
                </div>
            </div>

            <p class="text-xs text-ink-muted leading-relaxed font-sans">
                Persetujuan ini akan mengubah status resmi zona <strong class="text-ink">{{ $zonaSiaga?->nama }}</strong> menjadi <strong>SIAGA 1</strong> dan secara otomatis menyiarkan peringatan darurat ke <strong>128 pelanggan</strong> web-push serta grup petugas lapangan TNBTS.
            </p>

            <form action="{{ route('admin.siaga.setujui', $zonaSiaga?->id ?? 1) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-sans font-bold text-ink mb-1">
                        Catatan Otoritas Penyetuju (Opsional):
                    </label>
                    <textarea
                        name="catatan_persetujuan"
                        rows="2"
                        placeholder="Contoh: Berdasarkan pantauan cuaca ekstrem dan laporan kebakaran di Blok Savana, status Siaga 1 disetujui."
                        class="w-full px-3 py-2 rounded-md border border-line bg-paper text-xs text-ink font-sans focus:outline-none focus:ring-2 focus:ring-danger"
                    ></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="modalSetujuiBuka = false"
                        class="flex-1 min-h-[44px] py-2.5 rounded-md border border-line text-xs font-sans text-ink hover:bg-paper transition duration-fast cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="flex-1 min-h-[44px] py-2.5 rounded-md bg-danger hover:brightness-105 active:scale-[0.98] text-white text-xs font-sans font-bold transition duration-fast shadow-none cursor-pointer"
                    >
                        Konfirmasi & Kirim Siaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL KONFIRMASI PENCABUTAN STATUS SIAGA 1 --}}
    <div
        x-show="modalCabutBuka"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/60 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-paper-2 border-2 border-forest/40 p-6 rounded-lg max-w-lg w-full space-y-4 shadow-sheet" @click.away="modalCabutBuka = false">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-md bg-safe/10 text-safe flex items-center justify-center text-2xl font-bold shrink-0">

                </div>
                <div>
                    <span class="text-xs font-sans font-bold text-forest">Pemulihan Kawasan</span>
                    <h3 class="text-lg font-display font-bold text-ink">Pencabutan Status Siaga 1</h3>
                </div>
            </div>

            <p class="text-xs text-ink-muted leading-relaxed font-sans">
                Status Siaga 1 untuk zona <strong class="text-ink">{{ $zonaSiaga?->nama }}</strong> akan dicabut dan notifikasi pemulihan dikirim ke masyarakat.
            </p>

            <form action="{{ route('admin.siaga.cabut', $zonaSiaga?->id ?? 1) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-sans font-bold text-ink mb-1">
                        Catatan Pencabutan Status:
                    </label>
                    <textarea
                        name="catatan_pencabutan"
                        rows="2"
                        placeholder="Contoh: Kondisi savana basah setelah turun hujan 12 mm, ancaman bahaya api telah mereda."
                        class="w-full px-3 py-2 rounded-md border border-line bg-paper text-xs text-ink font-sans focus:outline-none focus:ring-2 focus:ring-forest"
                    ></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="modalCabutBuka = false"
                        class="flex-1 min-h-[44px] py-2.5 rounded-md border border-line text-xs font-sans text-ink hover:bg-paper transition duration-fast cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="flex-1 min-h-[44px] py-2.5 rounded-md bg-forest hover:bg-moss active:scale-[0.98] text-white text-xs font-sans font-bold transition duration-fast shadow-none cursor-pointer"
                    >
                        Konfirmasi Pencabutan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL JEJAK AUDIT PERSETUJUAN STATUS SIAGA --}}
    <div class="space-y-3">
        <h3 class="font-display font-bold text-base text-ink">
            Jejak Audit Keputusan Status Siaga Resmi (10 Terakhir)
        </h3>
        <div class="bg-paper-2 border border-line rounded-lg overflow-hidden shadow-none">
            <table class="w-full text-left text-xs font-sans">
                <thead class="bg-paper border-b border-line text-ink-muted text-xs">
                    <tr>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Zona</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Otoritas Penyetuju / Pencabut</th>
                        <th class="p-3">Catatan Audit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($riwayatSiaga as $item)
                        <tr class="hover:bg-paper-3 transition">
                            <td class="p-3 text-ink-muted">
                                {{ $item->disetujui_pada ? $item->disetujui_pada->format('d M Y · H:i') : ($item->dicabut_pada ? $item->dicabut_pada->format('d M Y · H:i') : '-') }}
                            </td>
                            <td class="p-3 font-bold text-ink">
                                {{ $item->zona?->kode }} · {{ $item->zona?->nama }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $item->status === 'SIAGA_1' ? 'bg-danger text-white' : 'bg-paper-3 text-ink-muted' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="p-3 font-semibold text-ink">
                                {{ $item->penyetuju?->nama ?? ($item->pencabut?->nama ?? 'Sistem Otomatis') }}
                            </td>
                            <td class="p-3 text-ink-muted font-sans text-xs">
                                {{ $item->catatan_persetujuan ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-ink-muted">
                                Belum ada riwayat persetujuan status siaga.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
