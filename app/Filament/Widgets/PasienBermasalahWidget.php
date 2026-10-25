<?php

namespace App\Filament\Widgets;

use App\Models\LogMinum;
use App\Models\PasienProgram;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PasienBermasalahWidget extends BaseWidget
{
    protected static ?string $heading = 'Pasien perlu ditindaklanjuti (7 hari terakhir)';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // Dosis dianggap "belum terkonfirmasi" bila sudah lewat 2 jam dari jadwal
        $batas = now()->subHours(2);

        $belumTerkonfirmasi = function ($q) use ($batas) {
            $q->where('jenis', 'minum')
                ->whereDate('tanggal', '>=', now()->subDays(6)->toDateString())
                ->where(function ($w) use ($batas) {
                    $w->whereDate('tanggal', '<', $batas->toDateString())
                        ->orWhere(function ($x) use ($batas) {
                            $x->whereDate('tanggal', $batas->toDateString())
                                ->where('jam', '<=', $batas->format('H:i:s'));
                        });
                })
                ->whereDoesntHave('logMinum', fn ($l) => $l->where('status', 'diminum'));
        };

        return $table
            ->query(
                PasienProgram::query()
                    ->where('status', 'aktif')
                    ->whereHas('jadwal', $belumTerkonfirmasi)
                    ->withCount(['jadwal as belum_terkonfirmasi' => $belumTerkonfirmasi])
                    ->addSelect([
                        'terakhir_minum' => LogMinum::query()
                            ->select('log_minum.waktu_konfirmasi')
                            ->join('jadwal', 'jadwal.id', '=', 'log_minum.jadwal_id')
                            ->whereColumn('jadwal.pasien_program_id', 'pasien_program.id')
                            ->where('log_minum.status', 'diminum')
                            ->orderByDesc('log_minum.waktu_konfirmasi')
                            ->limit(1),
                    ])
                    ->with('pmo')
                    ->orderByDesc('belum_terkonfirmasi')
            )
            ->columns([
                TextColumn::make('nama')->label('Pasien')->searchable(),
                TextColumn::make('no_rm')->label('No. RM'),
                TextColumn::make('belum_terkonfirmasi')
                    ->label('Dosis belum terkonfirmasi')
                    ->badge()
                    ->color(fn ($state): string => $state >= 3 ? 'danger' : 'warning'),
                TextColumn::make('terakhir_minum')
                    ->label('Terakhir konfirmasi minum')
                    ->dateTime('d M H:i')
                    ->placeholder('Belum pernah'),
                TextColumn::make('no_hp')->label('HP pasien')->copyable(),
                TextColumn::make('pmo.nama')->label('PMO')->placeholder('Belum ada'),
                TextColumn::make('pmo.no_hp')->label('HP PMO')->copyable(),
            ])
            ->paginated([10])
            ->poll('30s')
            ->emptyStateHeading('Tidak ada pasien yang perlu ditindaklanjuti');
    }
}