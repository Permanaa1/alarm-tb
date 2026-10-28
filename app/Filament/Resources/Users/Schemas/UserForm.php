<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\PeranPetugas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $akunSendiri = fn ($record): bool => $record && $record->getKey() === auth()->id();

        return $schema->components([
            Section::make('Data akun')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email (untuk login)')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Select::make('role')
                        ->label('Peran')
                        ->options(PeranPetugas::OPSI)
                        ->default('tb_dots')
                        ->required()
                        ->disabled($akunSendiri)
                        ->helperText(
                            'Admin: ' . PeranPetugas::DESKRIPSI['admin']
                            . ' TB-DOTS: ' . PeranPetugas::DESKRIPSI['tb_dots']
                            . ' Farmasi: ' . PeranPetugas::DESKRIPSI['farmasi']
                        ),

                    Toggle::make('is_active')
                        ->label('Akun aktif')
                        ->default(true)
                        ->disabled($akunSendiri)
                        ->helperText('Akun nonaktif tidak bisa masuk dashboard. Akun sendiri tidak bisa dinonaktifkan.'),
                ])
                ->columns(2),

            Section::make('Kata sandi')
                ->schema([
                    TextInput::make('password')
                        ->label('Kata sandi')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(8)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                        ->helperText(fn (string $operation): string => $operation === 'edit'
                            ? 'Kosongkan bila tidak ingin mengganti kata sandi.'
                            : 'Minimal 8 karakter. Sampaikan ke petugas, lalu minta menggantinya lewat menu Profil.'),
                ]),
        ]);
    }
}
