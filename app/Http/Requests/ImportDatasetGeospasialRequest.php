<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDatasetGeospasialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->peran === 'admin';
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'dataset_csv' => ['nullable', 'required_without:wilayah_geojson', 'file', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel', 'max:2048'],
            'wilayah_geojson' => ['nullable', 'required_without:dataset_csv', 'file', 'mimetypes:application/json,application/geo+json,text/plain', 'max:15360'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required_without' => 'Pilih minimal satu file: CSV hasil GEE atau GeoJSON wilayah analisis.',
            '*.mimetypes' => 'Jenis file tidak sesuai. Gunakan CSV atau GeoJSON hasil ekspor GEE.',
            'dataset_csv.max' => 'CSV maksimal 2 MB.',
            'wilayah_geojson.max' => 'GeoJSON maksimal 15 MB.',
        ];
    }
}
