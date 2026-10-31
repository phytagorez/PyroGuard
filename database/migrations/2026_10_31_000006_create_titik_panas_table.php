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
        Schema::create('titik_panas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->nullable()->constrained('zona')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('kecerahan', 7, 2); // Kecerahan / Brightness Kelvin dari satelit
            $table->string('sumber_satelit', 32)->default('FIRMS'); // NASA FIRMS (VIIRS/MODIS)
            $table->timestamp('terdeteksi_pada');
            $table->enum('sumber', ['aktual', 'demo'])->default('aktual');
            $table->timestamps();

            $table->index(['zona_id', 'terdeteksi_pada', 'sumber']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titik_panas');
    }
};
