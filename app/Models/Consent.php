<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    protected $table = 'consent';

    protected $fillable = ['pasien_program_id', 'versi_teks', 'disetujui_pada'];

    protected function casts(): array
    {
        return ['disetujui_pada' => 'datetime'];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(PasienProgram::class, 'pasien_program_id');
    }
}