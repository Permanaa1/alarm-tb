<?php

namespace App\Services\Simrs;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SimrsRepository
{
    private function db(): \Illuminate\Database\Connection
    {
        return DB::connection(config('simrs.connection'));
    }

    private function pasienQuery(): Builder
    {
        $p = config('simrs.pasien');

        $query = $this->db()->table($p['table'])->select([
            "{$p['table']}.{$p['no_rm']} as no_rm",
            "{$p['table']}.{$p['nama']} as nama",
            "{$p['table']}.{$p['tgl_lahir']} as tgl_lahir",
            "{$p['table']}.{$p['no_hp']} as no_hp",
        ]);

        if (config('simrs.filter_tbc')) {
            $d = config('simrs.diagnosis');
            $prefixes = config('simrs.tbc_icd_prefix');

            $query->whereExists(function ($sub) use ($p, $d, $prefixes) {
                $sub->select(DB::raw(1))
                    ->from($d['table'])
                    ->whereColumn("{$d['table']}.{$d['no_rm']}", "{$p['table']}.{$p['no_rm']}")
                    ->where(function ($w) use ($d, $prefixes) {
                        foreach ($prefixes as $prefix) {
                            $w->orWhere("{$d['table']}.{$d['kode_icd']}", 'like', $prefix . '%');
                        }
                    });
            });
        }

        return $query;
    }

    /** Cari pasien berdasarkan no. RM atau nama. */
    public function cariPasien(string $keyword, int $limit = 20): Collection
    {
        $p = config('simrs.pasien');
        $keyword = trim($keyword);

        return $this->pasienQuery()
            ->where(function ($q) use ($p, $keyword) {
                $q->where("{$p['table']}.{$p['no_rm']}", 'like', "%{$keyword}%")
                  ->orWhere("{$p['table']}.{$p['nama']}", 'like', "%{$keyword}%");
            })
            ->limit($limit)
            ->get();
    }

    /** Ambil satu pasien berdasarkan no. RM persis. */
    public function cariByNoRm(string $noRm): ?object
    {
        $p = config('simrs.pasien');

        return $this->pasienQuery()
            ->where("{$p['table']}.{$p['no_rm']}", $noRm)
            ->first();
    }

    /** Ambil resep terbaru pasien (teks apa adanya, tanpa dihitung ulang). */
    public function resepPasien(string $noRm, int $limit = 10): Collection
    {
        $r = config('simrs.resep');

        return $this->db()->table($r['table'])
            ->select([
                "{$r['nama_obat']} as nama_obat",
                "{$r['dosis']} as dosis",
                "{$r['tanggal']} as tanggal",
            ])
            ->where($r['no_rm'], $noRm)
            ->orderByDesc($r['tanggal'])
            ->limit($limit)
            ->get();
    }
}