<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_program_id')->constrained('pasien_program')->cascadeOnDelete();
            $table->string('versi_teks', 20);
            $table->timestamp('disetujui_pada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent');
    }
};