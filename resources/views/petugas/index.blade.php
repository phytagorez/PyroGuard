@extends('layouts.publik')

@section('judul', 'Dasbor Verifikasi Tiket Petugas TNBTS')

@section('konten')
<div
    x-data="petugasTiket({{ Js::from(['tabMobile' => request()->has('tiket') ? 'detail' : 'daftar', 'tiketAktifId' => $tiketAktif?->id, 'tiketAktifNomor' => $tiketAktif?->nomor_tiket ?? '', 'tiketAktifLat' => $tiketAktif?->latitude, 'tiketAktifLng' => $tiketAktif?->longitude]) }})"
    class="officer-workspace space-y-6"
>
    {{-- BARIS HEADER KHUSUS PETUGAS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-paper-2 border border-line p-4 rounded-lg">
        <div class="flex items-center gap-3">

            <div>
                <div class="flex items-center gap-2">
                    <span class="font-sans text-xs font-bold text-forest tracking-normal">DASBOR PETUGAS LAPANGAN TNBTS</span>
                    <span class="text-xs text-ink-muted font-sans">•</span>
                    <span class="text-xs font-sans text-ink font-semibold">{{ auth()->user()->nama }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-display font-bold text-ink tracking-tight">
                    Antrean Verifikasi Titik Asap & Api
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <form action="{{ route('keluar') }}" method="POST">
                @csrf
                <button
                    type="submit"
                    class="h-11 px-3 text-xs font-sans text-ink-muted hover:text-danger hover:bg-paper-3 border border-line rounded-md transition duration-fast active:scale-[0.98] cursor-pointer"
                >
                    Keluar Akun
                </button>
            </form>
        </div>
    </div>

    {{-- PESAN SUKSES / FLASH NOTIFIKASI --}}
    @if(session('pesan'))
        <div class="p-3.5 bg-safe/10 border border-safe/30 rounded-xl text-xs font-sans text-safe font-semibold flex items-center gap-2">
            <span></span>
            <span>{{ session('pesan') }}</span>
        </div>
    @endif

    {{-- TOGGLE MOBILE: DAFTAR TIKET vs DETAIL VERIFIKASI (lg:hidden) --}}
    <div class="flex lg:hidden bg-paper-2 p-1 rounded-xl border border-line text-xs font-sans">
        <button
            type="button"
            @click="tabMobile = 'daftar'"
            :class="tabMobile === 'daftar' ? 'bg-forest text-white font-bold' : 'text-ink-muted hover:text-ink'"
            class="flex-1 py-2 rounded-lg transition text-center"
        >
            Daftar Tiket ({{ $tikets->count() }})
        </button>
        <button
            type="button"
            @click="tabMobile = 'detail'"
            :class="tabMobile === 'detail' ? 'bg-forest text-white font-bold' : 'text-ink-muted hover:text-ink'"
            class="flex-1 py-2 rounded-lg transition text-center"
        >
            Verifikasi: <span x-text="tiketAktifNomor || 'Pilih Tiket'"></span>
        </button>
    </div>

    {{-- LAYOUT UTAMA GRID DUA KOLOM (DESKTOP: KIRI DAFTAR, KANAN VERIFIKASI 1 LAYAR) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- KOLOM KIRI: DAFTAR TIKET TERURUT JARAK (lg:col-span-5) --}}
        <div
            :class="tabMobile === 'daftar' ? 'block' : 'hidden lg:block'"
            class="lg:col-span-5 space-y-3"
        >
            {{-- CHIP FILTER STATUS (§7.7) --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs font-sans no-scrollbar">
                <a
                    href="{{ route('petugas.index', ['status' => 'semua']) }}"
                    class="px-3 py-1.5 rounded-md border transition whitespace-nowrap {{ $statusFilter === 'semua' ? 'bg-forest text-white border-forest font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Semua ({{ $jumlahPerStatus['semua'] }})
                </a>
                <a
                    href="{{ route('petugas.index', ['status' => 'baru']) }}"
                    class="px-3 py-1.5 rounded-md border transition whitespace-nowrap {{ $statusFilter === 'baru' ? 'bg-warn text-white border-warn font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Baru ({{ $jumlahPerStatus['baru'] }})
                </a>
                <a
                    href="{{ route('petugas.index', ['status' => 'menuju_lokasi']) }}"
                    class="px-3 py-1.5 rounded-md border transition whitespace-nowrap {{ $statusFilter === 'menuju_lokasi' ? 'bg-moss text-white border-moss font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Menuju ({{ $jumlahPerStatus['menuju_lokasi'] }})
                </a>
                <a
                    href="{{ route('petugas.index', ['status' => 'terverifikasi']) }}"
                    class="px-3 py-1.5 rounded-md border transition whitespace-nowrap {{ $statusFilter === 'terverifikasi' ? 'bg-danger text-white border-danger font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Terverifikasi ({{ $jumlahPerStatus['terverifikasi'] }})
                </a>
                <a
                    href="{{ route('petugas.index', ['status' => 'ditangani']) }}"
                    class="px-3 py-1.5 rounded-md border transition whitespace-nowrap {{ $statusFilter === 'ditangani' ? 'bg-safe text-white border-safe font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Ditangani ({{ $jumlahPerStatus['ditangani'] }})
                </a>
            </div>

            {{-- LIST KARTU TIKET TERURUT JARAK --}}
            <div class="space-y-2.5 max-h-[calc(100vh-220px)] overflow-y-auto pr-1">
                @forelse($tikets as $tiket)
                    @php
                        $isAktif = $tiketAktif && $tiketAktif->id === $tiket->id;
                        $warnaStatus = match($tiket->status_tiket) {
                            'baru' => 'bg-warn-fill text-[#5C3D06] border-warn/40',
                            'menuju_lokasi' => 'bg-moss/15 text-moss border-moss/30',
                            'terverifikasi' => 'bg-danger/15 text-danger border-danger/30',
                            'ditangani' => 'bg-safe/15 text-safe border-safe/30',
                            'selesai' => 'bg-paper-3 text-ink-muted border-line',
                            default => 'bg-paper text-ink border-line',
                        };
                    @endphp
                    <a
                        href="{{ route('petugas.index', ['status' => $statusFilter, 'tiket' => $tiket->nomor_tiket]) }}"
                        @click="tabMobile = 'detail'"
                        class="block p-3.5 rounded-md border transition duration-fast {{ $isAktif ? 'bg-paper-3 border-forest ring-2 ring-forest/30 shadow-none' : 'bg-paper-2 border-line hover:bg-paper-3' }}"
                    >
                        <div class="flex items-center justify-between text-xs font-sans mb-1">
                            <span class="font-bold text-ink text-sm">{{ $tiket->nomor_tiket }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold border {{ $warnaStatus }}">
                                {{ str_replace('_', ' ', $tiket->status_tiket) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs font-sans text-ink-muted">
                            <span class="text-ink font-medium">{{ $tiket->zona?->kode }} · {{ $tiket->zona?->nama }}</span>
                            <span class="text-forest font-bold tabular-nums">{{ $tiket->jarak_km }} km dari Anda</span>
                        </div>

                        <div class="flex items-center justify-between text-xs font-sans text-ink-muted mt-2 pt-2 border-t border-line/60">
                            <span class="uppercase text-ember font-semibold">{{ str_replace('_', ' ', $tiket->jenis_temuan) }}</span>
                            <span>{{ $tiket->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center bg-paper-2 rounded-md border border-line text-xs font-sans text-ink-muted space-y-1">
                        <p class="font-bold text-ink">Tidak ada tiket laporan.</p>
                        <p>Kawasan saat ini tidak memiliki laporan asap aktif.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- KOLOM KANAN: DETAIL & VERIFIKASI 1 LAYAR TANPA SCROLL (§3 & §7.7) (lg:col-span-7) --}}
        <div
            :class="tabMobile === 'detail' ? 'block' : 'hidden lg:block'"
            class="lg:col-span-7 bg-paper-2 border border-line rounded-lg p-5 sm:p-6 shadow-none space-y-4"
        >
            @if($tiketAktif)
                {{-- 1. HEADER DETAIL TIKET --}}
                <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-sans text-xl sm:text-2xl font-bold text-ink tracking-tight">{{ $tiketAktif->nomor_tiket }}</span>
                            <span class="font-sans text-xs px-2.5 py-0.5 rounded-full font-bold bg-paper-3 border border-line text-ink">
                                {{ str_replace('_', ' ', $tiketAktif->status_tiket) }}
                            </span>
                            @if($tiketAktif->hasil_verifikasi)
                                <span class="font-sans text-xs px-2.5 py-0.5 rounded-full font-bold {{ $tiketAktif->hasil_verifikasi === 'api_nyata' ? 'bg-danger text-white' : 'bg-paper-3 text-ink' }}">
                                    {{ str_replace('_', ' ', $tiketAktif->hasil_verifikasi) }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs font-sans text-ink-muted">
                            {{ $tiketAktif->zona?->kode }} · {{ $tiketAktif->zona?->nama }} · Dilaporkan {{ $tiketAktif->created_at->format('H:i') }} WIB ({{ $tiketAktif->created_at->diffForHumans() }})
                        </p>
                    </div>

                    {{-- Estimasi Jarak --}}
                    <div class="text-right shrink-0">
                        <span class="text-xs font-sans text-ink-muted block">Jarak Lapangan:</span>
                        <span class="text-lg font-display font-bold text-forest tabular-nums">{{ $tiketAktif->jarak_km ?? '1.8' }} km dari Anda</span>
                    </div>
                </div>

                {{-- 2. TICKET TIMELINE 6 STATUS VERTICAL/HORIZONTAL RINGKAS (§6.9) --}}
                <div class="p-3 bg-paper rounded-md border border-line">
                    <span class="text-xs font-sans text-ink-muted font-bold block mb-1.5">Riwayat Alur Tiket:</span>
                    <div class="grid grid-cols-6 gap-1 text-center font-sans text-xs">
                        @php
                            $tahapan = ['baru', 'ditugaskan', 'menuju_lokasi', 'terverifikasi', 'ditangani', 'selesai'];
                            $indeksAktif = array_search($tiketAktif->status_tiket, $tahapan);
                            if ($indeksAktif === false) $indeksAktif = 0;
                        @endphp
                        @foreach($tahapan as $idx => $t)
                            <div class="space-y-1">
                                <div class="w-full h-1.5 rounded-full {{ $idx <= $indeksAktif ? 'bg-forest' : 'bg-paper-3' }}"></div>
                                <span class="block truncate {{ $idx === $indeksAktif ? 'font-bold text-forest' : 'text-ink-muted' }}">
                                    {{ ucfirst(str_replace('_', ' ', $t)) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- 3. INFORMASI PELAPOR & FOTO LAPORAN & PETA MINI --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-sans">
                    <div class="p-3 bg-paper rounded-md border border-line space-y-1">
                        <span class="text-ink-muted block text-xs font-bold">Data Pelapor:</span>
                        <div class="font-bold text-ink">{{ $tiketAktif->pelapor?->nama ?? 'Tamu (Masyarakat/Wisatawan)' }}</div>
                        <div class="text-ink-muted">Gejala: <strong class="text-ember">{{ str_replace('_', ' ', $tiketAktif->jenis_temuan) }}</strong></div>
                        <div class="text-ink-muted">Akurasi GPS: {{ $tiketAktif->akurasi_gps ? '±'.$tiketAktif->akurasi_gps.' m' : 'Pin Peta' }}</div>
                        @if($tiketAktif->catatan)
                            <div class="text-ink mt-1 pt-1 border-t border-line/60 italic leading-snug">
                                "{{ $tiketAktif->catatan }}"
                            </div>
                        @endif
                    </div>

                    {{-- Foto Pelapor --}}
                    <div class="p-3 bg-paper rounded-md border border-line space-y-1">
                        <span class="text-ink-muted block text-xs font-bold">Foto Bukti Lapangan:</span>
                        @if($tiketAktif->foto_1 || $tiketAktif->foto_2)
                            <div class="flex gap-2">
                                @if($tiketAktif->foto_1)
                                    <a href="{{ Storage::url($tiketAktif->foto_1) }}" target="_blank" class="w-16 h-16 rounded-md overflow-hidden border border-line shrink-0 block">
                                        <img src="{{ Storage::url($tiketAktif->foto_1) }}" class="w-full h-full object-cover" alt="Foto laporan 1">
                                    </a>
                                @endif
                                @if($tiketAktif->foto_2)
                                    <a href="{{ Storage::url($tiketAktif->foto_2) }}" target="_blank" class="w-16 h-16 rounded-md overflow-hidden border border-line shrink-0 block">
                                        <img src="{{ Storage::url($tiketAktif->foto_2) }}" class="w-full h-full object-cover" alt="Foto laporan 2">
                                    </a>
                                @endif
                            </div>
                        @else
                            <p class="text-ink-muted italic pt-1">Pelapor tidak melampirkan foto.</p>
                        @endif
                    </div>
                </div>

                {{-- Peta Mini Leaflet Lokasi Tiket Lapangan --}}
                <div class="relative w-full h-36 rounded-md overflow-hidden border border-line bg-paper-3">
                    <div id="petaMiniPetugas" class="w-full h-full z-10" tabindex="0" role="region" aria-label="Peta Mini Posisi Tiket Lapangan"></div>
                    <div class="absolute bottom-1.5 left-2 z-20 bg-paper/90 backdrop-blur-xs px-2 py-0.5 rounded text-xs font-sans text-ink border border-line">
                        Koordinat: <span class="tabular-nums font-semibold">{{ $tiketAktif->latitude }}, {{ $tiketAktif->longitude }}</span>
                    </div>
                </div>

                {{-- 4. AKSI NAVIGASI MENUJU LOKASI --}}
                <div class="flex items-center gap-2 pt-1">
                    @if($tiketAktif->status_tiket === 'baru')
                        <form action="{{ route('petugas.menuju-lokasi', $tiketAktif->nomor_tiket) }}" method="POST" class="flex-1">
                            @csrf
                            <button
                                type="submit"
                                class="w-full h-11 bg-moss hover:bg-forest active:scale-[0.98] text-white rounded-md font-display font-bold text-xs transition duration-fast flex items-center justify-center gap-1.5 shadow-none"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <span>Menuju Lokasi Tiket</span>
                            </button>
                        </form>
                    @endif

                    {{-- Tombol Buka Rute Google Maps --}}
                    <a
                        href="https://www.google.com/maps/dir/?api=1&destination={{ $tiketAktif->latitude }},{{ $tiketAktif->longitude }}"
                        target="_blank"
                        class="h-11 px-4 bg-paper hover:bg-paper-3 border border-line rounded-md font-sans text-xs font-semibold text-ink flex items-center justify-center gap-1.5 transition duration-fast shrink-0 active:scale-[0.98]"
                    >
                        <span>Buka Google Maps</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>

                    @if(in_array($tiketAktif->status_tiket, ['terverifikasi', 'ditangani']))
                        <form action="{{ route('petugas.tandai-ditangani', $tiketAktif->nomor_tiket) }}" method="POST" class="flex-1">
                            @csrf
                            <button
                                type="submit"
                                class="w-full h-11 bg-safe hover:brightness-105 active:scale-[0.98] text-white rounded-md font-display font-bold text-xs transition duration-fast flex items-center justify-center gap-1.5 shadow-none"
                            >
                                <span>{{ $tiketAktif->status_tiket === 'terverifikasi' ? 'Tandai Ditangani' : 'Tandai Selesai' }}</span>
                            </button>
                        </form>
                    @endif
                </div>

                {{-- 5. TIGA TOMBOL HASIL VERIFIKASI BERUKURAN EKSTRA BESAR (56 PX DENGAN JEDA 8 PX) (§6.9 & §7.7) --}}
                <div class="space-y-2 pt-2 border-t border-line">
                    <span class="text-xs font-sans font-bold text-ink block">
                        Keputusan Hasil Verifikasi Lapangan (Wajib Dikonfirmasi):
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        {{-- Tombol 1: Api Nyata (Merah pekat solid) --}}
                        <button
                            type="button"
                            @click="konfirmasiVerifikasi('api_nyata', 'API NYATA', 'Titik api akan segera ditampilkan di peta publik kawasan dan peringatan darurat disiarkan ke pengguna dalam radius 3 km.', '#9E2B22')"
                            class="h-14 p-2 rounded-md bg-danger hover:brightness-105 active:scale-[0.98] text-white font-display font-bold text-sm transition duration-fast flex flex-col items-center justify-center text-center shadow-none cursor-pointer border border-danger"
                        >
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.533 1.05-.672 1.63a7.48 7.48 0 00-.28 1.48c-.027.318-.035.637-.024.955.066.035.132.07.198.106.66.36 1.347.78 1.83 1.36.467.562.72 1.25.72 2.016 0 1.264-.783 2.378-1.92 2.806.27.47.66.86 1.14 1.13.9.51 1.99.64 3.01.37 1.4-.38 2.5-1.5 2.85-2.91.4-1.63-.15-3.32-1.38-4.32-.4-.32-.82-.62-1.25-.92-.68-.48-1.38-1.03-1.84-1.74-.29-.44-.47-.94-.57-1.45a3.97 3.97 0 00-.08-.7z" clip-rule="evenodd"/></svg>
                                <span>API NYATA</span>
                            </span>
                            <span class="text-xs font-sans text-white/80 font-normal">Peta Publik + Siaran Darurat</span>
                        </button>

                        {{-- Tombol 2: Bukan Api (Netral outline garis tebal) --}}
                        <button
                            type="button"
                            @click="konfirmasiVerifikasi('bukan_api', 'BUKAN API', 'Laporan akan ditutup sebagai nihil/bukan kebakaran. Tidak ada siaran kepanikan ke publik.', '#173D2C')"
                            class="h-14 p-2 rounded-md bg-transparent hover:bg-forest/5 active:scale-[0.98] border-2 border-forest text-forest font-display font-bold text-sm transition duration-fast flex flex-col items-center justify-center text-center cursor-pointer"
                        >
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>BUKAN API</span>
                            </span>
                            <span class="text-xs font-sans text-ink-muted font-normal">Tutup Tiket · Nihil</span>
                        </button>

                        {{-- Tombol 3: Ganda (Netral soft) --}}
                        <button
                            type="button"
                            @click="konfirmasiVerifikasi('ganda', 'TIKET GANDA', 'Laporan ini merupakan duplikat dari kejadian yang sama dan akan digabung ke tiket aktif terdekat.', '#4C5A52')"
                            class="h-14 p-2 rounded-md bg-paper-3 hover:bg-paper active:scale-[0.98] text-ink border border-line font-display font-bold text-sm transition duration-fast flex flex-col items-center justify-center text-center cursor-pointer"
                        >
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <span>GANDA</span>
                            </span>
                            <span class="text-xs font-sans text-ink-muted font-normal">Gabung ke Tiket Induk</span>
                        </button>
                    </div>
                </div>

                {{-- FORM TERSEMBUNYI UNTUK PROSES VERIFIKASI --}}
                <form
                    id="formVerifikasiTiket"
                    action="{{ route('petugas.verifikasi', $tiketAktif->nomor_tiket) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="hidden"
                >
                    @csrf
                    <input type="hidden" name="hasil_verifikasi" id="formVerifikasiHasil" value="api_nyata">
                    <input type="hidden" name="tiket_induk_id" id="formVerifikasiTiketInduk" value="">
                    <input type="hidden" name="catatan" x-model="catatanVerifikasi">
                </form>

                {{-- MODAL PEMILIHAN TIKET GANDA (SWEETALERT/ALPINE) --}}
                <div
                    x-show="dialogGandaBuka"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/50 backdrop-blur-xs"
                    style="display: none;"
                >
                    <div class="bg-paper-2 border border-line p-5 rounded-lg max-w-sm w-full space-y-4 shadow-sheet" @click.away="dialogGandaBuka = false">
                        <div>
                            <h3 class="font-display font-bold text-base text-ink">Gabungkan ke Tiket Induk</h3>
                            <p class="text-xs text-ink-muted mt-0.5">Pilih nomor tiket utama yang sudah terdaftar untuk insiden ini:</p>
                        </div>

                        <select x-model="tiketIndukId" class="w-full px-3 py-2 rounded-md border border-line bg-paper text-xs font-sans">
                            <option value="">-- Pilih Tiket Induk --</option>
                            @foreach($tiketLainAktif as $tInduk)
                                <option value="{{ $tInduk->id }}">{{ $tInduk->nomor_tiket }} ({{ $tInduk->jenis_temuan }})</option>
                            @endforeach
                        </select>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="dialogGandaBuka = false"
                                class="flex-1 py-2 rounded-md border border-line text-xs font-sans text-ink hover:bg-paper"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="kirimVerifikasiGanda()"
                                class="flex-1 py-2 rounded-md bg-forest text-white text-xs font-sans font-bold hover:bg-moss"
                            >
                                Gabung Tiket
                            </button>
                        </div>
                    </div>
                </div>

            @else
                <div class="p-12 text-center text-ink-muted font-sans text-xs space-y-2">
                    <p class="font-bold text-base text-ink">Belum Ada Tiket Dipilih</p>
                    <p>Pilih salah satu tiket di sebelah kiri untuk melihat rincian lokasi dan memverifikasi temuan.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
