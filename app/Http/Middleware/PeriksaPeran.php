<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware Pemeriksaan Peran Pengguna (RBAC) PyroGuard (§7.9 design.md)
 * Membatasi akses rute khusus petugas TNBTS dan administrator BPBD.
 */
class PeriksaPeran
{
    /**
     * Tangani permintaan masuk.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$perans): Response
    {
        // 1. Jika belum masuk, arahkan ke halaman masuk dengan pesan
        if (! auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan masuk untuk mengakses halaman ini.',
                ], 401);
            }

            return redirect()->guest(route('masuk'))
                ->with('peringatan', 'Silakan masuk dengan akun petugas atau admin untuk melanjutkan.');
        }

        $user = auth()->user();

        // 2. Cek apakah peran pengguna sesuai dengan yang diizinkan
        if (! in_array($user->peran, $perans, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Halaman ini hanya untuk petugas. Anda masih bisa melihat status kawasan.',
                ], 403);
            }

            // Tampilkan halaman 403 ramah sesuai §7.9 & §9
            return response()->view('errors.403', [
                'pesan' => 'Halaman ini hanya untuk petugas. Anda masih bisa melihat status kawasan.',
            ], 403);
        }

        return $next($request);
    }
}
