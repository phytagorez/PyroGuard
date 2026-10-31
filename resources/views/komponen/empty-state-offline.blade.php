{{-- Komponen EmptyStateOffline (§6.15 design.md) --}}
{{-- Ditampilkan secara reaktif ketika perangkat pengguna terputus dari jaringan seluler di kawasan Bromo --}}
<div 
    x-data="{
        isOffline: !navigator.onLine,
        waktuSinkronTerakhir: localStorage.getItem('pyroguard_last_sync') || '{{ now()->format('H:i') }} WIB',
        zonaTertinggiTersimpan: JSON.parse(localStorage.getItem('pyroguard_last_highest_zone') || 'null'),

        init() {
            window.addEventListener('offline', () => {
                this.isOffline = true;
            });
            window.addEventListener('online', () => {
                this.isOffline = false;
            });

            // Simpan snapshot status zona tertinggi saat online
            if (navigator.onLine) {
                const el = document.getElementById('kode-zona-tertinggi');
                if (el) {
                    const statusSnapshot = {
                        kode: el.dataset.kode || 'BRM-04',
                        nama: el.dataset.nama || 'Savana & Jemplang',
                        kategori: el.dataset.kategori || 'BAHAYA',
                        cdd: el.dataset.cdd || '19',
                        fdi: el.dataset.fdi || '86',
                    };
                    localStorage.setItem('pyroguard_last_highest_zone', JSON.stringify(statusSnapshot));
                    localStorage.setItem('pyroguard_last_sync', '{{ now()->format('H:i') }} WIB');
                }
            }
        },

        cobaLagi() {
            window.location.reload();
        }
    }"
    x-show="isOffline"
    x-cloak
    class="fixed inset-x-0 bottom-16 lg:bottom-4 z-50 p-4 max-w-xl mx-auto"
>
    <div class="p-4 sm:p-5 bg-paper-2 border-2 border-amber-600/60 rounded-3xl shadow-sheet space-y-3.5 backdrop-blur-md">
        <div class="flex items-start gap-3.5">
            {{-- Ikon Garis Sinyal Terputus --}}
            <div class="w-10 h-10 rounded-2xl bg-amber-500/15 text-amber-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-12.728 0m0 0l2.829-2.829m-2.829 2.829L3 21m2.828-12.536a5 5 0 017.072 0m-7.072 0L3 3m18 0l-3 3" />
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-display font-bold text-ink">Anda sedang luring</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500/20 text-amber-800">
                        OFFLINE
                    </span>
                </div>
                <p class="text-xs text-ink-muted mt-0.5 leading-relaxed">
                    Koneksi sinyal di kawasan Bromo terputus. Menampilkan status terakhir yang tersimpan pada <span class="font-mono font-semibold text-ink" x-text="waktuSinkronTerakhir"></span>.
                </p>

                {{-- Status Zona Terakhir dari Cache --}}
                <template x-if="zonaTertinggiTersimpan">
                    <div class="mt-2.5 p-2.5 bg-paper rounded-xl border border-line text-xs font-mono flex items-center justify-between">
                        <span class="text-ink-muted truncate max-w-[150px]" x-text="zonaTertinggiTersimpan.nama"></span>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-ink" x-text="zonaTertinggiTersimpan.cdd + ' Hari CDD'"></span>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white"
                                :class="{
                                    'bg-danger': zonaTertinggiTersimpan.kategori === 'BAHAYA',
                                    'bg-warn': zonaTertinggiTersimpan.kategori === 'WASPADA',
                                    'bg-safe': zonaTertinggiTersimpan.kategori === 'AMAN'
                                }"
                                x-text="zonaTertinggiTersimpan.kategori"
                            ></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Dua Tombol Aksi: Telepon 112 & Coba Lagi --}}
        <div class="flex items-center gap-2 pt-1 border-t border-line/60">
            <a 
                href="tel:112" 
                class="flex-1 min-h-[44px] bg-ember hover:bg-ember/90 text-white rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition active:scale-98"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span>Panggilan Darurat 112</span>
            </a>

            <button 
                type="button" 
                @click="cobaLagi()" 
                class="min-h-[44px] px-4 bg-paper hover:bg-paper-3 border border-line text-ink rounded-xl font-display font-semibold text-xs flex items-center justify-center gap-1.5 transition active:scale-98"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Coba Lagi</span>
            </button>
        </div>
    </div>
</div>
