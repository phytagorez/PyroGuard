@props([
    'zonas' => collect(),
    'zonaAwal' => null,
])

<div 
    x-data="{
        buka: false,
        tabAktif: 'ringkasan',
        zonaKode: '{{ $zonaAwal?->kode ?? 'BRM-04' }}',
        zonasData: {{ Js::from($zonas) }},
        chartInited: false,

        get zona() {
            return this.zonasData.find(z => z.kode === this.zonaKode) || this.zonasData[0];
        },

        get risiko() {
            return this.zona?.risiko || {};
        },

        pilihZona(kode) {
            this.zonaKode = kode;
            this.buka = true;
            this.chartInited = false;
            if (this.tabAktif === 'tren') {
                this.$nextTick(() => this.renderChart());
            }
        },

        gantiTab(tab) {
            this.tabAktif = tab;
            if (tab === 'tren') {
                this.$nextTick(() => this.renderChart());
            }
        },

        renderChart() {
            const ctx = document.getElementById('chartTrenCdd');
            if (!ctx) return;
            
            if (window.cddChartInstance) {
                window.cddChartInstance.destroy();
            }

            const cuaca = this.zona?.cuaca_harian || [];
            // Ambil urutan kronologis 30 hari
            const sorted = [...cuaca].sort((a,b) => new Date(a.tanggal) - new Date(b.tanggal));
            const labels = sorted.map(c => c.tanggal.slice(5)); // MM-DD
            
            // Hitung CDD berjalan untuk visualisasi tren
            let cddCumulative = 0;
            const dataCdd = sorted.map(c => {
                if (parseFloat(c.curah_hujan_mm) < 1.0) {
                    cddCumulative++;
                } else {
                    cddCumulative = 0;
                }
                return cddCumulative;
            });

            // Tambahkan proyeksi 3 hari
            const projLabels = [...labels, '+24j', '+48j', '+72j'];
            const curVal = this.risiko.cdd || 0;
            const projData = [
                ...Array(labels.length - 1).fill(null),
                curVal,
                this.risiko.proyeksi?.h24 ?? (curVal + 1),
                this.risiko.proyeksi?.h48 ?? (curVal + 2),
                this.risiko.proyeksi?.h72 ?? (curVal + 3),
            ];

            window.cddChartInstance = new window.Chart(ctx, {
                type: 'line',
                data: {
                    labels: projLabels,
                    datasets: [
                        {
                            label: 'CDD Aktual (Hari Kering)',
                            data: [...dataCdd, null, null, null],
                            borderColor: '#173D2C',
                            backgroundColor: 'rgba(23, 61, 44, 0.08)',
                            fill: true,
                            tension: 0.2,
                            borderWidth: 2,
                            pointRadius: 2,
                        },
                        {
                            label: 'Proyeksi +72 Jam',
                            data: projData,
                            borderColor: '#D9544A',
                            borderDash: [5, 5],
                            pointBorderColor: '#D9544A',
                            borderWidth: 2,
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'JetBrains Mono', size: 10 } } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Hari Kering (CDD)', font: { size: 11 } }
                        }
                    }
                }
            });
            this.chartInited = true;
        }
    }"
    @buka-panel-zona.window="pilihZona($event.detail.kode)"
    @keydown.escape.window="buka = false"
    class="contents"
