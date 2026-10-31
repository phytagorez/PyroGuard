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
        Schema::create('zona', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 16)->unique(); // BRM-01 .. BRM-06
            $table->string('nama');
            $table->text('deskripsi_lokasi')->nullable();
            $table->string('jenis_lahan')->nullable(); // contoh: padang rumput, semak kering
            $table->string('akses_wisata')->nullable(); // tinggi, sedang, tertutup
            $table->json('poligon_geojson'); // GeoJSON Polygon
            $table->decimal('titik_tengah_lat', 10, 7);
            $table->decimal('titik_tengah_lng', 10, 7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zona');
    }
};
