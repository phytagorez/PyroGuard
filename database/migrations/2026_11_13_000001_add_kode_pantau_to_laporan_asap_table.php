<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode acak untuk tautan status publik. Nomor tiket berurutan (#LP-0001)
 * mudah ditebak, jadi tautan status memakai kode ini. Hanya menambah kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_asap', function (Blueprint $table) {
            $table->string('kode_pantau', 24)->nullable()->unique()->after('nomor_tiket');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_asap', function (Blueprint $table) {
            $table->dropColumn('kode_pantau');
        });
    }
};
