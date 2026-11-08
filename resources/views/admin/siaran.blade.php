@extends('layouts.admin')

@section('judul', 'Editor Siaran Multi-Kanal')

@section('konten')
<div
    x-data="{
        jenis: 'waspada',
        zonaId: '{{ $zonas->firstWhere('kode', 'BRM-04')?->id ?? 4 }}',
        kanal: 'telegram_publik',
        judul: 'WASPADA — Savana & Jemplang (BRM-04)',
        pesan: 'Hari ke-11 tanpa hujan. FDI 62/100, angin 19 km/j.\nJangan membakar sampah. Padamkan puntung rokok.\nJangan menyalakan flare atau api terbuka.\nDarurat: 112 · Pantau: pyrog.uard/brm04',

        muatTemplat(tipe) {
            this.jenis = tipe;
            if (tipe === 'waspada') {
                this.judul = 'WASPADA — Savana & Jemplang (BRM-04)';
                this.pesan = 'Hari ke-11 tanpa hujan. FDI 62/100, angin 19 km/j.\nJangan membakar sampah. Padamkan puntung rokok.\nJangan menyalakan flare atau api terbuka.\nDarurat: 112 · Pantau: pyrog.uard/brm04';
            } else if (tipe === 'siaga_1') {
                this.judul = 'SIAGA 1 — Savana & Jemplang (BRM-04)';
                this.pesan = 'Hari ke-19 tanpa hujan. FDI 86/100. Angin 28 km/j.\nDilarang keras membuat api terbuka sampai status dicabut.\nSiapkan masker dan ketahui jalur keluar.\nDarurat: 112 · Info: pyrog.uard/brm04';
            } else if (tipe === 'titik_api') {
                this.judul = 'PERINGATAN TITIK API TERVERIFIKASI — Savana';
                this.pesan = 'Asap terverifikasi 1,8 km arah barat daya.\nRegu menuju lokasi. Angin ke barat laut.\nJauhi area savana. Ikuti arahan petugas.';
            } else if (tipe === 'pencabutan') {
                this.judul = 'SIAGA 1 DICABUT — Savana & Jemplang';
                this.pesan = 'FDI turun ke 41/100 setelah hujan.\nTetap jaga kebersihan lahan dan jangan membakar sampah.\nDarurat: 112 · Pantau: pyrog.uard';
            } else {
                this.judul = 'INFORMASI DARURAT KAWASAN BROMO';
                this.pesan = 'Diimbau seluruh wisatawan dan pelaku jip untuk berhati-hati melintasi kaldera.';
            }
        }
    }"
    class="max-w-4xl mx-auto space-y-6"
