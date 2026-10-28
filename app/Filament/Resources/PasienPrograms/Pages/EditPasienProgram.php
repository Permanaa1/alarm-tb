<?php

namespace App\Filament\Resources\PasienPrograms\Pages;

use App\Filament\Resources\PasienPrograms\PasienProgramResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPasienProgram extends EditRecord
{
    protected static string $resource = PasienProgramResource::class;

    protected function getHeaderActions(): array
    {
        // Sengaja tanpa tombol hapus. Gunakan status selesai/putus obat/pindah.
        return [
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
