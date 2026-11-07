<?php

namespace App\Filament\Resources\PasienPrograms\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResepRelationManager extends RelationManager
{
    protected static string $relationship = 'resep';

    protected static ?string $title = 'Resep obat';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_obat')
                ->label('Nama obat')
                ->required()
                ->maxLength(255),

            TextInput::make('dosis_teks')
                ->label('Dosis (salin persis dari resep dokter)')
                ->helperText('Aplikasi tidak menghitung dosis. Teks ini hanya ditampilkan ke pasien.')
                ->required()
                ->maxLength(255),

            Select::make('fase')
                ->options([
                    'intensif' => 'Fase intensif',
                    'lanjutan' => 'Fase lanjutan',
                ])
                ->required(),

            Repeater::make('jam_minum')
                ->label('Jam minum')
                ->helperText('Satu baris untuk setiap kali minum dalam sehari, sesuai anjuran dokter (misalnya 07:00 dan 19:00 untuk 2 kali sehari).')
                ->simple(
                    TimePicker::make('jam')
                        ->seconds(false)
                        ->required()
                )
                ->defaultItems(1)
                ->minItems(1)
                ->maxItems(6)
                ->addActionLabel('Tambah jam minum')
                ->columnSpanFull(),

            DatePicker::make('tgl_mulai')
                ->label('Tanggal mulai')
                ->required(),

            DatePicker::make('tgl_selesai')
                ->label('Tanggal selesai')
                ->helperText('Tentukan bersama dokter/petugas TB. Jadwal hanya dibuat sampai tanggal ini.')
                ->afterOrEqual('tgl_mulai')
                ->required(),

            Textarea::make('catatan')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_obat')
            ->columns([
                TextColumn::make('nama_obat')->label('Obat')->searchable(),
                TextColumn::make('dosis_teks')->label('Dosis'),
                TextColumn::make('jam_minum')
                    ->label('Jam minum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5))
                    ->placeholder('Ikut jam pasien'),
                TextColumn::make('fase')->badge(),
                TextColumn::make('tgl_mulai')->label('Mulai')->date('d M Y'),
                TextColumn::make('tgl_selesai')->label('Selesai')->date('d M Y')->placeholder('Belum diisi'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
