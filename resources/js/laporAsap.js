export default function laporAsap(config) {
    let mapInstance;
    let markerInstance;
    return {
        sukses: config.sukses,
        tiket: config.tiket,
        zonas: config.zonas,

        // Form state
        lat: config.lat,
        lng: config.lng,
        akurasi: null,
        lokasiNama: config.lokasiNama,
        zonaId: config.zonaId,
        zonaKode: config.zonaKode,
        jenisTemuan: 'asap_tipis',
        catatan: '',

        // Peta & GPS


        gpsLoading: false,
        gpsError: null,

        // Foto state
        fotoPreviews: [],

        // Rate limiting state
        sisaKuota: config.sisaKuota,
        terkunci: config.terkunci,
        detikTunggu: config.detikTunggu,

        // Timer lapor lagi (60s)
        detikLaporLagi: 60,
        timerLaporLagi: null,

        init() {
            if (this.terkunci && this.detikTunggu > 0) {
                this.mulaiCountdownBatasLaju();
            }

            if (this.sukses) {
                this.mulaiCountdownLaporLagi();
            } else {
                this.$nextTick(() => {
                    this.inisialisasiPetaMini();
                });
            }
        },

        inisialisasiPetaMini() {
            if (!window.L || this.sukses) return;

            const map = window.L.map('petaMiniLapor', {
                center: [this.lat, this.lng],
                zoom: 13,
                zoomControl: false,
                attributionControl: true,
            });

            mapInstance = map;
            window.L.control.zoom({ position: 'topright' }).addTo(map);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);

            // Icon pin merah
            const pinIcon = window.L.divIcon({
                className: 'custom-pin',
                html: `
                    <div class='flex items-center justify-center w-8 h-8'>
                        <div class='w-5 h-5 rounded-full bg-ember border-2 border-white shadow-none flex items-center justify-center text-white text-xs'>

                        </div>
                    </div>
                `,
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });

            const marker = window.L.marker([this.lat, this.lng], {
                icon: pinIcon,
                draggable: true,
            }).addTo(map);

            markerInstance = marker;

            marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                this.lat = parseFloat(pos.lat.toFixed(7));
                this.lng = parseFloat(pos.lng.toFixed(7));
                this.akurasi = null;
                this.hitungZonaTerdekat();
            });

            this.hitungZonaTerdekat();

            map.on('click', (e) => {
                this.lat = parseFloat(e.latlng.lat.toFixed(7));
                this.lng = parseFloat(e.latlng.lng.toFixed(7));
                markerInstance.setLatLng(e.latlng);
                this.akurasi = null;
                this.hitungZonaTerdekat();
            });
        },

        ambilGpsOtomatis() {
            if (!navigator.geolocation) {
                this.gpsError = 'Perangkat tidak mendukung GPS. Silakan geser pin manual pada peta.';
                return;
            }

            this.gpsLoading = true;
            this.gpsError = null;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = parseFloat(pos.coords.latitude.toFixed(7));
                    this.lng = parseFloat(pos.coords.longitude.toFixed(7));
                    this.akurasi = Math.round(pos.coords.accuracy);
                    this.gpsLoading = false;

                    if (markerInstance && mapInstance) {
                        markerInstance.setLatLng([this.lat, this.lng]);
                        mapInstance.panTo([this.lat, this.lng]);
                    }

                    this.hitungZonaTerdekat();
                },
                (err) => {
                    this.gpsLoading = false;
                    this.gpsError = 'Izin lokasi tidak diaktifkan. Anda dapat menggeser pin merah di peta untuk menentukan titik temuan.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        hitungZonaTerdekat() {
            let jarakMin = Infinity;
            let terpilih = this.zonas[0];

            this.zonas.forEach(z => {
                const dLat = z.titik_tengah_lat - this.lat;
                const dLng = z.titik_tengah_lng - this.lng;
                const d = (dLat * dLat) + (dLng * dLng);
                if (d < jarakMin) {
                    jarakMin = d;
                    terpilih = z;
                }
            });

            if (!terpilih) { return; }
            this.zonaId = terpilih.id;
            this.zonaKode = terpilih.kode;
            this.lokasiNama = terpilih.nama;
        },

        handleFoto(event) {
            const files = Array.from(event.target.files).slice(0, 2);
            this.fotoPreviews = [];

            files.forEach((file, idx) => {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Foto ' + file.name + ' melebihi ukuran maksimal 5 MB.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const now = new Date();
                    const jam = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    this.fotoPreviews.push({
                        url: e.target.result,
                        nama: file.name,
                        waktu: jam + ' WIB',
                        pos: this.lat.toFixed(4) + ', ' + this.lng.toFixed(4),
                    });
                };
                reader.readAsDataURL(file);
            });
        },

        hapusFoto(idx) {
            this.fotoPreviews.splice(idx, 1);
            if (idx === 0) {
                const f1 = document.getElementById('inputFoto1');
                if (f1) f1.value = '';
            } else if (idx === 1) {
                const f2 = document.getElementById('inputFoto2');
                if (f2) f2.value = '';
            }
        },

        mulaiCountdownBatasLaju() {
            const interval = setInterval(() => {
                if (this.detikTunggu <= 1) {
                    clearInterval(interval);
                    this.terkunci = false;
                    this.sisaKuota = 3;
                    this.detikTunggu = 0;
                } else {
                    this.detikTunggu--;
                }
            }, 1000);
        },

        mulaiCountdownLaporLagi() {
            this.detikLaporLagi = 60;
            this.timerLaporLagi = setInterval(() => {
                if (this.detikLaporLagi <= 1) {
                    clearInterval(this.timerLaporLagi);
                    this.detikLaporLagi = 0;
                } else {
                    this.detikLaporLagi--;
                }
            }, 1000);
        },

        formatWaktu(sec) {
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        salinNomorTiket(nomor) {
            navigator.clipboard.writeText(nomor);
            alert('Nomor tiket ' + nomor + ' berhasil disalin ke clipboard.');
        },

        destroy() { clearInterval(this.timerLaporLagi); mapInstance?.remove(); },

        resetForm() {
            this.sukses = false;
            this.tiket = null;
            this.fotoPreviews = [];
            this.catatan = '';
            this.$nextTick(() => {
                this.inisialisasiPetaMini();
            });
        }
    };
}
