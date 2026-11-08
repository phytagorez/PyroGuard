import './bootstrap';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'preline';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import pantauanPeta from './pantauan';
import laporAsap from './laporAsap';
import petugasTiket from './petugasTiket';

window.Alpine = Alpine;
window.Swal = Swal;
window.Chart = Chart;
window.L = L;
window.pantauanPeta = pantauanPeta;
window.laporAsap = laporAsap;
window.petugasTiket = petugasTiket;

// Auto-init Preline UI on load and Alpine page updates
document.addEventListener('DOMContentLoaded', () => {
    if (window.HSStaticMethods) {
        window.HSStaticMethods.autoInit();
    }

    // Inisialisasi Mode Terang Tinggi dari localStorage
    if (localStorage.getItem('pyroguard_mode_terang_tinggi') === 'true') {
        document.documentElement.classList.add('mode-terang-tinggi');
    }
});

window.toggleModeTerangTinggi = function () {
    const isHigh = document.documentElement.classList.toggle('mode-terang-tinggi');
    localStorage.setItem('pyroguard_mode_terang_tinggi', isHigh ? 'true' : 'false');
    return isHigh;
};

// Registrasi Progressive Web App (PWA) Service Worker (Â§12 & Â§18)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            // Silently fallback if service worker cannot be registered in dev
            console.log('SW registration note:', err.message);
        });
    });
}

Alpine.start();
