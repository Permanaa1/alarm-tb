<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = ['pasien_program_id', 'jenis', 'tanggal', 'jam', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d'];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(PasienProgram::class, 'pasien_program_id');
    }

    public function logMinum(): HasMany
    {
        return $this->hasMany(LogMinum::class, 'jadwal_id');
    }

    /** Hanya jadwal jenis minum obat. */
    public function scopeMinum(Builder $query): Builder
    {
        return $query->where('jenis', 'minum');
    }

    /** Jadwal yang waktunya sudah lewat (sampai sekarang atau waktu yang diberikan). */
    public function scopeJatuhTempo(Builder $query, ?Carbon $sampai = null): Builder
    {
        $sampai = $sampai ?? now();

        return $query->where(function (Builder $w) use ($sampai) {
            $w->where('tanggal', '<', $sampai->toDateString())
                ->orWhere(function (Builder $x) use ($sampai) {
                    $x->where('tanggal', $sampai->toDateString())
                        ->where('jam', '<=', $sampai->format('H:i:s'));
                });
        });
    }

    /** Jadwal dalam N hari terakhir (termasuk hari ini). */
    public function scopeSejak(Builder $query, int $hari): Builder
    {
        return $query->where('tanggal', '>=', now()->subDays($hari - 1)->toDateString());
    }

    public function scopeSudahDiminum(Builder $query): Builder
    {
        return $query->whereHas('logMinum', fn ($l) => $l->where('status', 'diminum'));
    }

    public function scopeBelumDiminum(Builder $query): Builder
    {
        return $query->whereDoesntHave('logMinum', fn ($l) => $l->where('status', 'diminum'));
    }
}
