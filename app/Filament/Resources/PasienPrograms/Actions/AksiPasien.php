<?php

namespace App\Filament\Resources\PasienPrograms\Actions;

use App\Models\PasienProgram;
use App\Services\ActivationCodeService;
use App\Services\AuditLogger;
use App\Services\ScheduleGenerator;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

/** Aksi yang dipakai di halaman detail dan di tabel, ditulis satu kali di sini. */
class AksiPasien
{
    public static function buatKode(): Action
    {
        return Action::make('buatKode')
            ->label('Buat kode aktivasi')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->visible(fn (): bool => in_array(auth()->user()?->role, ['admin', 'tb_dots'], true))
            ->requiresConfirmation()
            ->modalHeading('Buat kode aktivasi baru?')
            ->modalDescription('Kode lama yang belum dipakai akan dibatalkan. Kode hanya ditampilkan sekali, jadi tuliskan di kartu pasien.')
            ->modalSubmitActionLabel('Buat kode')
            ->action(function (PasienProgram $record) {
                $kode = app(ActivationCodeService::class)->buat($record, auth()->id());

                AuditLogger::catat('buat_kode', $record);

                $tampilan = '<div style="font-size:1.75rem;font-weight:700;letter-spacing:.12em;margin:.25rem 0">'
                    . e(ActivationCodeService::format($kode))
                    . '</div>Berlaku ' . ActivationCodeService::MASA_BERLAKU_JAM
                    . ' jam dan hanya bisa dipakai sekali. Tuliskan di kartu pasien.';

                Notification::make()
                    ->title('Kode aktivasi untuk ' . $record->nama)
                    ->body(new HtmlString($tampilan))
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    public static function buatJadwal(): Action
    {
        return Action::make('buatJadwal')
            ->label('Buat/perbarui jadwal')
            ->icon('heroicon-o-calendar-days')
            ->color('info')
            ->visible(fn (): bool => in_array(auth()->user()?->role, ['admin', 'tb_dots', 'farmasi'], true))
            ->requiresConfirmation()
            ->modalHeading('Buat atau perbarui jadwal minum?')
            ->modalDescription('Jadwal minum yang waktunya masih di depan akan dibuat ulang sesuai resep. Jadwal yang sudah lewat dan yang sudah punya catatan konfirmasi tidak diubah.')
            ->action(function (PasienProgram $record) {
                $hasil = app(ScheduleGenerator::class)->generate($record);

                AuditLogger::catat('buat_jadwal', $record, json_encode($hasil));

                $notif = Notification::make()
                    ->title('Jadwal untuk ' . $record->nama)
                    ->body(
                        "Dibuat: {$hasil['dibuat']}, diganti: {$hasil['dihapus']}"
                        . ($hasil['dilewati'] ? ", resep dilewati: {$hasil['dilewati']} (tanggal selesai kosong atau tidak wajar)" : '')
                    );

                ($hasil['dibuat'] === 0 || $hasil['dilewati'] > 0) ? $notif->warning() : $notif->success();

                $notif->send();
            });
    }
}
