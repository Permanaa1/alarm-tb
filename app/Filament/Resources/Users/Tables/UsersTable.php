<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\AuditLogger;
use App\Support\PeranPetugas;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PeranPetugas::OPSI[$state] ?? $state)
                    ->color(fn ($state): string => PeranPetugas::warna($state)),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')->label('Peran')->options(PeranPetugas::OPSI),
                TernaryFilter::make('is_active')
                    ->label('Status akun')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                Action::make('ubahStatus')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->visible(fn (User $record): bool => $record->getKey() !== auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?')
                    ->modalDescription(fn (User $record): string => $record->is_active
                        ? 'Akun tidak akan bisa masuk dashboard lagi sampai diaktifkan kembali.'
                        : 'Akun akan bisa masuk dashboard kembali.')
                    ->action(function (User $record) {
                        $aktifBaru = ! $record->is_active;

                        $record->forceFill(['is_active' => $aktifBaru])->save();

                        AuditLogger::catat(
                            $aktifBaru ? 'aktifkan_akun' : 'nonaktifkan_akun',
                            $record,
                            $record->email
                        );

                        Notification::make()
                            ->title($aktifBaru ? 'Akun diaktifkan' : 'Akun dinonaktifkan')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->label('Ubah'),
            ])
            ->toolbarActions([])
            ->striped()
            ->defaultSort('name');
    }
}
