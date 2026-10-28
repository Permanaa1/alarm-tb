<?php

namespace App\Filament\Resources\PasienPrograms\Tables;

use App\Filament\Resources\PasienPrograms\Actions\AksiPasien;
use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use App\Services\RekapKepatuhan;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PasienProgramsTable
{
    public static function configure(Table $table): Table
    {
        $hpTerhubung = fn ($q) => $q->whereNull('dicabut_pada');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('pmo')
                ->withCount([
                    'jadwal as jt_7' => fn ($q) => $q->minum()->jatuhTempo()->sejak(7),
                    'jadwal as belum_7' => fn ($q) => $q->minum()->jatuhTempo()->sejak(7)->belumDiminum(),
                ])
                ->withExists(['deviceTokens as hp_terhubung' => $hpTerhubung])
                ->withMin('resep as mulai_resep', 'tgl_mulai')
                ->withMax('resep as selesai_resep', 'tgl_selesai'))
            ->columns([
                TextColumn::make('nama')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => 'RM ' . $record->no_rm),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'putus_obat' => 'danger',
                        'selesai' => 'gray',
                        default => 'warning',
                    }),
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
                IconColumn::make('hp_terhubung')
                    ->label('Aplikasi aktif')
                    ->boolean(),
                TextColumn::make('pmo.nama')
                    ->label('PMO')
                    ->placeholder('Belum ada')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Didaftarkan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'aktif' => 'Aktif',
                    'selesai' => 'Selesai',
                    'putus_obat' => 'Putus obat',
                    'pindah' => 'Pindah',
                ]),
                TernaryFilter::make('hp_terhubung')
                    ->label('Aplikasi aktif')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('deviceTokens', $hpTerhubung),
                        false: fn (Builder $query) => $query->whereDoesntHave('deviceTokens', $hpTerhubung),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordUrl(fn ($record) => PasienProgramResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Detail'),
                ActionGroup::make([
                    EditAction::make()->label('Ubah data'),
                    AksiPasien::buatKode(),
                    AksiPasien::buatJadwal(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-chevron-down')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([])
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada pasien terdaftar')
            ->emptyStateDescription('Klik "Daftarkan pasien" untuk menambahkan pasien dari SIMRS.');
    }
}
