<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data pasien kini mandiri: diinput manual oleh petugas, tidak lagi diambil dari SIMRS.
 * - pasien_program: tambah tempat/tanggal lahir, jenis kelamin, alamat.
 *   Kolom dibuat nullable agar pasien lama (yang dulu disalin dari SIMRS) tidak rusak;
 *   formulir dashboard mewajibkan tanggal lahir untuk data baru dan saat data diubah.
 * - resep: kolom "sumber" (simrs | manual) dibuang, karena semua resep kini manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasien_program', function (Blueprint $table) {
            $table->string('tempat_lahir', 100)->nullable()->after('nama');
            $table->date('tgl_lahir')->nullable()->after('tempat_lahir');
            $table->string('jenis_kelamin', 1)->nullable()->after('tgl_lahir'); // L | P
            $table->text('alamat')->nullable()->after('no_hp');
        });

        if (Schema::hasColumn('resep', 'sumber')) {
            Schema::table('resep', function (Blueprint $table) {
                $table->dropColumn('sumber');
            });
        }
    }

    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->string('sumber', 10)->default('manual')->after('tgl_selesai');
        });

        Schema::table('pasien_program', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'tgl_lahir', 'jenis_kelamin', 'alamat']);
        });
    }
};
