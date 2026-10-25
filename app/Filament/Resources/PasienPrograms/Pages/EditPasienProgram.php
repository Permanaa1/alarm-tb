<?php

namespace App\Filament\Resources\PasienPrograms\Pages;

use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPasienProgram extends EditRecord
{
    protected static string $resource = PasienProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
