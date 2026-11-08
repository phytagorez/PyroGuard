@php
    $menuPublik = [
        ['pantauan', 'Pantauan peta', '01'],
        ['evakuasi', 'Logistik & akses', '02'],
        ['lapor.index', 'Lapor asap', '03'],
        ['notifikasi', 'Kanal peringatan', '04'],
        ['metode', 'Metode & dataset', '05'],
    ];
    $menuAdmin = [
        ['admin.tinjauan', 'Tinjauan risiko', '01'],
        ['admin.siaga', 'Persetujuan siaga', '02'],
        ['admin.laporan', 'Laporan warga', '03'],
        ['admin.siaran', 'Tulis siaran', '04'],
        ['admin.log-siaran', 'Riwayat siaran', '05'],
        ['admin.dataset', 'Dataset & integrasi', '06'],
    ];
@endphp
<nav class="workspace-nav" aria-label="{{ $areaAdmin ? 'Menu otoritas' : 'Menu kawasan' }}">
    <span class="nav-caption">{{ $areaAdmin ? 'Koordinasi' : 'Eksplorasi' }}</span>
    @foreach($areaAdmin ? $menuAdmin : $menuPublik as [$namaRute, $label, $nomor])
        <a href="{{ route($namaRute) }}" @if(request()->routeIs($namaRute)) aria-current="page" @endif><span>{{ $label }}</span><span class="nav-arrow" aria-hidden="true">↗</span></a>
    @endforeach
    @auth
        @if(in_array(auth()->user()->peran, ['petugas', 'admin']))
            <span class="nav-caption">Operasional</span>
            <a href="{{ route('petugas.index') }}" @if(request()->routeIs('petugas.*')) aria-current="page" @endif>Tugas lapangan <span class="nav-arrow" aria-hidden="true">↗</span></a>
        @endif
        @if(auth()->user()->peran === 'admin')
            <a href="{{ route($areaAdmin ? 'pantauan' : 'admin.tinjauan') }}">{{ $areaAdmin ? 'Pantauan publik' : 'Ruang otoritas' }} <span aria-hidden="true">↗</span></a>
        @endif
    @endauth
</nav>
