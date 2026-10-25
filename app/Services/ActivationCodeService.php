<?php

namespace App\Services;

use App\Models\KodeAktivasi;
use App\Models\PasienProgram;

class ActivationCodeService
{
    public const MASA_BERLAKU_JAM = 48;

    // Tanpa 0/O dan 1/I agar tidak salah baca saat ditulis tangan
    private const ALFABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Buat kode baru. Kode lama yang belum dipakai dibatalkan.
     * Mengembalikan kode mentah (hanya tersedia saat ini, di database hanya hash-nya).
     */
    public function buat(PasienProgram $pasien, ?int $userId): string
    {
        $pasien->kodeAktivasi()
            ->whereNull('dipakai_pada')
            ->where('berlaku_sampai', '>', now())
            ->update(['berlaku_sampai' => now()]);

        $kode = $this->generateKodeUnik();

        $pasien->kodeAktivasi()->create([
            'kode_hash' => self::hash($kode),
            'berlaku_sampai' => now()->addHours(self::MASA_BERLAKU_JAM),
            'dibuat_oleh' => $userId,
        ]);

        return $kode;
    }

    /** Hash kode (huruf besar, tanpa spasi dan tanda hubung). */
    public static function hash(string $kode): string
    {
        $bersih = strtoupper(str_replace(['-', ' '], '', trim($kode)));

        return hash('sha256', $bersih);
    }

    /** Tampilan kode untuk petugas, contoh ABCD-EFGH. */
    public static function format(string $kode): string
    {
        return implode('-', str_split($kode, 4));
    }

    private function generateKodeUnik(): string
    {
        do {
            $kode = '';
            for ($i = 0; $i < 8; $i++) {
                $kode .= self::ALFABET[random_int(0, strlen(self::ALFABET) - 1)];
            }
        } while (KodeAktivasi::where('kode_hash', self::hash($kode))->exists());

        return $kode;
    }
}