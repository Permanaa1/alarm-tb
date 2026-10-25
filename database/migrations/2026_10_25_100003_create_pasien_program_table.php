<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasien_program', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm', 30)->unique();      // penghubung ke SIMRS
            $table->string('nama');                       // salinan dari SIMRS
            $table->string('no_hp', 20)->nullable();
            $table->foreignId('pmo_id')->nullable()->constrained('pmo')->nullOnDelete();
            $table->time('jam_minum_default')->default('07:00:00');
            $table->string('status', 20)->default('aktif'); // aktif | selesai | putus_obat | pindah
            $table->boolean('foto_bukti_aktif')->default(false);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasien_program');
    }
};