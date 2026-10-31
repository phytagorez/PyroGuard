<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyLaporanAsapRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi untuk verifikasi laporan.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hasil_verifikasi' => ['required', 'in:api_nyata,bukan_api,ganda'],
            'tiket_induk_id' => ['nullable', 'required_if:hasil_verifikasi,ganda', 'exists:laporan_asap,id'],
            'foto_verifikasi' => ['nullable'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Pesan kesalahan khusus bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hasil_verifikasi.required' => 'Hasil verifikasi wajib dipilih (api_nyata, bukan_api, atau ganda).',
            'hasil_verifikasi.in' => 'Hasil verifikasi harus salah satu dari: api_nyata, bukan_api, atau ganda.',
            'tiket_induk_id.required_if' => 'Tiket induk wajib dipilih jika laporan ditandai sebagai laporan ganda/duplikat.',
        ];
    }
}
