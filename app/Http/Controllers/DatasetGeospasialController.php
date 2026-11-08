<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDatasetGeospasialRequest;
use App\Services\LayananDatasetGeospasial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DatasetGeospasialController extends Controller
{
    public function index(LayananDatasetGeospasial $dataset): View
    {
        return view('admin.dataset', ['metadata' => $dataset->metadata(), 'dataset' => $dataset->tersimpan()]);
    }

    public function store(ImportDatasetGeospasialRequest $request, LayananDatasetGeospasial $dataset): RedirectResponse
    {
        $dataset->impor($request->file('dataset_csv'), $request->file('wilayah_geojson'));

        return to_route('admin.dataset')->with('pesan', 'Dataset berhasil diimpor. Hasil historis dan wilayah analisis tersedia di peta publik.');
    }
}
