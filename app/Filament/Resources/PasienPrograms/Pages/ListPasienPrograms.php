<?php

namespace App\Filament\Resources\PasienPrograms\Pages;

use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPasienPrograms extends ListRecords
{
    protected static string $resource = PasienProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
