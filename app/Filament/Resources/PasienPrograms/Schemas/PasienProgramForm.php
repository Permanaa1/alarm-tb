<?php

namespace App\Filament\Resources\PasienPrograms\Schemas;

use App\Services\Simrs\SimrsRepository;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PasienProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data pasien (dari SIMRS)')
                ->description('Cari pasien dari SIMRS. Nama dan nomor HP terisi otomatis.')
                ->schema([
                    Select::make('no_rm')
                        ->label('Cari pasien (no. RM atau nama)')
                        ->searchable()
                        ->required()
                        ->live()
                        ->getSearchResultsUsing(function (string $search): array {
                            if (mb_strlen(trim($search)) < 2) {
                                return [];
                            }

                            return app(SimrsRepository::class)
                                ->cariPasien($search)
                                ->mapWithKeys(fn ($p) => [$p->no_rm => "{$p->no_rm} — {$p->nama}"])
                                ->all();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $p = app(SimrsRepository::class)->cariByNoRm((string) $value);

                            return $p ? "{$p->no_rm} — {$p->nama}" : (string) $value;
                        })
                        ->afterStateUpdated(function ($state, Set $set) {
                            $p = $state ? app(SimrsRepository::class)->cariByNoRm((string) $state) : null;
                            $set('nama', $p->nama ?? null);
                            $set('no_hp', $p->no_hp ?? null);
                        })
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Pasien dengan no. RM ini sudah terdaftar di program.',
                        ])
                        ->disabledOn('edit')
                        ->columnSpanFull(),

                    TextInput::make('nama')
                        ->label('Nama')
                        ->readOnly()
                        ->required(),

                    TextInput::make('no_hp')
                        ->label('No. HP')
                        ->tel()
                        ->helperText('Terisi dari SIMRS. Boleh diperbarui kalau nomornya berubah.'),
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
