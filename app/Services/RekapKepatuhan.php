<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\PasienProgram;
use Carbon\Carbon;

/**
 * Menghitung rekap pengobatan dan kepatuhan untuk dashboard.
 *
 * Definisi:
 * - "Jatuh tempo"  = jadwal minum yang waktunya sudah lewat.
 * - "Diminum"      = jatuh tempo yang punya catatan status "diminum".
 * - "Belum tercatat" = jatuh tempo tanpa catatan "diminum". Belum tentu berarti tidak minum:
 *   bisa juga HP belum mengirim data, atau aplikasi belum diaktifkan.
 *
 * Ambang di bawah adalah nilai awal, silakan dikonfirmasi dengan petugas TB.
 */
class RekapKepatuhan
{
    public const AMBANG_RUTIN = 90;  // persen
    public const AMBANG_CUKUP = 70;  // persen
    public const MIN_DOSIS = 3;      // minimal dosis jatuh tempo agar penilaian bermakna

    private array $memo = [];

    // ---------------- helper statis (dipakai tabel dan widget) ----------------

    public static function persen(int $diminum, int $jatuhTempo): ?int
    {
        return $jatuhTempo > 0 ? (int) round($diminum / $jatuhTempo * 100) : null;
    }

    /** @return array{0: string, 1: string} [teks, warna] */
    public static function label(int $diminum, int $jatuhTempo, bool $hpTerhubung): array
    {
        if (! $hpTerhubung) {
            return ['Aplikasi belum aktif', 'gray'];
        }

        if ($jatuhTempo < self::MIN_DOSIS) {
            return ['Data belum cukup', 'gray'];
        }

        $persen = self::persen($diminum, $jatuhTempo);

        if ($persen >= self::AMBANG_RUTIN) {
            return ['Rutin', 'success'];
        }

        if ($persen >= self::AMBANG_CUKUP) {
            return ['Cukup', 'warning'];
        }

        return ['Perlu ditindaklanjuti', 'danger'];
    }

    public static function teksDosis(int $diminum, int $jatuhTempo): string
    {
        $persen = self::persen($diminum, $jatuhTempo);

        return $persen === null
            ? 'Belum ada jadwal yang jatuh tempo'
            : "{$diminum} dari {$jatuhTempo} dosis ({$persen}%)";
    }

    /** Selisih hari kalender (b - a), tanpa bergantung versi Carbon. */
    public static function selisihHari(Carbon $a, Carbon $b): int
    {
        return (int) round(
            ($b->copy()->startOfDay()->timestamp - $a->copy()->startOfDay()->timestamp) / 86400
        );
    }

    /** Tahap pengobatan berdasarkan tanggal mulai dan selesai. */
    public static function hitungTahap(Carbon $mulai, Carbon $selesai): array
    {
        $mulai = $mulai->copy()->startOfDay();
        $selesai = $selesai->copy()->startOfDay();
        $hariIni = now()->startOfDay();

        $total = max(1, self::selisihHari($mulai, $selesai) + 1);

        if ($hariIni->lt($mulai)) {
            $tahap = 'Belum dimulai';
            $hariKe = 0;
            $sisa = $total;
        } elseif ($hariIni->gt($selesai)) {
            $tahap = 'Selesai';
            $hariKe = $total;
            $sisa = 0;
        } else {
            $tahap = 'Sedang berjalan';
            $hariKe = self::selisihHari($mulai, $hariIni) + 1;
            $sisa = $total - $hariKe;
        }

        return [
            'tahap' => $tahap,
            'hari_ke' => $hariKe,
            'total_hari' => $total,
            'sisa_hari' => $sisa,
            'persen' => (int) round($hariKe / $total * 100),
        ];
    }

    // ---------------- per pasien ----------------

    public function pengobatan(PasienProgram $pasien): array
    {
        return $this->memo["pengobatan:{$pasien->id}"] ??= $this->hitungPengobatan($pasien);
    }

