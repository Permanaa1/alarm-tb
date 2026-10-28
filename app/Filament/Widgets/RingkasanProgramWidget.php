<?php

namespace App\Filament\Widgets;

use App\Models\Jadwal;
use App\Models\PasienProgram;
use App\Services\RekapKepatuhan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RingkasanProgramWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $hpTerhubung = fn ($q) => $q->whereNull('dicabut_pada');

        $aktif = PasienProgram::where('status', 'aktif')->count();

        $terhubung = PasienProgram::where('status', 'aktif')
            ->whereHas('deviceTokens', $hpTerhubung)
            ->count();

        $hariIni = Jadwal::query()
            ->minum()
            ->whereDate('tanggal', now()->toDateString())
            ->jatuhTempo()
            ->whereHas('pasien', fn ($q) => $q->where('status', 'aktif'));

        $jatuhTempoHariIni = (clone $hariIni)->count();
        $diminumHariIni = (clone $hariIni)->sudahDiminum()->count();

        $perhatian = PasienProgram::where('status', 'aktif')
            ->whereHas('deviceTokens', $hpTerhubung)
            ->withCount([
                'jadwal as jt7' => fn ($q) => $q->minum()->jatuhTempo()->sejak(7),
                'jadwal as dm7' => fn ($q) => $q->minum()->jatuhTempo()->sejak(7)->sudahDiminum(),
            ])
            ->get()
            ->filter(fn ($p) => RekapKepatuhan::label($p->dm7, $p->jt7, true)[1] === 'danger')
            ->count();

        return [
            Stat::make('Pasien aktif', $aktif)
                ->description('Sedang dalam program'),
            Stat::make('Aplikasi sudah aktif', "{$terhubung} dari {$aktif}")
                ->description('Pasien yang HP-nya sudah terhubung')
                ->color($terhubung < $aktif ? 'warning' : 'success'),
            Stat::make('Minum tercatat hari ini', $jatuhTempoHariIni > 0 ? "{$diminumHariIni} dari {$jatuhTempoHariIni}" : '-')
                ->description('Dari jadwal yang sudah lewat waktunya'),
            Stat::make('Perlu ditindaklanjuti', $perhatian)
                ->description('Kepatuhan 7 hari rendah (HP terhubung)')
                ->color($perhatian > 0 ? 'danger' : 'success'),
        ];
    }
}
