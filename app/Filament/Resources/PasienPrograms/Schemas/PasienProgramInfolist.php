<?php

namespace App\Filament\Resources\PasienPrograms\Schemas;

use App\Services\RekapKepatuhan;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PasienProgramInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $rekap = app(RekapKepatuhan::class);

        return $schema->components([
            Section::make('Identitas')
                ->schema([
                    TextEntry::make('no_rm')->label('No. RM'),
                    TextEntry::make('nama')->label('Nama'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'aktif' => 'success',
                            'putus_obat' => 'danger',
                            'selesai' => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('no_hp')->label('HP pasien')->placeholder('-')->copyable(),
                    TextEntry::make('pmo.nama')->label('PMO (pendamping)')->placeholder('Belum ada'),
                    TextEntry::make('pmo.no_hp')->label('HP PMO')->placeholder('-')->copyable(),
                ])
                ->columns(3),

            Section::make('Proses pengobatan')
                ->description('Dihitung dari tanggal mulai paling awal dan tanggal selesai paling akhir pada resep.')
                ->schema([
                    TextEntry::make('tahap')
                        ->label('Tahap')
                        ->state(fn ($record) => $rekap->pengobatan($record)['tahap'] ?? 'Belum ada resep')
                        ->badge()
                        ->color(fn ($state): string => match ($state) {
                            'Sedang berjalan' => 'success',
                            'Belum dimulai' => 'warning',
                            default => 'gray',
                        }),
                    TextEntry::make('mulai')
                        ->label('Mulai pengobatan')
                        ->state(fn ($record) => $rekap->pengobatan($record)['mulai'] ?? null)
                        ->date('d M Y')
                        ->placeholder('-'),
                    TextEntry::make('selesai')
                        ->label('Rencana selesai')
                        ->state(fn ($record) => $rekap->pengobatan($record)['selesai'] ?? null)
                        ->date('d M Y')
                        ->placeholder('-'),
                    TextEntry::make('hari_ke')
                        ->label('Hari pengobatan')
                        ->state(function ($record) use ($rekap) {
                            $p = $rekap->pengobatan($record);

                            return $p['ada'] ? "Hari ke-{$p['hari_ke']} dari {$p['total_hari']}" : '-';
                        }),
                    TextEntry::make('sisa_hari')
                        ->label('Sisa waktu')
                        ->state(function ($record) use ($rekap) {
                            $p = $rekap->pengobatan($record);

                            return $p['ada'] ? "{$p['sisa_hari']} hari lagi" : '-';
                        }),
                    TextEntry::make('fase_sekarang')
                        ->label('Fase saat ini')
                        ->state(fn ($record) => ($rekap->pengobatan($record)['fase_sekarang'] ?? '') ?: '-')
                        ->badge(),
                    TextEntry::make('progres')
                        ->label('Kemajuan pengobatan')
                        ->state(function ($record) use ($rekap) {
                            $p = $rekap->pengobatan($record);
                            if (! $p['ada']) {
                                return '-';
                            }

                            $persen = (int) $p['persen'];

                            return new HtmlString(
                                '<div style="display:flex;align-items:center;gap:.75rem;width:100%">'
                                . '<div style="flex:1;background:#d1d5db;border-radius:9999px;height:12px">'
                                . '<div style="width:' . $persen . '%;background:#16a34a;height:12px;border-radius:9999px"></div>'
                                . '</div><strong>' . $persen . '%</strong></div>'
                            );
                        })
                        ->html()
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('Rekap kepatuhan minum obat')
                ->description('Dihitung dari jadwal minum yang waktunya sudah lewat. "Belum tercatat" belum tentu berarti pasien tidak minum: bisa juga HP belum mengirim data.')
                ->schema([
                    TextEntry::make('penilaian')
                        ->label('Penilaian (7 hari terakhir)')
                        ->state(function ($record) use ($rekap) {
                            $k = $rekap->kepatuhan($record, 7);

                            return RekapKepatuhan::label(
                                $k['diminum'],
                                $k['jatuh_tempo'],
                                $rekap->statusAplikasi($record)['terhubung']
                            )[0];
                        })
                        ->badge()
                        ->color(function ($record) use ($rekap): string {
                            $k = $rekap->kepatuhan($record, 7);

                            return RekapKepatuhan::label(
                                $k['diminum'],
                                $k['jatuh_tempo'],
                                $rekap->statusAplikasi($record)['terhubung']
                            )[1];
                        }),
                    TextEntry::make('rekap_7')
                        ->label('7 hari terakhir')
                        ->state(function ($record) use ($rekap) {
                            $k = $rekap->kepatuhan($record, 7);

                            return RekapKepatuhan::teksDosis($k['diminum'], $k['jatuh_tempo']);
                        }),
                    TextEntry::make('rekap_30')
                        ->label('30 hari terakhir')
                        ->state(function ($record) use ($rekap) {
                            $k = $rekap->kepatuhan($record, 30);

                            return RekapKepatuhan::teksDosis($k['diminum'], $k['jatuh_tempo']);
                        }),
                    TextEntry::make('rekap_semua')
                        ->label('Sejak awal')
                        ->state(function ($record) use ($rekap) {
                            $k = $rekap->kepatuhan($record, null);

                            return RekapKepatuhan::teksDosis($k['diminum'], $k['jatuh_tempo']);
                        }),
                    TextEntry::make('belum_7')
                        ->label('Belum tercatat (7 hari)')
                        ->state(fn ($record) => $rekap->kepatuhan($record, 7)['belum'] . ' dosis'),
                    TextEntry::make('terakhir_minum')
                        ->label('Terakhir konfirmasi minum')
                        ->state(fn ($record) => $rekap->terakhirMinum($record))
                        ->dateTime('d M Y H:i')
                        ->placeholder('Belum pernah'),
                ])
                ->columns(3),

            Section::make('Aplikasi di HP')
                ->schema([
                    IconEntry::make('hp_terhubung')
                        ->label('HP terhubung')
                        ->state(fn ($record) => $rekap->statusAplikasi($record)['terhubung'])
                        ->boolean(),
                    TextEntry::make('perangkat')
                        ->label('Perangkat')
                        ->state(fn ($record) => $rekap->statusAplikasi($record)['perangkat'])
                        ->placeholder('-'),
                    TextEntry::make('terakhir_aktif')
                        ->label('Terakhir aplikasi aktif')
                        ->state(fn ($record) => $rekap->statusAplikasi($record)['terakhir_aktif'])
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                    TextEntry::make('persetujuan')
                        ->label('Persetujuan data')
                        ->state(fn ($record) => $rekap->statusAplikasi($record)['consent']),
                    TextEntry::make('kode_aktivasi_status')
                        ->label('Kode aktivasi terakhir')
                        ->state(fn ($record) => $rekap->statusKode($record))
                        ->columnSpan(2),
                ])
                ->columns(3),
        ]);
    }
}
