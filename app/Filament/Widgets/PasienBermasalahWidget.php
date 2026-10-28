<?php

namespace App\Filament\Widgets;

use App\Models\LogMinum;
use App\Models\PasienProgram;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PasienBermasalahWidget extends BaseWidget
{
    protected static ?string $heading = 'Pasien perlu ditindaklanjuti (7 hari terakhir)';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // Dosis dianggap "belum terkonfirmasi" bila jadwalnya sudah lewat 2 jam
        $batas = now()->subHours(2);

        $belumTerkonfirmasi = function ($q) use ($batas) {
            $q->where('jenis', 'minum')
                ->whereDate('tanggal', '>=', now()->subDays(6)->toDateString())
                ->where(function ($w) use ($batas) {
                    $w->whereDate('tanggal', '<', $batas->toDateString())
                        ->orWhere(function ($x) use ($batas) {
                            $x->whereDate('tanggal', $batas->toDateString())
                                ->where('jam', '<=', $batas->format('H:i:s'));
                        });
                })
                ->whereDoesntHave('logMinum', fn ($l) => $l->where('status', 'diminum'));
        };

        $hpTerhubung = fn ($q) => $q->whereNull('dicabut_pada');

        return $table
            ->query(
                PasienProgram::query()
                    ->where('status', 'aktif')
                    ->whereHas('jadwal', $belumTerkonfirmasi)
                    ->withCount(['jadwal as belum_terkonfirmasi' => $belumTerkonfirmasi])
                    ->withExists(['deviceTokens as hp_terhubung' => $hpTerhubung])
                    ->addSelect([
                        'terakhir_minum' => LogMinum::query()
                            ->select('log_minum.waktu_konfirmasi')
                            ->join('jadwal', 'jadwal.id', '=', 'log_minum.jadwal_id')
                            ->whereColumn('jadwal.pasien_program_id', 'pasien_program.id')
                            ->where('log_minum.status', 'diminum')
                            ->orderByDesc('log_minum.waktu_konfirmasi')
                            ->limit(1),
                    ])
                    ->with('pmo')
                    ->orderByDesc('belum_terkonfirmasi')
            )
            ->description('Angka = jumlah jadwal minum yang sudah lewat lebih dari 2 jam tanpa catatan "sudah minum". Bisa berarti pasien belum minum, HP belum sempat mengirim data, atau aplikasi belum diaktifkan (lihat kolom "HP terhubung").')
            ->columns([
                TextColumn::make('nama')->label('Pasien')->searchable(),
                TextColumn::make('no_rm')->label('No. RM'),
                TextColumn::make('belum_terkonfirmasi')
                    ->label('Dosis belum terkonfirmasi')
                    ->badge()
                    ->color(fn ($state): string => $state >= 3 ? 'danger' : 'warning'),
                IconColumn::make('hp_terhubung')
                    ->label('HP terhubung')
                    ->boolean(),
                TextColumn::make('terakhir_minum')
                    ->label('Terakhir konfirmasi minum')
                    ->dateTime('d M H:i')
                    ->placeholder('Belum pernah'),
                TextColumn::make('no_hp')->label('HP pasien')->copyable(),
                TextColumn::make('pmo.nama')->label('PMO')->placeholder('Belum ada'),
                TextColumn::make('pmo.no_hp')->label('HP PMO')->copyable(),
            ])
            ->filters([
                TernaryFilter::make('hp_terhubung')
                    ->label('HP terhubung')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('deviceTokens', $hpTerhubung),
                        false: fn (Builder $query) => $query->whereDoesntHave('deviceTokens', $hpTerhubung),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->poll('30s')
            ->paginated([10])
            ->emptyStateHeading('Tidak ada pasien yang perlu ditindaklanjuti');
    }
}
