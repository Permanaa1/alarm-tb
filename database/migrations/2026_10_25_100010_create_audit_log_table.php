<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50);                     // lihat_pasien, buat_kode, ubah_resep, dll
            $table->string('objek_tipe', 50)->nullable();
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['objek_tipe', 'objek_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};