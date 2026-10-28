<?php

namespace App\Filament\Resources\PasienPrograms\Pages;

use App\Filament\Resources\PasienPrograms\Actions\AksiPasien;
use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPasienProgram extends ViewRecord
{
    protected static string $resource = PasienProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AksiPasien::buatKode(),
            AksiPasien::buatJadwal(),
            EditAction::make()->label('Ubah data'),
        ];
    }
}
