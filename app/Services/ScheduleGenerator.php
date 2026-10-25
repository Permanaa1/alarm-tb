<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\PasienProgram;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleGenerator
{
    // Batas wajar rentang satu resep, untuk menahan salah ketik tahun
    private const MAKS_HARI_PER_RESEP = 400;

    /**
     * Buat jadwal minum harian dari hari ini sampai tanggal selesai tiap resep.
     * Mengembalikan ['dibuat' => int, 'dihapus' => int, 'dilewati' => int].
     */
    public function generate(PasienProgram $pasien): array
    {
        $hariIni = now()->startOfDay();
        $jam = Carbon::parse((string) $pasien->jam_minum_default)->format('H:i:s');

        $dilewati = 0;
        $hari = []; // 'Y-m-d' => ['intensif' => true, ...]

        foreach ($pasien->resep()->get() as $resep) {
            if (! $resep->tgl_selesai) {
                $dilewati++;
                continue;
            }

            $mulai = $resep->tgl_mulai->copy()->startOfDay();
            $selesai = $resep->tgl_selesai->copy()->startOfDay();

            if ($selesai->lt($mulai) || $selesai->gt($mulai->copy()->addDays(self::MAKS_HARI_PER_RESEP))) {
                $dilewati++;
                continue;
            }

            if ($mulai->lt($hariIni)) {
                $mulai = $hariIni->copy();
            }

            for ($d = $mulai->copy(); $d->lte($selesai); $d->addDay()) {
                $hari[$d->toDateString()][$resep->fase] = true;
            }
        }

        ksort($hari);
        $sekarang = now();

        $rows = [];
        foreach ($hari as $tanggal => $fases) {
            $rows[] = [
                'pasien_program_id' => $pasien->id,
                'jenis' => 'minum',
                'tanggal' => $tanggal,
                'jam' => $jam,
                'keterangan' => 'Fase ' . implode(' + ', array_keys($fases)),
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ];
        }

        return DB::transaction(function () use ($pasien, $hariIni, $rows, $dilewati) {
            // Hapus jadwal minum mendatang yang belum punya catatan konfirmasi
            $dihapus = $pasien->jadwal()
                ->where('jenis', 'minum')
                ->where('tanggal', '>=', $hariIni->toDateString())
                ->whereDoesntHave('logMinum')
                ->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                Jadwal::upsert(
                    $chunk,
                    ['pasien_program_id', 'jenis', 'tanggal', 'jam'],
                    ['keterangan', 'updated_at']
                );
            }

            return [
                'dibuat' => count($rows),
                'dihapus' => $dihapus,
                'dilewati' => $dilewati,
            ];
        });
    }
}