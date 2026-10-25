<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class PasienProgram extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'pasien_program';

    protected $fillable = [
        'no_rm', 'nama', 'no_hp', 'pmo_id', 'jam_minum_default',
        'status', 'foto_bukti_aktif', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return ['foto_bukti_aktif' => 'boolean'];
    }

    public function pmo(): BelongsTo
    {
        return $this->belongsTo(Pmo::class, 'pmo_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function resep(): HasMany
    {
        return $this->hasMany(Resep::class, 'pasien_program_id');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'pasien_program_id');
    }

    public function kodeAktivasi(): HasMany
    {
        return $this->hasMany(KodeAktivasi::class, 'pasien_program_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class, 'pasien_program_id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class, 'pasien_program_id');
    }
}