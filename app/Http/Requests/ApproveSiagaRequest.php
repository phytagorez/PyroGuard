<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveSiagaRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi untuk persetujuan siaga.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tindakan' => ['required', 'in:setujui,cabut'],
            'catatan' => ['nullable', 'string', 'max:500'],
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
            'tindakan.required' => 'Pilih tindakan yang akan diambil (setujui atau cabut).',
            'tindakan.in' => 'Tindakan harus berupa setujui (Siaga 1) atau cabut.',
        ];
    }
}
