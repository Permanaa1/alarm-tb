<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resep extends Model
{
    protected $table = 'resep';

    protected $fillable = [
        'pasien_program_id', 'nama_obat', 'dosis_teks', 'fase',
        'tgl_mulai', 'tgl_selesai', 'sumber', 'catatan',
    ];

    protected function casts(): array
    {
    return ['tgl_mulai' => 'date:Y-m-d', 'tgl_selesai' => 'date:Y-m-d'];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(PasienProgram::class, 'pasien_program_id');
    }
}