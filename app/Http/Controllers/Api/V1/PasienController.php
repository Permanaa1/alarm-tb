<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\PasienProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public const VERSI_CONSENT = '1.0';

    public static function ringkasProfil(PasienProgram $pasien): array
    {
        $pasien->loadMissing('pmo');

        $resepAktif = $pasien->resep()
            ->where(function ($q) {
                $q->whereNull('tgl_selesai')->orWhere('tgl_selesai', '>=', now()->toDateString());
            })
            ->orderBy('tgl_mulai')
            ->get(['nama_obat', 'dosis_teks', 'fase', 'jam_minum', 'tgl_mulai', 'tgl_selesai']);

        return [
            'nama' => $pasien->nama,
            'jam_minum_default' => substr((string) $pasien->jam_minum_default, 0, 5),
            'foto_bukti_aktif' => (bool) $pasien->foto_bukti_aktif,
            'pmo' => $pasien->pmo
                ? ['nama' => $pasien->pmo->nama, 'hubungan' => $pasien->pmo->hubungan]
                : null,
            'resep' => $resepAktif,
            'consent_disetujui' => $pasien->consents()
                ->where('versi_teks', self::VERSI_CONSENT)
                ->exists(),
            'versi_consent' => self::VERSI_CONSENT,
        ];
    }

    public function profil(Request $request): JsonResponse
    {
        return response()->json(self::ringkasProfil($request->user()));
    }

    public function jadwal(Request $request): JsonResponse
    {
        /** @var PasienProgram $pasien */
        $pasien = $request->user();

        $hari = min(max((int) $request->query('hari', 30), 1), 60);
        $dari = now()->startOfDay();
        $sampai = $dari->copy()->addDays($hari);

        // Penanda bahwa HP ini masih aktif
        if ($deviceId = $this->deviceId($request)) {
            DeviceToken::where('id', $deviceId)
                ->where('pasien_program_id', $pasien->id)
                ->update(['terakhir_aktif' => now()]);
        }

        $jadwal = $pasien->jadwal()
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->with(['logMinum' => fn ($q) => $q->select('id', 'jadwal_id', 'status')->latest('id')])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        return response()->json([
            'dihasilkan_pada' => now()->toIso8601String(),
            'zona_waktu' => config('app.timezone'),
            'jadwal' => $jadwal->map(fn ($j) => [
                'id' => $j->id,
                'jenis' => $j->jenis,
                'tanggal' => $j->tanggal->toDateString(),
                'jam' => substr((string) $j->jam, 0, 5),
                'keterangan' => $j->keterangan,
                'status' => $j->logMinum->first()?->status,
            ])->values(),
        ]);
    }

    public function consent(Request $request): JsonResponse
    {
        $request->validate(['setuju' => ['accepted']]);

        $request->user()->consents()->firstOrCreate(
            ['versi_teks' => self::VERSI_CONSENT],
            ['disetujui_pada' => now()]
        );

        return response()->json(['pesan' => 'Persetujuan tercatat.']);
    }

    public function perangkat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
            'nama_perangkat' => ['nullable', 'string', 'max:100'],
        ]);

        $deviceId = $this->deviceId($request);

        DeviceToken::where('id', $deviceId)
            ->where('pasien_program_id', $request->user()->id)
            ->update(array_filter([
                'fcm_token' => $data['fcm_token'],
                'nama_perangkat' => $data['nama_perangkat'] ?? null,
                'terakhir_aktif' => now(),
            ], fn ($v) => $v !== null));

        return response()->json(['pesan' => 'Perangkat diperbarui.']);
    }

    /** ID perangkat diambil dari nama token ("perangkat:12"). */
    private function deviceId(Request $request): ?int
    {
        $nama = $request->user()->currentAccessToken()?->name ?? '';

        return preg_match('/^perangkat:(\d+)$/', $nama, $m) ? (int) $m[1] : null;
    }
}
