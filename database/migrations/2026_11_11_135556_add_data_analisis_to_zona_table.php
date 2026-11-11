<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
public function up(): void
{
    Schema::table('zona', function (Blueprint $table) {
        $table->json('data_analisis')->nullable()->after('poligon_geojson');
    });
}

public function down(): void
{
    Schema::table('zona', function (Blueprint $table) {
        $table->dropColumn('data_analisis');
    });
}

};
