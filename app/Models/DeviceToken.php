<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $table = 'device_token';

    protected $fillable = [
        'pasien_program_id', 'fcm_token', 'nama_perangkat',
        'terakhir_aktif', 'dicabut_pada',
    ];

    protected function casts(): array
    {
        return ['terakhir_aktif' => 'datetime', 'dicabut_pada' => 'datetime'];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(PasienProgram::class, 'pasien_program_id');
    }
}