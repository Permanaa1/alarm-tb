<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_program_id')->constrained('pasien_program')->cascadeOnDelete();
            $table->string('nama_obat');
            $table->string('dosis_teks');                   // teks apa adanya dari resep dokter
            $table->string('fase', 20);                     // intensif | lanjutan
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->string('sumber', 10)->default('manual'); // simrs | manual
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resep');
    }
};