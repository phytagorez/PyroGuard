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
        Schema::create('status_siaga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zona_id')->constrained('zona')->cascadeOnDelete();
            $table->enum('status', ['NORMAL', 'SIAGA_OTOMATIS', 'SIAGA_1', 'DICABUT'])->index();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->text('catatan_persetujuan')->nullable();
            $table->timestamp('disetujui_pada')->nullable();
            $table->foreignId('dicabut_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('dicabut_pada')->nullable();
            $table->enum('sumber', ['aktual', 'demo'])->default('aktual');
            $table->timestamps();

            $table->index(['zona_id', 'status', 'sumber']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_siaga');
    }
};
