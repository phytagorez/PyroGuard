<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sumber_air', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zona')->cascadeOnDelete();
            $table->string('nama');
            $table->enum('jenis', ['embung', 'sumber_air', 'pos_jaga', 'titik_kumpul'])->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('debit_air')->nullable(); // contoh: "Kapasitas 15.000 L", "50 L/menit"
            $table->string('status_siaga')->default('Tersedia'); // contoh: "Siaga & Terisi", "Aktif 24 Jam"
            $table->json('jalur_evakuasi_geojson')->nullable(); // GeoJSON LineString / MultiLineString jalur keluar aman
            $table->timestamps();

            $table->index(['zona_id', 'jenis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sumber_air');
    }
};
