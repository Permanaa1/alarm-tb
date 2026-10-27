<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LogMinum;
use App\Models\PasienProgram;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogMinumController extends Controller
{
    public function sinkron(Request $request): JsonResponse
    {
        /** @var PasienProgram $pasien */
        $pasien = $request->user();

        $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.client_uuid' => ['required', 'uuid'],
            'items.*.jadwal_id' => ['required', 'integer'],
            'items.*.status' => ['required', 'in:diminum,ditunda'],
            'items.*.waktu_konfirmasi' => ['required', 'date'],
        ]);

        $items = $request->input('items');

        // Hanya jadwal milik pasien ini yang boleh diisi
        $jadwalSah = $pasien->jadwal()
            ->whereIn('id', collect($items)->pluck('jadwal_id'))
            ->pluck('id')
            ->flip();

        $hasil = [];

        foreach ($items as $item) {
            if (! $jadwalSah->has($item['jadwal_id'])) {
                $hasil[] = [
                    'client_uuid' => $item['client_uuid'],
                    'hasil' => 'ditolak',
                    'alasan' => 'jadwal_tidak_ditemukan',
                ];
                continue;
            }

            $log = LogMinum::firstOrCreate(
                ['client_uuid' => $item['client_uuid']],
                [
                    'jadwal_id' => $item['jadwal_id'],
                    'status' => $item['status'],
                    'waktu_konfirmasi' => Carbon::parse($item['waktu_konfirmasi'])
                        ->setTimezone(config('app.timezone')),
                    'diterima_server_at' => now(),
                ]
            );

            $hasil[] = [
                'client_uuid' => $item['client_uuid'],
                'hasil' => $log->wasRecentlyCreated ? 'dicatat' : 'sudah_ada',
            ];
        }

        return response()->json(['hasil' => $hasil]);
    }
}