>
    {{-- OVERLAY LATAR BELAKANG DI MOBILE --}}
    <div 
        x-show="buka"
        x-transition:enter="transition ease-out duration-base"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-fast"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="buka = false"
        class="fixed inset-0 bg-ink/40 z-50 lg:hidden"
        style="display: none;"
    ></div>

    {{-- CONTAINER PANEL (Mobile Sheet 85vh / Desktop Sticky Column) --}}
    <aside
        :class="buka ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
        class="fixed lg:static bottom-0 inset-x-0 z-50 lg:z-auto h-[85vh] lg:h-auto max-h-[85vh] lg:max-h-none bg-paper-2 border-t lg:border border-line rounded-t-3xl lg:rounded-2xl shadow-sheet lg:shadow-card flex flex-col transition-transform duration-base overflow-hidden"
    >
        {{-- PEGANGAN DRAG DI MOBILE (§6.3 design.md) --}}
        <div class="lg:hidden w-full flex justify-center pt-3 pb-1" @click="buka = false">
            <div class="w-10 h-1 bg-line rounded-full cursor-pointer"></div>
        </div>

        {{-- HEADER PANEL --}}
        <div class="p-4 sm:p-5 border-b border-line bg-paper flex items-center justify-between shrink-0">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-forest uppercase tracking-wider" x-text="zona?.kode"></span>
                    <span class="text-xs text-ink-muted">•</span>
                    <span class="text-xs text-ink-muted font-mono" x-text="zona?.jenis_lahan"></span>
                </div>
                <h3 class="text-lg sm:text-xl font-display font-bold text-ink mt-0.5" x-text="zona?.nama"></h3>
            </div>

            <div class="flex items-center gap-2">
                <template x-if="risiko?.kategori">
                    <x-komponen.lencana-risiko x-bind:status="risiko.kategori" varian="solid" ukuran="sm" />
                </template>
                <button 
                    type="button" 
                    @click="buka = false" 
                    class="lg:hidden p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-paper-2"
                    aria-label="Tutup panel zona"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- 4 TAB NAVIGASI (§6.3 design.md) --}}
        <div class="flex border-b border-line bg-paper text-xs font-mono shrink-0">
            <button 
                type="button"
                @click="gantiTab('ringkasan')"
                :class="tabAktif === 'ringkasan' ? 'border-b-2 border-forest font-bold text-forest bg-paper-2' : 'text-ink-muted hover:text-ink'"
                class="flex-1 py-3 text-center transition"
            >
                Ringkasan
            </button>
            <button 
                type="button"
                @click="gantiTab('mengapa')"
                :class="tabAktif === 'mengapa' ? 'border-b-2 border-forest font-bold text-forest bg-paper-2' : 'text-ink-muted hover:text-ink'"
                class="flex-1 py-3 text-center transition"
            >
                Mengapa
            </button>
            <button 
                type="button"
                @click="gantiTab('tren')"
                :class="tabAktif === 'tren' ? 'border-b-2 border-forest font-bold text-forest bg-paper-2' : 'text-ink-muted hover:text-ink'"
                class="flex-1 py-3 text-center transition"
            >
                Tren 30h
            </button>
            <button 
                type="button"
                @click="gantiTab('jam_rentan')"
                :class="tabAktif === 'jam_rentan' ? 'border-b-2 border-forest font-bold text-forest bg-paper-2' : 'text-ink-muted hover:text-ink'"
                class="flex-1 py-3 text-center transition"
            >
                Jam Rentan
            </button>
        </div>

        {{-- KONTEN PANEL SCROLLABLE --}}
        <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
            {{-- TAB 1: RINGKASAN --}}
            <div x-show="tabAktif === 'ringkasan'" class="space-y-4">
                {{-- Dua Metrik Besar: CDD & FDI --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3.5 bg-paper rounded-xl border border-line">
                        <span class="text-xs font-mono text-ink-muted uppercase block">Hari Tanpa Hujan</span>
                        <div class="flex items-baseline gap-1 mt-1">
                            <span class="text-3xl font-display font-bold text-ink tabular-nums" x-text="risiko?.cdd ?? 0"></span>
                            <span class="text-xs font-mono text-ink-muted">hari (aktual)</span>
                        </div>
                    </div>
                    <div class="p-3.5 bg-paper rounded-xl border border-line">
                        <span class="text-xs font-mono text-ink-muted uppercase block">Bahaya Api (FDI)</span>
                        <div class="flex items-baseline gap-1 mt-1">
                            <span class="text-3xl font-display font-bold text-danger tabular-nums" x-text="risiko?.fdi ?? 0"></span>
                            <span class="text-xs font-mono text-ink-muted">/100</span>
                        </div>
                    </div>
                </div>

                {{-- 3 Chip Proyeksi CDD (+24j, +48j, +72j) --}}
                <div class="bg-paper p-3 rounded-xl border border-line">
                    <span class="text-xs font-mono text-ink-muted uppercase block mb-2">Proyeksi Hari Kering ke Depan:</span>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs font-mono">
                        <div class="p-2 bg-paper-2 rounded-lg border border-line">
                            <span class="text-[10px] text-ink-muted block">+24 Jam</span>
                            <strong class="text-sm font-bold text-ink" x-text="(risiko?.proyeksi?.h24 ?? 0) + ' hari'"></strong>
                        </div>
                        <div class="p-2 bg-paper-2 rounded-lg border border-line">
                            <span class="text-[10px] text-ink-muted block">+48 Jam</span>
                            <strong class="text-sm font-bold text-ink" x-text="(risiko?.proyeksi?.h48 ?? 0) + ' hari'"></strong>
                        </div>
                        <div class="p-2 bg-paper-2 rounded-lg border border-line">
                            <span class="text-[10px] text-ink-muted block">+72 Jam</span>
                            <strong class="text-sm font-bold text-ink" x-text="(risiko?.proyeksi?.h72 ?? 0) + ' hari'"></strong>
                        </div>
                    </div>
                </div>

                {{-- FdiMeter Bilah Horizontal (§6.4) --}}
                <div class="bg-paper p-3.5 rounded-xl border border-line space-y-2">
                    <div class="flex justify-between items-center text-xs font-mono">
                        <span class="text-ink-muted uppercase font-semibold">Skala Bahaya Api</span>
                        <span class="font-bold tabular-nums" x-text="(risiko?.fdi ?? 0) + ' / 100'"></span>
                    </div>
                    <div class="w-full h-2.5 bg-paper-3 rounded-full overflow-hidden relative">
                        <div 
                            class="h-full rounded-full transition-all duration-slow"
                            :class="(risiko?.fdi || 0) > 80 ? 'bg-danger' : ((risiko?.fdi || 0) > 40 ? 'bg-warn' : 'bg-safe')"
                            :style="'width: ' + Math.min(100, Math.max(0, risiko?.fdi || 0)) + '%'"
                        ></div>
                    </div>
                    <div class="flex justify-between text-[10px] font-mono text-ink-muted pt-0.5">
                        <span>0 Aman</span>
                        <span class="border-l border-line pl-1">40 Waspada</span>
                        <span class="border-l border-line pl-1">80 Bahaya</span>
                        <span>100</span>
                    </div>
                </div>

                {{-- Parameter Cuaca Hari Ini --}}
                <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                    <div class="p-2.5 bg-paper rounded-lg border border-line flex items-center justify-between">
                        <span class="text-ink-muted">Kecepatan Angin:</span>
                        <strong class="text-ink" x-text="(risiko?.kecepatan_angin_kmj ?? 0) + ' km/j'"></strong>
                    </div>
                    <div class="p-2.5 bg-paper rounded-lg border border-line flex items-center justify-between">
                        <span class="text-ink-muted">Arah Dominan:</span>
                        <strong class="text-ink" x-text="risiko?.arah_angin ?? 'Tenggara'"></strong>
                    </div>
                </div>

                {{-- Jendela Jam Rentan --}}
                <div class="p-3 bg-amber-500/10 border border-amber-600/30 rounded-xl">
                    <div class="flex items-center gap-2 text-xs font-mono text-warn font-bold">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        <span>JENDELA JAM PALING RENTAN:</span>
                    </div>
                    <p class="text-sm font-display font-bold text-ink mt-1" x-text="risiko?.jam_rentan ?? '12:00 - 15:30 WIB'"></p>
                    <p class="text-[11px] text-ink-muted mt-0.5">Kelembapan udara berada pada titik terendah harian dan tiupan angin mencapai kecepatan maksimum.</p>
                </div>
            </div>

            {{-- TAB 2: MENGAPA (§6.5 FactorBars) --}}
            <div x-show="tabAktif === 'mengapa'" class="space-y-4" style="display: none;">
                <p class="text-xs text-ink-muted leading-relaxed">
                    Status risiko dihitung oleh mesin formula PyroGuard berdasarkan 3 faktor pemicu utama. Berikut rincian kontribusi poin pembentuk FDI:
                </p>

                {{-- Tiga Batang Kontribusi Faktor --}}
                <div class="space-y-3 bg-paper p-4 rounded-xl border border-line">
                    {{-- Faktor 1: CDD --}}
                    <div>
                        <div class="flex justify-between items-center text-xs font-mono mb-1">
                            <span class="font-semibold text-ink">1. Hari Tanpa Hujan (CDD)</span>
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 bg-forest text-white rounded text-[10px]">Penyumbang utama</span>
                                <strong class="text-forest tabular-nums" x-text="Math.round((risiko?.cdd || 0) * 2.85) + ' poin'"></strong>
                            </div>
                        </div>
                        <div class="w-full h-2 bg-paper-3 rounded-full overflow-hidden">
                            <div class="h-full bg-forest rounded-full" :style="'width: ' + Math.min(100, ((risiko?.cdd || 0) * 2.85 / (risiko?.fdi || 1)) * 100) + '%'"></div>
                        </div>
                    </div>

                    {{-- Faktor 2: Kelembapan --}}
                    <div>
                        <div class="flex justify-between items-center text-xs font-mono mb-1">
                            <span class="font-semibold text-ink">2. Defisit Kelembapan Udara (100 - RH)</span>
                            <strong class="text-forest tabular-nums">24 poin</strong>
                        </div>
                        <div class="w-full h-2 bg-paper-3 rounded-full overflow-hidden">
                            <div class="h-full bg-forest rounded-full" style="width: 28%;"></div>
                        </div>
                    </div>

                    {{-- Faktor 3: Angin --}}
                    <div>
                        <div class="flex justify-between items-center text-xs font-mono mb-1">
                            <span class="font-semibold text-ink">3. Kecepatan Tiupan Angin</span>
                            <strong class="text-forest tabular-nums">8 poin</strong>
                        </div>
                        <div class="w-full h-2 bg-paper-3 rounded-full overflow-hidden">
                            <div class="h-full bg-forest rounded-full" style="width: 10%;"></div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-paper rounded-xl border border-line text-xs space-y-2">
                    <p class="font-medium text-ink">Kalibrasi Bobot Kawasan Bromo:</p>
                    <code class="block p-2 bg-paper-2 rounded font-mono text-[11px] text-ink overflow-x-auto">
                        FDI = CDD×2.85 + (100−RH)×0.35 + Angin×0.30
                    </code>
                    <a href="{{ url('/metode') }}" class="inline-flex items-center gap-1 text-moss hover:underline font-semibold pt-1">
                        <span>Pelajari rumus & batas ambang selengkapnya</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- TAB 3: TREN 30 HARI (Chart.js) --}}
            <div x-show="tabAktif === 'tren'" class="space-y-3" style="display: none;">
                <div class="flex justify-between items-center text-xs font-mono">
                    <span class="text-ink-muted">Grafik Hari Kering (CDD) 30 Hari:</span>
                    <span class="text-[10px] text-ink-muted">Sumber: Open-Meteo</span>
                </div>
                <div class="bg-paper p-3 rounded-xl border border-line h-64 relative">
                    <canvas id="chartTrenCdd"></canvas>
                </div>
                <p class="text-[11px] font-mono text-ink-muted text-center">
                    Garis putus-putus merah menandakan proyeksi +24j s/d +72j ke depan.
                </p>
            </div>

            {{-- TAB 4: JAM RENTAN --}}
            <div x-show="tabAktif === 'jam_rentan'" class="space-y-3" style="display: none;">
                <p class="text-xs text-ink-muted">
                    Profil fluktuasi kelembapan dan tiupan angin harian di kawasan <strong x-text="zona?.nama"></strong>:
                </p>
                <div class="space-y-2 bg-paper p-3.5 rounded-xl border border-line text-xs font-mono">
                    <div class="flex justify-between py-1.5 border-b border-line">
                        <span class="text-ink-muted">06:00 - 09:00 WIB</span>
                        <span class="text-safe font-semibold">Aman (Kabut dingin, RH 85%)</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-line">
                        <span class="text-ink-muted">09:00 - 12:00 WIB</span>
                        <span class="text-warn font-semibold">Mulai Kering (Suhu naik, angin 15 km/j)</span>
                    </div>
                    <div class="flex justify-between py-2 bg-danger/10 px-2 rounded border border-danger/30">
                        <span class="font-bold text-danger">12:00 - 15:30 WIB</span>
                        <span class="font-bold text-danger">Puncak Bahaya (RH 31%, Angin 28 km/j)</span>
                    </div>
                    <div class="flex justify-between py-1.5 pt-2">
                        <span class="text-ink-muted">16:00 - 18:00 WIB</span>
                        <span class="text-safe font-semibold">Mereda (Suhu turun cepat)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOTER PANEL TETAP (STICKY ACTIONS) --}}
        <div class="p-3.5 sm:p-4 bg-paper border-t border-line grid grid-cols-2 gap-2 shrink-0">
            <a 
                :href="'{{ url('/lapor') }}?zona=' + zona?.kode"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 bg-forest text-white rounded-xl text-xs font-semibold hover:bg-forest/90 transition text-center"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Lapor di zona ini</span>
            </a>
            <a 
                :href="'{{ url('/evakuasi') }}?zona=' + zona?.kode"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 bg-paper-2 border border-line text-ink rounded-xl text-xs font-semibold hover:bg-paper-3 transition text-center"
            >
                <svg class="w-4 h-4 shrink-0 text-evac" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span>Jalur evakuasi</span>
            </a>
        </div>
    </aside>
</div>
