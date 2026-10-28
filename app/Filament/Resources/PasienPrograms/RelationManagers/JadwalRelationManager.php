<?php

namespace App\Filament\Resources\PasienPrograms\RelationManagers;

use App\Filament\Resources\PasienPrograms\Pages\ViewPasienProgram;
use App\Models\Jadwal;
use Carbon\Carbon;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Riwayat dan jadwal minum. Hanya tampil (baca saja) di halaman detail pasien. */
class JadwalRelationManager extends RelationManager
{
    protected static string $relationship = 'jadwal';

    protected static ?string $title = 'Riwayat dan jadwal minum';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewPasienProgram::class;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    private static function statusTeks(Jadwal $j): string
    {
        if ($j->logMinum->contains('status', 'diminum')) {
            return 'Sudah minum';
        }

        $waktu = Carbon::parse($j->tanggal->toDateString() . ' ' . $j->jam);

        if ($waktu->isFuture()) {
            return 'Mendatang';
        }

        if ($j->logMinum->contains('status', 'ditunda')) {
            return 'Ditunda';
        }

        return 'Belum tercatat';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tanggal')
            ->modifyQueryUsing(fn (Builder $query) => $query->minum()->with('logMinum'))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('tanggal')->orderByDesc('jam'))
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('D, d M Y'),
                TextColumn::make('jam')
                    ->label('Jam')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                TextColumn::make('keterangan')
                    ->label('Obat')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('status_minum')
                    ->label('Status')
                    ->state(fn ($record) => self::statusTeks($record))
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Sudah minum' => 'success',
                        'Belum tercatat', 'Ditunda' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('waktu_konfirmasi')
                    ->label('Dikonfirmasi pada')
                    ->state(fn ($record) => $record->logMinum
                        ->where('status', 'diminum')
                        ->sortByDesc('waktu_konfirmasi')
                        ->first()?->waktu_konfirmasi)
                    ->dateTime('d M H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('periode')
                    ->label('Periode')
                    ->options([
                        '7' => '7 hari terakhir',
                        '30' => '30 hari terakhir',
                        'semua' => 'Semua',
                    ])
                    ->default('30')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            '7' => $query->sejak(7),
                            '30' => $query->sejak(30),
                            default => $query,
                        };
                    }),
                Filter::make('sudah_lewat')
                    ->label('Sembunyikan yang belum waktunya')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->jatuhTempo()),
                Filter::make('belum_tercatat')
                    ->label('Hanya yang belum tercatat')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->jatuhTempo()->belumDiminum()),
            ])
            ->paginated([10, 25, 50]);
    }
}
