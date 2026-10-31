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
        Schema::create('laporan_asap', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tiket', 32)->unique(); // Contoh: #LP-0231
            $table->foreignId('zona_id')->constrained('zona')->cascadeOnDelete();
            $table->foreignId('pelapor_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->string('ip_pelapor', 45)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('akurasi_gps', 8, 2)->nullable();
            $table->enum('jenis_temuan', ['asap_tipis', 'asap_tebal', 'api_terlihat', 'bekas_terbakar']);
            $table->string('foto_1')->nullable();
            $table->string('foto_2')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status_tiket', ['baru', 'ditugaskan', 'menuju_lokasi', 'terverifikasi', 'ditangani', 'selesai'])->default('baru')->index();
            $table->enum('hasil_verifikasi', ['api_nyata', 'bukan_api', 'ganda'])->nullable()->index();
            $table->foreignId('tiket_induk_id')->nullable()->constrained('laporan_asap')->nullOnDelete();
            $table->string('foto_verifikasi')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->enum('sumber', ['aktual', 'demo'])->default('aktual');
            $table->timestamps();

            $table->index(['zona_id', 'status_tiket', 'sumber']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_asap');
    }
};
