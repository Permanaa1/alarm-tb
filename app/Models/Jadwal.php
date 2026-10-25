<?php

namespace App\Models;

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
}