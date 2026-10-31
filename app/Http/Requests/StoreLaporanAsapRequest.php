<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLaporanAsapRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true; // Publik dapat melapor sebagai tamu tanpa login (§6.8 design.md)
    }

    /**
     * Dapatkan aturan validasi untuk permintaan ini.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'akurasi_gps' => ['nullable', 'numeric'],
            'jenis_temuan' => ['required', 'in:asap_tipis,asap_tebal,api_terlihat,bekas_terbakar'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'zona_id' => ['nullable', 'integer', 'exists:zona,id'],
            'foto_1' => ['nullable'],
            'foto_2' => ['nullable'],
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
            'latitude.required' => 'Koordinat latitude wajib disertakan dari GPS atau peta.',
            'longitude.required' => 'Koordinat longitude wajib disertakan dari GPS atau peta.',
            'jenis_temuan.required' => 'Pilih salah satu jenis temuan asap atau api.',
            'jenis_temuan.in' => 'Pilihan temuan harus berupa: asap_tipis, asap_tebal, api_terlihat, atau bekas_terbakar.',
        ];
    }
}
