<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogMinum extends Model
{
    protected $table = 'log_minum';

    protected $fillable = [
        'jadwal_id', 'client_uuid', 'status',
        'waktu_konfirmasi', 'diterima_server_at', 'foto_path',
    ];

    protected function casts(): array
    {
        return [
            'waktu_konfirmasi' => 'datetime',
            'diterima_server_at' => 'datetime',
        ];
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }
}