<?php

namespace App\Filament\Resources\PasienPrograms\Pages;

use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AuditLogger;

class CreatePasienProgram extends CreateRecord
{
    protected static string $resource = PasienProgramResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
    $data['dibuat_oleh'] = auth()->id();

    return $data;
    }

    protected function afterCreate(): void
    {
    AuditLogger::catat('daftar_pasien', $this->record);
    }
}
