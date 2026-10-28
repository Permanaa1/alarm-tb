<?php

namespace App\Filament\Resources\PasienPrograms;

use App\Filament\Resources\PasienPrograms\Pages\CreatePasienProgram;
use App\Filament\Resources\PasienPrograms\Pages\EditPasienProgram;
use App\Filament\Resources\PasienPrograms\Pages\ListPasienPrograms;
use App\Filament\Resources\PasienPrograms\Pages\ViewPasienProgram;
use App\Filament\Resources\PasienPrograms\RelationManagers\JadwalRelationManager;
use App\Filament\Resources\PasienPrograms\RelationManagers\ResepRelationManager;
use App\Filament\Resources\PasienPrograms\Schemas\PasienProgramForm;
use App\Filament\Resources\PasienPrograms\Schemas\PasienProgramInfolist;
use App\Filament\Resources\PasienPrograms\Tables\PasienProgramsTable;
use App\Models\PasienProgram;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PasienProgramResource extends Resource
{
    protected static ?string $model = PasienProgram::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Pasien';

    protected static ?string $pluralModelLabel = 'Pasien Program';

    protected static ?string $navigationLabel = 'Pasien Program';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PasienProgramForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PasienProgramInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PasienProgramsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ResepRelationManager::class,
            JadwalRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPasienPrograms::route('/'),
            'create' => CreatePasienProgram::route('/create'),
            'view' => ViewPasienProgram::route('/{record}'),
            'edit' => EditPasienProgram::route('/{record}/edit'),
        ];
    }
}
