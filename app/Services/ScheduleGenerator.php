<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\PasienProgram;
use App\Models\Resep;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleGenerator
{
    // Batas wajar rentang satu resep, untuk menahan salah ketik tahun
    private const MAKS_HARI_PER_RESEP = 400;

    /**
     * Membuat jadwal minum dari sekarang sampai tanggal selesai tiap resep.
     *
     * Aturan:
     * - Satu resep bisa punya beberapa jam minum per hari (kolom jam_minum).
     *   Bila kosong, dipakai jam minum default pasien.
     * - Hanya jadwal yang waktunya MASIH DI DEPAN yang dibuat atau diganti.
     *   Jadwal yang waktunya sudah lewat tidak disentuh, sehingga dosis yang terlewat
     *   tetap terlihat di dashboard dan pasien yang baru didaftarkan siang hari
     *   tidak langsung dianggap melewatkan dosis pagi.
     * - Jadwal mendatang yang sudah punya catatan konfirmasi tidak dihapus.
     * - Beberapa obat pada jam yang sama digabung menjadi satu jadwal.
     *
     * Mengembalikan ['dibuat' => int, 'dihapus' => int, 'dilewati' => int].
     */
    public function generate(PasienProgram $pasien): array
    {
        $sekarang = now();
        $hariIni = $sekarang->copy()->startOfDay();
        $jamPasien = Carbon::parse((string) $pasien->jam_minum_default)->format('H:i:s');

        $dilewati = 0;
        $slot = []; // 'Y-m-d' => ['H:i:s' => ['Nama obat' => true]]

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

            $daftarJam = $this->daftarJam($resep, $jamPasien);

            for ($d = $mulai->copy(); $d->lte($selesai); $d->addDay()) {
                $tanggal = $d->toDateString();

                foreach ($daftarJam as $jam) {
                    // Lewati waktu yang sudah lewat
                    if (Carbon::parse("{$tanggal} {$jam}")->lte($sekarang)) {
                        continue;
                    }

                    $slot[$tanggal][$jam][$resep->nama_obat] = true;
                }
            }
        }

        ksort($slot);

        $rows = [];
        foreach ($slot as $tanggal => $perJam) {
            ksort($perJam);

            foreach ($perJam as $jam => $obat) {
                $rows[] = [
                    'pasien_program_id' => $pasien->id,
                    'jenis' => 'minum',
                    'tanggal' => $tanggal,
                    'jam' => $jam,
                    'keterangan' => Str::limit(implode(', ', array_keys($obat)), 200, ''),
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }
        }

        return DB::transaction(function () use ($pasien, $sekarang, $rows, $dilewati) {
            // Hapus jadwal minum yang waktunya masih di depan dan belum punya catatan konfirmasi
            $dihapus = $pasien->jadwal()
                ->where('jenis', 'minum')
                ->where(function ($q) use ($sekarang) {
                    $q->where('tanggal', '>', $sekarang->toDateString())
                        ->orWhere(function ($x) use ($sekarang) {
                            $x->where('tanggal', $sekarang->toDateString())
                                ->where('jam', '>', $sekarang->format('H:i:s'));
                        });
                })
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

    /** Jam minum resep (terurut, tanpa duplikat). Kosong -> jam default pasien. */
    private function daftarJam(Resep $resep, string $jamPasien): array
    {
        $jam = collect($resep->jam_minum ?? [])
            ->filter()
            ->map(fn ($j) => Carbon::parse((string) $j)->format('H:i:s'))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $jam ?: [$jamPasien];
    }
}
