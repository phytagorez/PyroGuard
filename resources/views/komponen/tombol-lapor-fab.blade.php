<div class="fixed bottom-20 inset-x-4 z-40 lg:hidden flex justify-center pointer-events-none">
    <a
        href="{{ url('/lapor') }}"
        class="pointer-events-auto flex items-center justify-center gap-3 w-full max-w-sm h-14 bg-ember text-white rounded-full shadow-sheet font-display font-bold text-base tracking-wide hover:brightness-105 active:scale-98 transition duration-fast"
        style="padding-bottom: env(safe-area-inset-bottom, 0px);"
        aria-label="Laporkan asap atau titik api kebakaran lahan di kawasan Bromo"
    >
        {{-- Ikon Asap Api --}}
        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
        </svg>
        <span>Lapor asap</span>
    </a>
</div>