>
    <div>

        <h1 class="text-2xl font-display font-bold text-ink tracking-tight mt-0.5">
            Editor Siaran Multi-Kanal Berbasis Templat
        </h1>
        <p class="text-xs text-ink-muted mt-1">
            Kirim siaran terarah ke kanal publik Telegram, grup siaga petugas, atau web-push browser.
        </p>
    </div>

    {{-- TOMBOL PINTASAN TEMPLAT BAKU (§14.1) --}}
    <div class="space-y-2">
        <span class="text-xs font-sans font-bold text-ink-muted block">Pilih Templat Baku:</span>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-sans">
            <button
                type="button"
                @click="muatTemplat('waspada')"
                :class="jenis === 'waspada' ? 'bg-warn text-white border-warn' : 'bg-paper-2 text-ink border-line hover:bg-paper-3'"
                class="min-h-[44px] p-2.5 rounded-md border font-bold transition duration-fast active:scale-[0.98] text-center cursor-pointer"
            >
                Templat Waspada
            </button>
            <button
                type="button"
                @click="muatTemplat('siaga_1')"
                :class="jenis === 'siaga_1' ? 'bg-danger text-white border-danger' : 'bg-paper-2 text-ink border-line hover:bg-paper-3'"
                class="min-h-[44px] p-2.5 rounded-md border font-bold transition duration-fast active:scale-[0.98] text-center cursor-pointer"
            >
                Templat Siaga 1
            </button>
            <button
                type="button"
                @click="muatTemplat('titik_api')"
                :class="jenis === 'titik_api' ? 'bg-ember text-white border-ember' : 'bg-paper-2 text-ink border-line hover:bg-paper-3'"
                class="min-h-[44px] p-2.5 rounded-md border font-bold transition duration-fast active:scale-[0.98] text-center cursor-pointer"
            >
                Templat Titik Api
            </button>
            <button
                type="button"
                @click="muatTemplat('pencabutan')"
                :class="jenis === 'pencabutan' ? 'bg-forest text-white border-forest' : 'bg-paper-2 text-ink border-line hover:bg-paper-3'"
                class="min-h-[44px] p-2.5 rounded-md border font-bold transition duration-fast active:scale-[0.98] text-center cursor-pointer"
            >
                Templat Pencabutan
            </button>
        </div>
    </div>

    {{-- FORMULIR EDITOR SIARAN --}}
    <form action="{{ route('admin.siaran.kirim') }}" method="POST" class="p-6 bg-paper-2 border border-line rounded-lg shadow-none space-y-5">
        @csrf
        <input type="hidden" name="jenis_siaran" x-model="jenis">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Target Zona --}}
            <div class="space-y-1">
                <label class="block text-xs font-sans font-bold text-ink">
                    Zona Sasaran
                </label>
                <select name="zona_id" x-model="zonaId" class="w-full px-3 py-2.5 rounded-md border border-line bg-paper text-xs font-sans">
                    <option value="">-- Seluruh Kawasan Bromo --</option>
                    @foreach($zonas as $z)
                        <option value="{{ $z->id }}">{{ $z->kode }} · {{ $z->nama }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Pilihan Kanal --}}
            <div class="space-y-1">
                <label class="block text-xs font-sans font-bold text-ink">
                    Kanal Pengiriman
                </label>
                <select name="kanal" x-model="kanal" class="w-full px-3 py-2.5 rounded-md border border-line bg-paper text-xs font-sans">
                    <option value="telegram_publik">Kanal Publik Telegram (@PyroGuardBromo)</option>
                    <option value="grup_petugas">Grup Petugas Lapangan TNBTS</option>
                    <option value="web_push">Web-Push Browser Pengunjung</option>
                </select>
            </div>
        </div>

        {{-- Judul Pesan --}}
        <div class="space-y-1">
            <label class="block text-xs font-sans font-bold text-ink">
                Judul Notifikasi
            </label>
            <input
                type="text"
                name="judul_pesan"
                x-model="judul"
                required
                class="w-full px-3.5 py-2.5 rounded-md border border-line bg-paper text-sm font-display font-bold text-ink focus:ring-2 focus:ring-forest"
            >
        </div>

        {{-- Isi Pesan Baku --}}
        <div class="space-y-1">
            <label class="block text-xs font-sans font-bold text-ink">
                Isi Pesan Siaran (Struktur §14.1)
            </label>
            <textarea
                name="isi_pesan"
                x-model="pesan"
                rows="6"
                required
                class="w-full px-3.5 py-2.5 rounded-md border border-line bg-paper text-xs font-sans text-ink leading-relaxed focus:ring-2 focus:ring-forest"
            ></textarea>
        </div>

        {{-- Kotak Pratinjau Siaran --}}
        <div class="p-4 bg-paper rounded-md border border-line space-y-1.5 text-xs font-sans">
            <span class="text-xs text-ink-muted font-bold block">Pratinjau Langsung:</span>
            <div class="font-bold text-ink text-sm" x-text="judul"></div>
            <pre class="whitespace-pre-wrap text-ink-muted font-sans text-xs leading-relaxed" x-text="pesan"></pre>
        </div>

        <button
            type="submit"
            class="w-full h-14 bg-forest hover:bg-moss active:scale-[0.98] text-white rounded-md font-display font-bold text-sm transition duration-fast flex items-center justify-center gap-2 shadow-none cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            <span>Kirim Siaran Sekarang</span>
        </button>
    </form>
</div>
@endsection
