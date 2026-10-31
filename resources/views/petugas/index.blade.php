@extends('layouts.publik')

@section('judul', 'Dasbor Verifikasi Tiket Petugas TNBTS')

@section('konten')
<div 
    x-data="{
        tabMobile: '{{ request()->has('tiket') ? 'detail' : 'daftar' }}',
        tiketAktifId: {{ $tiketAktif?->id ?? 'null' }},
        tiketAktifNomor: '{{ $tiketAktif?->nomor_tiket ?? '' }}',
        hasilDipilih: 'api_nyata',
        dialogGandaBuka: false,
        tiketIndukId: '',
        catatanVerifikasi: '',

        konfirmasiVerifikasi(hasil, label, konsekuensi, warna) {
            this.hasilDipilih = hasil;
            
            if (hasil === 'ganda') {
                this.dialogGandaBuka = true;
                return;
            }

            if (window.Swal) {
                window.Swal.fire({
                    title: 'Verifikasi: ' + label + '?',
                    html: '<div class=\'text-xs font-mono text-left p-2 bg-paper rounded-lg border border-line\'>' +
                          '<strong class=\'text-ink block mb-1\'>Konsekuensi Keputusan:</strong>' +
                          '<p class=\'text-ink-muted\'>' + konsekuensi + '</p>' +
                          '</div>',
                    icon: hasil === 'api_nyata' ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: warna,
                    cancelButtonColor: '#4C5A52',
                    confirmButtonText: 'Ya, Konfirmasi ' + label,
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('formVerifikasiHasil').value = hasil;
                        document.getElementById('formVerifikasiTiket').submit();
                    }
                });
            } else {
                if (confirm('Konfirmasi verifikasi ' + label + '?\n\n' + konsekuensi)) {
                    document.getElementById('formVerifikasiHasil').value = hasil;
                    document.getElementById('formVerifikasiTiket').submit();
                }
            }
        },

        kirimVerifikasiGanda() {
            if (!this.tiketIndukId) {
                alert('Pilih nomor tiket induk yang aktif.');
                return;
            }
            document.getElementById('formVerifikasiHasil').value = 'ganda';
            document.getElementById('formVerifikasiTiketInduk').value = this.tiketIndukId;
            document.getElementById('formVerifikasiTiket').submit();
        }
    }"
    class="space-y-4 max-w-7xl mx-auto pb-16"
