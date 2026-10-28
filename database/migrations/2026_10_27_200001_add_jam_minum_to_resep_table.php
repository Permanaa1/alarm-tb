<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            // Daftar jam minum per resep, contoh ["07:00:00","14:00:00","21:00:00"].
            // Kosong (null) = ikut jam minum pasien (perilaku lama).
            $table->json('jam_minum')->nullable()->after('fase');
        });
    }

    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->dropColumn('jam_minum');
        });
    }
};
