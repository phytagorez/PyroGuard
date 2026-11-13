<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah jejak asal data pada titik logistik. Hanya menambah kolom,
 * tidak mengubah atau menghapus data yang sudah ada.
 * Baris tanpa sumber_data dianggap "data contoh".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sumber_air', function (Blueprint $table) {
            $table->string('sumber_data')->nullable()->after('status_siaga');
            $table->date('tanggal_verifikasi')->nullable()->after('sumber_data');
            $table->string('kontak')->nullable()->after('tanggal_verifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('sumber_air', function (Blueprint $table) {
            $table->dropColumn(['sumber_data', 'tanggal_verifikasi', 'kontak']);
        });
    }
};