>
    {{-- BARIS HEADER KHUSUS PETUGAS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-paper-2 border border-line p-4 rounded-2xl">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-forest text-white flex items-center justify-center font-bold text-lg shadow-sm">
                🌲
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider">DASBOR PETUGAS LAPANGAN TNBTS</span>
                    <span class="text-xs text-ink-muted font-mono">•</span>
                    <span class="text-xs font-mono text-ink font-semibold">{{ auth()->user()->nama }}</span>
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
                    class="h-9 px-3 text-xs font-mono text-ink-muted hover:text-danger hover:bg-paper-3 border border-line rounded-lg transition"
                >
                    Keluar Akun
                </button>
            </form>
        </div>
    </div>

    {{-- PESAN SUKSES / FLASH NOTIFIKASI --}}
    @if(session('pesan'))
        <div class="p-3.5 bg-safe/10 border border-safe/30 rounded-xl text-xs font-mono text-safe font-semibold flex items-center gap-2">
            <span>✓</span>
            <span>{{ session('pesan') }}</span>
        </div>
    @endif

    {{-- TOGGLE MOBILE: DAFTAR TIKET vs DETAIL VERIFIKASI (lg:hidden) --}}
    <div class="flex lg:hidden bg-paper-2 p-1 rounded-xl border border-line text-xs font-mono">
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
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs font-mono no-scrollbar">
                <a 
                    href="{{ route('petugas.index', ['status' => 'semua']) }}"
                    class="px-3 py-1.5 rounded-lg border transition whitespace-nowrap {{ $statusFilter === 'semua' ? 'bg-forest text-white border-forest font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Semua ({{ $jumlahPerStatus['semua'] }})
                </a>
                <a 
                    href="{{ route('petugas.index', ['status' => 'baru']) }}"
                    class="px-3 py-1.5 rounded-lg border transition whitespace-nowrap {{ $statusFilter === 'baru' ? 'bg-amber-500 text-white border-amber-600 font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Baru ({{ $jumlahPerStatus['baru'] }})
                </a>
                <a 
                    href="{{ route('petugas.index', ['status' => 'menuju_lokasi']) }}"
                    class="px-3 py-1.5 rounded-lg border transition whitespace-nowrap {{ $statusFilter === 'menuju_lokasi' ? 'bg-moss text-white border-moss font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Menuju ({{ $jumlahPerStatus['menuju_lokasi'] }})
                </a>
                <a 
                    href="{{ route('petugas.index', ['status' => 'terverifikasi']) }}"
                    class="px-3 py-1.5 rounded-lg border transition whitespace-nowrap {{ $statusFilter === 'terverifikasi' ? 'bg-danger text-white border-danger font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
                >
                    Terverifikasi ({{ $jumlahPerStatus['terverifikasi'] }})
                </a>
                <a 
                    href="{{ route('petugas.index', ['status' => 'ditangani']) }}"
                    class="px-3 py-1.5 rounded-lg border transition whitespace-nowrap {{ $statusFilter === 'ditangani' ? 'bg-safe text-white border-safe font-bold' : 'bg-paper-2 text-ink border-line hover:bg-paper-3' }}"
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
                            'baru' => 'bg-amber-500/15 text-warn border-warn/30',
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
                        class="block p-3.5 rounded-2xl border transition duration-fast {{ $isAktif ? 'bg-paper-3 border-forest ring-2 ring-forest/30 shadow-card' : 'bg-paper-2 border-line hover:bg-paper-3' }}"
                    >
                        <div class="flex items-center justify-between text-xs font-mono mb-1">
                            <span class="font-bold text-ink text-sm">{{ $tiket->nomor_tiket }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-bold border {{ $warnaStatus }}">
                                {{ str_replace('_', ' ', $tiket->status_tiket) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs font-mono text-ink-muted">
                            <span class="text-ink font-medium">{{ $tiket->zona?->kode }} · {{ $tiket->zona?->nama }}</span>
                            <span class="text-forest font-bold tabular-nums">± {{ $tiket->jarak_km }} km</span>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-mono text-ink-muted mt-2 pt-2 border-t border-line/60">
                            <span class="uppercase text-ember font-semibold">{{ str_replace('_', ' ', $tiket->jenis_temuan) }}</span>
                            <span>{{ $tiket->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center bg-paper-2 rounded-2xl border border-line text-xs font-mono text-ink-muted space-y-1">
                        <p class="font-bold text-ink">Tidak ada tiket laporan.</p>
                        <p>Kawasan saat ini tidak memiliki laporan asap aktif.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- KOLOM KANAN: DETAIL & VERIFIKASI 1 LAYAR TANPA SCROLL (§3 & §7.7) (lg:col-span-7) --}}
        <div 
            :class="tabMobile === 'detail' ? 'block' : 'hidden lg:block'"
            class="lg:col-span-7 bg-paper-2 border border-line rounded-3xl p-5 sm:p-6 shadow-card space-y-5"
        >
            @if($tiketAktif)
                {{-- 1. HEADER DETAIL TIKET --}}
                <div class="flex items-start justify-between gap-3 border-b border-line pb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xl sm:text-2xl font-bold text-ink tracking-tight">{{ $tiketAktif->nomor_tiket }}</span>
                            <span class="font-mono text-xs px-2.5 py-0.5 rounded-full font-bold uppercase bg-paper-3 border border-line text-ink">
                                {{ str_replace('_', ' ', $tiketAktif->status_tiket) }}
                            </span>
                            @if($tiketAktif->hasil_verifikasi)
                                <span class="font-mono text-xs px-2.5 py-0.5 rounded-full font-bold uppercase {{ $tiketAktif->hasil_verifikasi === 'api_nyata' ? 'bg-danger text-white' : 'bg-paper-3 text-ink' }}">
                                    {{ str_replace('_', ' ', $tiketAktif->hasil_verifikasi) }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs font-mono text-ink-muted">
                            {{ $tiketAktif->zona?->kode }} · {{ $tiketAktif->zona?->nama }} · Dilaporkan {{ $tiketAktif->created_at->format('H:i') }} WIB ({{ $tiketAktif->created_at->diffForHumans() }})
                        </p>
                    </div>

                    {{-- Estimasi Jarak --}}
                    <div class="text-right shrink-0">
                        <span class="text-[10px] font-mono text-ink-muted uppercase block">Jarak dari Pos:</span>
                        <span class="text-xl font-display font-bold text-forest tabular-nums">± {{ $tiketAktif->jarak_km ?? '1.8' }} km</span>
                    </div>
                </div>

                {{-- 2. TICKET TIMELINE 6 STATUS VERTICAL/HORIZONTAL RINGKAS (§6.9) --}}
                <div class="p-3 bg-paper rounded-2xl border border-line">
                    <span class="text-[10px] font-mono text-ink-muted uppercase font-bold block mb-2">Riwayat Alur Tiket:</span>
                    <div class="grid grid-cols-6 gap-1 text-center font-mono text-[10px]">
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

                {{-- 3. INFORMASI PELAPOR & FOTO LAPORAN --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-mono">
                    <div class="p-3 bg-paper rounded-xl border border-line space-y-1">
                        <span class="text-ink-muted block text-[10px] uppercase font-bold">Data Pelapor:</span>
                        <div class="font-bold text-ink">{{ $tiketAktif->pelapor?->nama ?? 'Tamu (Masyarakat/Wisatawan)' }}</div>
                        <div class="text-ink-muted">Gejala: <strong class="text-ember uppercase">{{ str_replace('_', ' ', $tiketAktif->jenis_temuan) }}</strong></div>
                        <div class="text-ink-muted">Akurasi GPS: {{ $tiketAktif->akurasi_gps ? '±'.$tiketAktif->akurasi_gps.' m' : 'Pin Peta' }}</div>
                        @if($tiketAktif->catatan)
                            <div class="text-ink mt-1 pt-1 border-t border-line/60 italic leading-snug">
                                "{{ $tiketAktif->catatan }}"
                            </div>
                        @endif
                    </div>

                    {{-- Foto Pelapor --}}
                    <div class="p-3 bg-paper rounded-xl border border-line space-y-1">
                        <span class="text-ink-muted block text-[10px] uppercase font-bold">Foto Bukti Lapangan:</span>
                        @if($tiketAktif->foto_1 || $tiketAktif->foto_2)
                            <div class="flex gap-2">
                                @if($tiketAktif->foto_1)
                                    <a href="{{ Storage::url($tiketAktif->foto_1) }}" target="_blank" class="w-16 h-16 rounded-lg overflow-hidden border border-line shrink-0 block">
                                        <img src="{{ Storage::url($tiketAktif->foto_1) }}" class="w-full h-full object-cover" alt="Foto laporan 1">
                                    </a>
                                @endif
                                @if($tiketAktif->foto_2)
                                    <a href="{{ Storage::url($tiketAktif->foto_2) }}" target="_blank" class="w-16 h-16 rounded-lg overflow-hidden border border-line shrink-0 block">
                                        <img src="{{ Storage::url($tiketAktif->foto_2) }}" class="w-full h-full object-cover" alt="Foto laporan 2">
                                    </a>
                                @endif
                            </div>
                        @else
                            <p class="text-ink-muted italic pt-1">Pelapor tidak melampirkan foto.</p>
                        @endif
                    </div>
                </div>

                {{-- 4. AKSI NAVIGASI MENUJU LOKASI --}}
                <div class="flex items-center gap-3">
                    @if($tiketAktif->status_tiket === 'baru')
                        <form action="{{ route('petugas.menuju-lokasi', $tiketAktif->nomor_tiket) }}" method="POST" class="flex-1">
                            @csrf
                            <button 
                                type="submit"
                                class="w-full h-12 bg-moss hover:bg-forest active:scale-98 text-white rounded-xl font-display font-bold text-sm transition flex items-center justify-center gap-2 shadow-sm"
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
                        class="h-12 px-4 bg-paper hover:bg-paper-3 border border-line rounded-xl font-mono text-xs font-semibold text-ink flex items-center justify-center gap-1.5 transition shrink-0"
                    >
                        <span>Navigasi Peta</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>

                    @if(in_array($tiketAktif->status_tiket, ['terverifikasi', 'ditangani']))
                        <form action="{{ route('petugas.tandai-ditangani', $tiketAktif->nomor_tiket) }}" method="POST" class="flex-1">
                            @csrf
                            <button 
                                type="submit"
                                class="w-full h-12 bg-safe hover:brightness-105 active:scale-98 text-white rounded-xl font-display font-bold text-sm transition flex items-center justify-center gap-2 shadow-sm"
                            >
                                <span>{{ $tiketAktif->status_tiket === 'terverifikasi' ? 'Tandai Ditangani' : 'Tandai Selesai' }}</span>
                            </button>
                        </form>
                    @endif
                </div>

                {{-- 5. TIGA TOMBOL HASIL VERIFIKASI BERUKURAN BESAR (>= 56 PX) (§6.9 & §7.7) --}}
                <div class="space-y-2 pt-2 border-t border-line">
                    <span class="text-xs font-mono font-bold uppercase text-ink block">
                        Keputusan Hasil Verifikasi Lapangan (Wajib Dikonfirmasi):
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        {{-- Tombol 1: Api Nyata --}}
                        <button 
                            type="button" 
                            @click="konfirmasiVerifikasi('api_nyata', 'API NYATA', 'Titik api akan segera ditampilkan di peta publik kawasan dan peringatan darurat disiarkan ke pengguna dalam radius 3 km.', '#D9544A')"
                            class="min-h-[56px] p-3 rounded-2xl bg-danger hover:brightness-105 text-white font-display font-bold text-sm transition active:scale-95 flex flex-col items-center justify-center text-center shadow-card"
                        >
                            <span>🔥 API NYATA</span>
                            <span class="text-[10px] font-mono opacity-80 font-normal">Tampil di Peta + Siaran 3 km</span>
                        </button>

                        {{-- Tombol 2: Bukan Api --}}
                        <button 
                            type="button" 
                            @click="konfirmasiVerifikasi('bukan_api', 'BUKAN API', 'Laporan akan ditutup sebagai nihil/bukan kebakaran. Tidak ada siaran kepanikan ke publik.', '#173D2C')"
                            class="min-h-[56px] p-3 rounded-2xl bg-forest hover:bg-moss text-white font-display font-bold text-sm transition active:scale-95 flex flex-col items-center justify-center text-center shadow-card"
                        >
                            <span>🍃 BUKAN API</span>
                            <span class="text-[10px] font-mono opacity-80 font-normal">Tutup Tiket · Tanpa Siaran</span>
                        </button>

                        {{-- Tombol 3: Ganda --}}
                        <button 
                            type="button" 
                            @click="konfirmasiVerifikasi('ganda', 'TIKET GANDA', 'Laporan ini merupakan duplikat dari kejadian yang sama dan akan digabung ke tiket aktif terdekat.', '#4C5A52')"
                            class="min-h-[56px] p-3 rounded-2xl bg-paper-3 hover:bg-line text-ink border border-line font-display font-bold text-sm transition active:scale-95 flex flex-col items-center justify-center text-center shadow-sm"
                        >
                            <span>🔗 GANDA</span>
                            <span class="text-[10px] font-mono text-ink-muted font-normal">Gabung ke Tiket Induk</span>
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
                    <div class="bg-paper-2 border border-line p-5 rounded-2xl max-w-sm w-full space-y-4 shadow-sheet" @click.away="dialogGandaBuka = false">
                        <div>
                            <h3 class="font-display font-bold text-base text-ink">Gabungkan ke Tiket Induk</h3>
                            <p class="text-xs text-ink-muted mt-0.5">Pilih nomor tiket utama yang sudah terdaftar untuk insiden ini:</p>
                        </div>

                        <select x-model="tiketIndukId" class="w-full px-3 py-2 rounded-xl border border-line bg-paper text-xs font-mono">
                            <option value="">-- Pilih Tiket Induk --</option>
                            @foreach($tiketLainAktif as $tInduk)
                                <option value="{{ $tInduk->id }}">{{ $tInduk->nomor_tiket }} ({{ $tInduk->jenis_temuan }})</option>
                            @endforeach
                        </select>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="dialogGandaBuka = false" 
                                class="flex-1 py-2 rounded-xl border border-line text-xs font-mono text-ink hover:bg-paper"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="kirimVerifikasiGanda()" 
                                class="flex-1 py-2 rounded-xl bg-forest text-white text-xs font-mono font-bold hover:bg-moss"
                            >
                                Gabung Tiket
                            </button>
                        </div>
                    </div>
                </div>

            @else
                <div class="p-12 text-center text-ink-muted font-mono text-xs space-y-2">
                    <p class="font-bold text-base text-ink">Belum Ada Tiket Dipilih</p>
                    <p>Pilih salah satu tiket di sebelah kiri untuk melihat rincian lokasi dan memverifikasi temuan.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
