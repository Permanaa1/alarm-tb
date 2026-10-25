<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_minum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->uuid('client_uuid')->nullable()->unique();    // supaya sinkronisasi ulang tidak dobel
            $table->string('status', 25);                          // diminum | terlewat | ditunda | belum_terkonfirmasi
            $table->timestamp('waktu_konfirmasi')->nullable();     // waktu menurut HP
            $table->timestamp('diterima_server_at')->nullable();   // waktu data sampai ke server
            $table->string('foto_path')->nullable();
            $table->timestamps();

            $table->index(['jadwal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_minum');
    }
};