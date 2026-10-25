<?php

namespace App\Filament\Resources\PasienPrograms\Tables;

use App\Models\PasienProgram;
use App\Services\ActivationCodeService;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Services\ScheduleGenerator;

class PasienProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_rm')->label('No. RM')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('pmo.nama')->label('PMO')->placeholder('Belum ada'),
                TextColumn::make('jam_minum_default')
                    ->label('Jam minum')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'putus_obat' => 'danger',
                        'selesai' => 'gray',
                        default => 'warning',
                    }),
                IconColumn::make('foto_bukti_aktif')->label('Foto bukti')->boolean(),
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
            ])
            ->recordActions([
                Action::make('buatKode')
                    ->label('Buat kode aktivasi')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (): bool => in_array(auth()->user()?->role, ['admin', 'tb_dots'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Buat kode aktivasi baru?')
                    ->modalDescription('Kode lama yang belum dipakai akan dibatalkan. Kode hanya ditampilkan sekali, jadi tuliskan di kartu pasien.')
                    ->modalSubmitActionLabel('Buat kode')
                    ->action(function (PasienProgram $record) {
                        $kode = app(ActivationCodeService::class)->buat($record, auth()->id());

                        AuditLogger::catat('buat_kode', $record);

                        Notification::make()
                            ->title('Kode aktivasi untuk ' . $record->nama)
                            ->body(ActivationCodeService::format($kode) . ' (berlaku ' . ActivationCodeService::MASA_BERLAKU_JAM . ' jam, sekali pakai)')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('buatJadwal')
                ->label('Buat/perbarui jadwal')
                ->icon('heroicon-o-calendar-days')
                ->visible(fn (): bool => in_array(auth()->user()?->role, ['admin', 'tb_dots', 'farmasi'], true))
                ->requiresConfirmation()
                ->modalHeading('Buat atau perbarui jadwal minum?')
                ->modalDescription('Jadwal minum dari hari ini sampai tanggal selesai resep akan dibuat ulang. Jadwal yang sudah punya catatan konfirmasi tidak dihapus.')
                ->action(function (PasienProgram $record) {
                    $hasil = app(ScheduleGenerator::class)->generate($record);

                    AuditLogger::catat('buat_jadwal', $record, json_encode($hasil));

                    $notif = Notification::make()
                        ->title('Jadwal untuk ' . $record->nama)
                        ->body(
                            "Dibuat: {$hasil['dibuat']}, diganti: {$hasil['dihapus']}"
                            . ($hasil['dilewati'] ? ", resep dilewati: {$hasil['dilewati']} (tanggal selesai kosong atau tidak wajar)" : '')
                        );

                    ($hasil['dibuat'] === 0 || $hasil['dilewati'] > 0) ? $notif->warning() : $notif->success();

                    $notif->send();
                }),
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}