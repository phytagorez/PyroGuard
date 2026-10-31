<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PyroGuard — Sistem Peringatan Dini Kebakaran Lahan Bromo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink min-h-screen flex flex-col items-center justify-center p-6 selection:bg-moss selection:text-white">
    <div class="max-w-xl w-full bg-paper-2 border border-line rounded-2xl p-8 shadow-card text-center space-y-6">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-forest text-white rounded-full text-xs font-mono uppercase tracking-wider">
            <span class="w-2 h-2 rounded-full bg-safe"></span>
            Tahap 1 Pondasi Aktif · Bromo
        </div>

        <h1 class="text-3xl font-display font-bold text-forest tracking-tight">
            PyroGuard Bromo
        </h1>

        <p class="text-ink-muted text-sm leading-relaxed">
            Sistem Peringatan Dini Kebakaran Lahan Kawasan Bromo Tengger Semeru berbasis mesin kalkulasi risiko (CDD & FDI), verifikasi laporan lapangan, dan peringatan dini terintegrasi.
        </p>

        <div class="grid grid-cols-2 gap-3 pt-2 text-left text-xs font-mono">
            <div class="p-3 bg-paper rounded-xl border border-line">
                <span class="text-ink-muted block mb-1">Zona Terpantau</span>
                <span class="text-base font-bold text-ink">6 Wilayah</span>
            </div>
            <div class="p-3 bg-paper rounded-xl border border-line">
                <span class="text-ink-muted block mb-1">Zona Siaga</span>
                <span class="text-base font-bold text-danger">BRM-04 (Savana)</span>
            </div>
        </div>
    </div>
</body>
</html>
