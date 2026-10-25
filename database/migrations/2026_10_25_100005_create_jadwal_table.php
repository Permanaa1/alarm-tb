<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_program_id')->constrained('pasien_program')->cascadeOnDelete();
            $table->string('jenis', 20);                    // minum | kontrol | ambil_obat
            $table->date('tanggal');
            $table->time('jam');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['pasien_program_id', 'jenis', 'tanggal', 'jam'], 'jadwal_unik');
            $table->index(['pasien_program_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal');
    }
};