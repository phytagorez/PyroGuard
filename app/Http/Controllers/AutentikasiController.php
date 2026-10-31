<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AutentikasiController extends Controller
{
    /**
     * Tampilkan halaman masuk (§7.9 design.md).
     */
    public function tampilMasuk(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isPetugas()) {
                return redirect()->intended('/petugas');
            }
            if ($user->isAdmin()) {
                return redirect()->intended('/admin');
            }

            return redirect('/');
        }

        return view('autentikasi.masuk');
    }

    /**
     * Proses autentikasi masuk (email atau nomor telepon + kata sandi).
     */
    public function masuk(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email atau nomor telepon wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'nomor_telepon';

        $ingat = $request->boolean('ingat');

        if (Auth::attempt([$loginField => $credentials['login'], 'password' => $credentials['password']], $ingat)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->isPetugas()) {
                return redirect()->intended('/petugas');
            }

            if ($user->isAdmin()) {
                return redirect()->intended('/admin');
            }

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'login' => 'Email / nomor telepon atau kata sandi tidak cocok dengan data kami.',
        ])->onlyInput('login');
    }

    /**
     * Tampilkan formulir pendaftaran calon petugas lapangan TNBTS (§7.9).
     */
    public function tampilDaftarPetugas(): View
    {
        return view('autentikasi.daftar-petugas');
    }

    /**
     * Proses pendaftaran petugas baru.
     */
    public function daftarPetugas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:pengguna,email'],
            'nomor_telepon' => ['required', 'string', 'max:20', 'unique:pengguna,nomor_telepon'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email dinas/pribadi wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar di PyroGuard.',
            'nomor_telepon.required' => 'Nomor WhatsApp/telepon darurat wajib diisi.',
            'nomor_telepon.unique' => 'Nomor telepon sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $petugas = Pengguna::create([
            'nama' => $validated['nama'],
            'email' => strtolower($validated['email']),
            'nomor_telepon' => $validated['nomor_telepon'],
            'password' => Hash::make($validated['password']),
            'peran' => 'petugas',
            'skor_kepercayaan' => 100,
        ]);

        Auth::login($petugas);

        return redirect('/petugas')->with('pesan', 'Pendaftaran berhasil. Selamat bertugas di kawasan Bromo!');
    }

    /**
     * Proses keluar sistem.
     */
    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('pesan', 'Anda telah berhasil keluar dari akun.');
    }
}
