<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KodeAktivasi;
use App\Services\ActivationCodeService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AktivasiController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'nama_perangkat' => ['nullable', 'string', 'max:100'],
            'fcm_token' => ['nullable', 'string', 'max:4096'],
        ]);

        $hash = ActivationCodeService::hash($data['kode']);

        $hasil = DB::transaction(function () use ($hash, $data) {
            $kode = KodeAktivasi::query()
                ->where('kode_hash', $hash)
                ->whereNull('dipakai_pada')
                ->where('berlaku_sampai', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $kode) {
                return null;
            }

            $pasien = $kode->pasien;

            if ($pasien->status !== 'aktif') {
                return null;
            }

            $kode->update(['dipakai_pada' => now()]);

            // HP lama (jika ada) dicabut
            $pasien->tokens()->delete();
            $pasien->deviceTokens()->whereNull('dicabut_pada')->update(['dicabut_pada' => now()]);

            $perangkat = $pasien->deviceTokens()->create([
                'fcm_token' => $data['fcm_token'] ?? null,
                'nama_perangkat' => $data['nama_perangkat'] ?? null,
                'terakhir_aktif' => now(),
            ]);

            $token = $pasien->createToken('perangkat:' . $perangkat->id)->plainTextToken;

            return [$pasien, $token];
        });

        if (! $hasil) {
            return response()->json([
                'pesan' => 'Kode tidak valid atau sudah kedaluwarsa. Minta kode baru ke petugas.',
            ], 422);
        }

        [$pasien, $token] = $hasil;

        AuditLogger::catat('aktivasi_perangkat', $pasien);

        return response()->json([
            'token' => $token,
            'profil' => PasienController::ringkasProfil($pasien),
        ]);
    }
}
