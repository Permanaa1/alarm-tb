<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_aktivasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_program_id')->constrained('pasien_program')->cascadeOnDelete();
            $table->string('kode_hash', 64)->unique();      // sha256 dari kode
            $table->timestamp('berlaku_sampai');
            $table->timestamp('dipakai_pada')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_aktivasi');
    }
};