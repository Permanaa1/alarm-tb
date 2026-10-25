<?php

namespace App\Filament\Resources\PasienPrograms\RelationManagers;

use App\Services\AuditLogger;
use App\Services\Simrs\SimrsRepository;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                TextColumn::make('fase')->badge(),
                TextColumn::make('tgl_mulai')->label('Mulai')->date('d M Y'),
                TextColumn::make('tgl_selesai')->label('Selesai')->date('d M Y')->placeholder('Belum diisi'),
            ])
            ->headerActions([
                Action::make('lihatSimrs')
                    ->label('Lihat resep di SIMRS')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->action(function () {
                        $pasien = $this->getOwnerRecord();
                        $rows = app(SimrsRepository::class)->resepPasien($pasien->no_rm);

                        AuditLogger::catat('lihat_resep_simrs', $pasien);

                        $teks = $rows->isEmpty()
                            ? 'Tidak ada resep ditemukan di SIMRS.'
                            : $rows->map(fn ($r) => e("{$r->tanggal}: {$r->nama_obat} — {$r->dosis}"))->implode('<br>');

                        Notification::make()
                            ->title('Resep di SIMRS')
                            ->body(new HtmlString($teks))
                            ->info()
                            ->persistent()
                            ->send();
                    }),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}