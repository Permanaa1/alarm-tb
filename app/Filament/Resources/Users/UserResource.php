<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $modelLabel = 'Akun petugas';

    protected static ?string $pluralModelLabel = 'Akun petugas';

    protected static ?string $navigationLabel = 'Akun petugas';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 90;

    // Hanya admin. Semua pintu dijaga eksplisit karena tidak memakai policy.
    private static function adalahAdmin(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canViewAny(): bool
    {
        return self::adalahAdmin();
    }

    public static function canCreate(): bool
    {
        return self::adalahAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return self::adalahAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return false; // akun tidak dihapus, cukup dinonaktifkan
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
