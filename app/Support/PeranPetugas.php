<?php

namespace App\Support;

class PeranPetugas
{
    public const OPSI = [
        'admin' => 'Admin',
        'tb_dots' => 'Petugas TB-DOTS',
        'farmasi' => 'Farmasi',
    ];

    public const DESKRIPSI = [
        'admin' => 'Semua fitur, termasuk mengelola akun petugas.',
        'tb_dots' => 'Mendaftarkan pasien, mengisi resep, membuat jadwal, dan membuat kode aktivasi.',
        'farmasi' => 'Mengisi resep dan membuat jadwal. Tidak dapat membuat kode aktivasi.',
    ];

    public static function warna(?string $role): string
    {
        return match ($role) {
            'admin' => 'danger',
            'tb_dots' => 'success',
            'farmasi' => 'info',
            default => 'gray',
        };
    }
}
