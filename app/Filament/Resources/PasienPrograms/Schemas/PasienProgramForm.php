<?php

namespace App\Filament\Resources\PasienPrograms\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PasienProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data pasien')
                ->description('Isi manual dari berkas pasien. Data ini berdiri sendiri dan tidak terhubung ke SIMRS.')
                ->schema([
                    TextInput::make('no_rm')
                        ->label('No. RM')
                        ->required()
                        ->maxLength(30)
                        ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Pasien dengan no. RM ini sudah terdaftar di program.',
                        ]),

                    TextInput::make('nama')
                        ->label('Nama lengkap')
                        ->required()
                        ->maxLength(255)
                        ->dehydrateStateUsing(fn ($state) => trim((string) $state)),

                    TextInput::make('tempat_lahir')
                        ->label('Tempat lahir')
                        ->maxLength(100),

                    DatePicker::make('tgl_lahir')
                        ->label('Tanggal lahir')
                        ->required()
                        ->maxDate(now())
                        ->native(false)
                        ->displayFormat('d M Y'),

                    Select::make('jenis_kelamin')
                        ->label('Jenis kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ]),

                    TextInput::make('no_hp')
                        ->label('No. HP pasien')
                        ->tel()
                        ->maxLength(20),

                    Textarea::make('alamat')
                        ->label('Alamat')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Pendamping dan status')
                ->description('Jam minum obat diatur pada resep (tab Resep obat di halaman detail pasien).')
                ->schema([
                    Select::make('pmo_id')
                        ->label('PMO (pendamping)')
                        ->relationship('pmo', 'nama')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('nama')->required(),
                            TextInput::make('no_hp')->label('No. HP')->tel()->required(),
                            TextInput::make('hubungan')->placeholder('anak, istri, dll'),
                        ]),

                    Select::make('status')
                        ->options([
                            'aktif' => 'Aktif',
                            'selesai' => 'Selesai',
                            'putus_obat' => 'Putus obat',
                            'pindah' => 'Pindah',
                        ])
                        ->default('aktif')
                        ->required(),
                ])
                ->columns(2),
        ]);
    }
}
