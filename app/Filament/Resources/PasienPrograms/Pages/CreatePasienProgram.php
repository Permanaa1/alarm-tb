<?php

namespace App\Filament\Resources\PasienPrograms\Pages;

use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use App\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

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

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
