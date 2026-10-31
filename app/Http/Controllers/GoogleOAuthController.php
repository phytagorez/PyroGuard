<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleOAuthController extends Controller
{
    /**
     * Arahkan pengguna ke halaman otorisasi Google OAuth.
     */
    public function redirect(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        // Fallback ramah jika kredensial .env belum diisi / masih placeholder
        if (empty($clientId) || empty($clientSecret) || str_contains($clientId, '[MASUKKAN')) {
            return redirect()->route('masuk')->with(
                'peringatan_google',
                'Integrasi Google OAuth memerlukan Client ID & Secret di file .env. Silakan masuk menggunakan form email atau gunakan akun demo petugas.'
            );
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (Throwable $e) {
            return redirect()->route('masuk')->with(
                'peringatan_google',
                'Gagal menginisialisasi Google OAuth: '.$e->getMessage().'. Silakan masuk dengan kata sandi.'
            );
        }
    }

    /**
     * Tangani callback data pengguna setelah otorisasi Google.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('masuk')->with(
                'peringatan_google',
                'Otorisasi Google dibatalkan atau gagal: '.$e->getMessage()
            );
        }

        $email = strtolower($googleUser->getEmail());

        // Cari pengguna berdasarkan google_id atau email
        $user = Pengguna::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            // Tautkan google_id jika sebelumnya daftar dengan email manual
            if (empty($user->google_id)) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            // Buat pengguna baru jika belum ada
            $user = Pengguna::create([
                'nama' => $googleUser->getName() ?: 'Pengguna Google',
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'password' => Hash::make(Str::random(32)),
                'peran' => 'publik',
                'skor_kepercayaan' => 50,
            ]);
        }

        Auth::login($user, true);

        if ($user->isPetugas()) {
            return redirect()->intended('/petugas');
        }

        if ($user->isAdmin()) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/')->with('pesan', 'Berhasil masuk dengan akun Google: '.$user->nama);
    }
}
