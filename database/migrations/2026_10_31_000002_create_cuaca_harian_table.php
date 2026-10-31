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
        Schema::create('cuaca_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zona')->cascadeOnDelete();
            $table->date('tanggal')->index();
            $table->decimal('curah_hujan_mm', 6, 2)->default(0);
            $table->decimal('kelembapan_min', 5, 2); // RH min %
            $table->decimal('kecepatan_angin_maks', 6, 2); // km/j
            $table->string('arah_angin', 32); // contoh: Tenggara, Barat Daya
            $table->decimal('suhu_maks', 5, 2)->nullable(); // °C
            $table->enum('status_data', ['aktual', 'prakiraan'])->default('aktual');
            $table->enum('sumber', ['aktual', 'demo'])->default('aktual');
            $table->timestamps();

            $table->index(['zona_id', 'tanggal', 'sumber']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuaca_harian');
    }
};
