<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\AuditLogger;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        // Sengaja tanpa tombol hapus. Akun cukup dinonaktifkan.
        return [];
    }

    /** Pengaman: admin tidak boleh mengunci dirinya sendiri. */
    protected function beforeSave(): void
    {
        if ($this->record->getKey() !== auth()->id()) {
            return;
        }

        $aktif = $this->data['is_active'] ?? true;
        $peran = $this->data['role'] ?? $this->record->role;

        if (! $aktif || $peran !== 'admin') {
            Notification::make()
                ->title('Tidak bisa mengubah akun sendiri')
                ->body('Anda tidak boleh menonaktifkan atau menurunkan peran akun Anda sendiri.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        AuditLogger::catat('ubah_akun', $this->record, $this->record->email . ' (' . $this->record->role . ')');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
