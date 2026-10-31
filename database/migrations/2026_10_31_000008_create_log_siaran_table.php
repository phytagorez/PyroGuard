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
        Schema::create('log_siaran', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis_siaran', ['waspada', 'siaga_1', 'titik_api', 'pencabutan', 'tiket_baru'])->index();
            $table->foreignId('zona_id')->nullable()->constrained('zona')->nullOnDelete();
            $table->enum('kanal', ['web_push', 'telegram_publik', 'telegram_privat', 'grup_petugas'])->index();
            $table->string('judul_pesan');
            $table->text('isi_pesan');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->boolean('adalah_simulasi')->default(false);
            $table->timestamp('dikirim_pada');
            $table->timestamps();

            $table->index(['kanal', 'dikirim_pada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_siaran');
    }
};
