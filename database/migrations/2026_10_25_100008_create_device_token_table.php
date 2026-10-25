<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_token', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_program_id')->constrained('pasien_program')->cascadeOnDelete();
            $table->text('fcm_token')->nullable();
            $table->string('nama_perangkat')->nullable();
            $table->timestamp('terakhir_aktif')->nullable();
            $table->timestamp('dicabut_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_token');
    }
};