export default function petugasTiket(config) {
    return {
        tabMobile: config.tabMobile,
        tiketAktifId: config.tiketAktifId,
        tiketAktifNomor: config.tiketAktifNomor,
        tiketAktifLat: config.tiketAktifLat,
        tiketAktifLng: config.tiketAktifLng,
        hasilDipilih: 'api_nyata',
        dialogGandaBuka: false,
        tiketIndukId: '',
        catatanVerifikasi: '',

        init() {
            if (this.tiketAktifLat && this.tiketAktifLng) {
                this.$nextTick(() => {
                    this.initPetaMini();
                });
            }
        },

        initPetaMini() {
            if (!window.L || !document.getElementById('petaMiniPetugas')) return;
            const map = window.L.map('petaMiniPetugas', {
                center: [this.tiketAktifLat, this.tiketAktifLng],
                zoom: 14,
                zoomControl: false,
                attributionControl: true,
            });
            window.L.control.zoom({ position: 'topright' }).addTo(map);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);
            const pinIcon = window.L.divIcon({
                className: 'custom-pin-petugas',
                html: `<div class='w-7 h-7 rounded-full bg-danger border-2 border-white shadow-none flex items-center justify-center text-white text-xs font-bold'></div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });
            window.L.marker([this.tiketAktifLat, this.tiketAktifLng], { icon: pinIcon }).addTo(map);
        },

        konfirmasiVerifikasi(hasil, label, konsekuensi, warna) {
            this.hasilDipilih = hasil;

            if (hasil === 'ganda') {
                this.dialogGandaBuka = true;
                return;
            }

            if (window.Swal) {
                window.Swal.fire({
                    title: 'Verifikasi: ' + label + '?',
                    html: '<div class=\'text-xs font-sans text-left p-3 bg-paper rounded-md border border-line\'>' +
                          '<strong class=\'text-ink block mb-1 font-bold\'>Konsekuensi Keputusan:</strong>' +
                          '<p class=\'text-ink-muted leading-relaxed\'>' + konsekuensi + '</p>' +
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
    };
}
