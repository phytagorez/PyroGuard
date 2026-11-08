<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MetodeController extends Controller
{
    /**
     * Tampilkan halaman Metode & Keterbatasan Formula PyroGuard (Â§7.6 design.md).
     */
    public function __invoke(): View
    {
        return view('metode.index');
    }
}
