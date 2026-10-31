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
        Schema::create('prakiraan_risiko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zona')->cascadeOnDelete();
            $table->unsignedInteger('hari_tanpa_hujan'); // CDD
            $table->unsignedInteger('proyeksi_cdd_24j');
            $table->unsignedInteger('proyeksi_cdd_48j');
            $table->unsignedInteger('proyeksi_cdd_72j');
            $table->unsignedSmallInteger('indeks_bahaya_api'); // FDI (0-100)
            $table->enum('kategori_risiko', ['AMAN', 'WASPADA', 'BAHAYA'])->index();
            $table->decimal('poin_cdd', 6, 2);
            $table->decimal('poin_kelembapan', 6, 2);
            $table->decimal('poin_angin', 6, 2);
            $table->string('jam_rentan', 64); // Contoh: "12:00 - 15:30 WIB"
            $table->decimal('kecepatan_angin_kmj', 6, 2);
            $table->string('arah_angin', 32);
            $table->enum('status_siaga', ['NORMAL', 'SIAGA_OTOMATIS', 'SIAGA_1', 'DICABUT'])->default('NORMAL')->index();
            $table->enum('status_data', ['aktual', 'prakiraan'])->default('aktual');
            $table->enum('sumber', ['aktual', 'demo'])->default('aktual');
            $table->timestamp('dihitung_pada');
            $table->timestamps();

            $table->index(['zona_id', 'sumber', 'dihitung_pada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prakiraan_risiko');
    }
};
