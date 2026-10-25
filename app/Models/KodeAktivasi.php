<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KodeAktivasi extends Model
{
    protected $table = 'kode_aktivasi';

    protected $fillable = [
        'pasien_program_id', 'kode_hash', 'berlaku_sampai',
        'dipakai_pada', 'dibuat_oleh',
    ];

    protected $hidden = ['kode_hash'];

    protected function casts(): array
    {
        return ['berlaku_sampai' => 'datetime', 'dipakai_pada' => 'datetime'];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(PasienProgram::class, 'pasien_program_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}