    private function hitungPengobatan(PasienProgram $pasien): array
    {
        $reseps = $pasien->resep()->get();

        $mulai = $reseps->pluck('tgl_mulai')->filter()->min();
        $selesai = $reseps->pluck('tgl_selesai')->filter()->max();

        if (! $mulai || ! $selesai) {
            return ['ada' => false];
        }

        $hariIni = now()->startOfDay();

        $fase = $reseps
            ->filter(fn ($r) => $r->tgl_mulai && $r->tgl_selesai
                && $hariIni->gte($r->tgl_mulai->copy()->startOfDay())
                && $hariIni->lte($r->tgl_selesai->copy()->startOfDay()))
            ->pluck('fase')
            ->unique()
            ->implode(' + ');

        return array_merge(
            ['ada' => true, 'mulai' => $mulai, 'selesai' => $selesai, 'fase_sekarang' => $fase],
            self::hitungTahap($mulai, $selesai)
        );
    }

    /** @param int|null $hari null = sejak awal */
    public function kepatuhan(PasienProgram $pasien, ?int $hari): array
    {
        return $this->memo["kepatuhan:{$pasien->id}:" . ($hari ?? 'semua')]
            ??= $this->hitungKepatuhan($pasien, $hari);
    }

    private function hitungKepatuhan(PasienProgram $pasien, ?int $hari): array
    {
        $dasar = Jadwal::query()
            ->where('pasien_program_id', $pasien->id)
            ->minum()
            ->jatuhTempo();

        if ($hari !== null) {
            $dasar->sejak($hari);
        }

        $jatuhTempo = (clone $dasar)->count();
        $diminum = (clone $dasar)->sudahDiminum()->count();

        return [
            'jatuh_tempo' => $jatuhTempo,
            'diminum' => $diminum,
            'belum' => $jatuhTempo - $diminum,
            'persen' => self::persen($diminum, $jatuhTempo),
        ];
    }

    public function terakhirMinum(PasienProgram $pasien): ?Carbon
    {
        $waktu = \App\Models\LogMinum::query()
            ->join('jadwal', 'jadwal.id', '=', 'log_minum.jadwal_id')
            ->where('jadwal.pasien_program_id', $pasien->id)
            ->where('log_minum.status', 'diminum')
            ->max('log_minum.waktu_konfirmasi');

        return $waktu ? Carbon::parse($waktu) : null;
    }

    public function statusAplikasi(PasienProgram $pasien): array
    {
        return $this->memo["aplikasi:{$pasien->id}"] ??= $this->hitungStatusAplikasi($pasien);
    }

    private function hitungStatusAplikasi(PasienProgram $pasien): array
    {
        $perangkat = $pasien->deviceTokens()->whereNull('dicabut_pada')->latest('id')->first();
        $consent = $pasien->consents()->latest('id')->first();

        return [
            'terhubung' => $perangkat !== null,
            'perangkat' => $perangkat?->nama_perangkat,
            'terakhir_aktif' => $perangkat?->terakhir_aktif,
            'consent' => $consent
                ? "Disetujui (versi {$consent->versi_teks}, " . $consent->disetujui_pada->format('d M Y') . ')'
                : 'Belum disetujui',
        ];
    }

    public function statusKode(PasienProgram $pasien): string
    {
        $kode = $pasien->kodeAktivasi()->latest('id')->first();

        if (! $kode) {
            return 'Belum pernah dibuat';
        }

        if ($kode->dipakai_pada) {
            return 'Sudah dipakai pada ' . $kode->dipakai_pada->format('d M Y H:i');
        }

        if ($kode->berlaku_sampai->isPast()) {
            return 'Kedaluwarsa pada ' . $kode->berlaku_sampai->format('d M Y H:i');
        }

        return 'Belum dipakai, berlaku sampai ' . $kode->berlaku_sampai->format('d M Y H:i');
    }
}
