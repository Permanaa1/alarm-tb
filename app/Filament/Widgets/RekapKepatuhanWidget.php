<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use App\Models\LogMinum;
use App\Models\PasienProgram;
use App\Services\RekapKepatuhan;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RekapKepatuhanWidget extends BaseWidget
{
    protected static ?string $heading = 'Rekap pasien aktif (7 hari terakhir)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $hp = fn ($q) => $q->whereNull('dicabut_pada');

        return $table
            ->query(
                PasienProgram::query()
                    ->where('status', 'aktif')
                    ->withCount([
                        'jadwal as jt_7' => fn ($q) => $q->minum()->jatuhTempo()->sejak(7),
                        'jadwal as belum_7' => fn ($q) => $q->minum()->jatuhTempo()->sejak(7)->belumDiminum(),
                    ])
                    ->withExists(['deviceTokens as hp_terhubung' => $hp])
                    ->withMin('resep as mulai_resep', 'tgl_mulai')
                    ->withMax('resep as selesai_resep', 'tgl_selesai')
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
                    ->orderByDesc('belum_7')
                    ->orderBy('nama')
            )
            ->description('Penilaian dihitung dari jadwal minum yang sudah lewat waktunya. "Belum tercatat" belum tentu berarti pasien tidak minum: bisa juga HP belum mengirim data atau aplikasi belum diaktifkan. Pasien dengan belum-tercatat terbanyak ada di atas. Klik baris untuk melihat detail.')
            ->columns([
                TextColumn::make('nama')
                    ->label('Pasien')
                    ->searchable()
                    ->description(fn ($record) => 'RM ' . $record->no_rm),
                TextColumn::make('mulai_resep')
                    ->label('Pengobatan')
                    ->state(fn ($record) => $record->mulai_resep && $record->selesai_resep
                        ? Carbon::parse($record->mulai_resep)->format('d M Y') . ' – ' . Carbon::parse($record->selesai_resep)->format('d M Y')
                        : 'Belum ada resep')
                    ->description(function ($record) {
                        if (! $record->mulai_resep || ! $record->selesai_resep) {
                            return null;
                        }

                        $t = RekapKepatuhan::hitungTahap(
                            Carbon::parse($record->mulai_resep),
                            Carbon::parse($record->selesai_resep)
                        );

                        return $t['tahap'] === 'Sedang berjalan'
                            ? "Hari ke-{$t['hari_ke']} dari {$t['total_hari']}"
                            : $t['tahap'];
                    }),
                TextColumn::make('kepatuhan')
                    ->label('Kepatuhan 7 hari')
                    ->state(fn ($record) => RekapKepatuhan::label(
                        $record->jt_7 - $record->belum_7,
                        $record->jt_7,
                        (bool) $record->hp_terhubung
                    )[0])
                    ->badge()
                    ->color(fn ($record): string => RekapKepatuhan::label(
                        $record->jt_7 - $record->belum_7,
                        $record->jt_7,
                        (bool) $record->hp_terhubung
                    )[1])
                    ->description(fn ($record) => $record->hp_terhubung
                        ? RekapKepatuhan::teksDosis($record->jt_7 - $record->belum_7, $record->jt_7)
                        : null),
                TextColumn::make('terakhir_minum')
                    ->label('Terakhir konfirmasi minum')
                    ->dateTime('d M H:i')
                    ->placeholder('Belum pernah'),
                IconColumn::make('hp_terhubung')
                    ->label('Aplikasi aktif')
                    ->boolean(),
                TextColumn::make('no_hp')->label('HP pasien')->copyable()->placeholder('-'),
                TextColumn::make('pmo.nama')->label('PMO')->placeholder('Belum ada'),
                TextColumn::make('pmo.no_hp')->label('HP PMO')->copyable()->placeholder('-'),
            ])
            ->filters([
                TernaryFilter::make('hp_terhubung')
                    ->label('Aplikasi aktif')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('deviceTokens', $hp),
                        false: fn (Builder $query) => $query->whereDoesntHave('deviceTokens', $hp),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordUrl(fn ($record) => PasienProgramResource::getUrl('view', ['record' => $record]))
            ->poll('60s')
            ->paginated([10, 25])
            ->emptyStateHeading('Belum ada pasien aktif');
    }
}
