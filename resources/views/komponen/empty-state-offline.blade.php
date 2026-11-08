<div x-data="{ isOffline: !navigator.onLine }" @offline.window="isOffline = true" @online.window="isOffline = false" x-show="isOffline" x-cloak class="offline-panel" role="status">
    <strong>Anda sedang luring</strong>
    <p>Peta dasar memerlukan koneksi. Informasi tersimpan bisa kedaluwarsa; waktu observasi terbaru belum dapat diperiksa.</p>
    <div><a href="tel:112">Panggilan Darurat 112</a><button type="button" @click="window.location.reload()">Coba Lagi</button></div>
</div>
