<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function catat(string $aksi, ?Model $objek = null, ?string $keterangan = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'objek_tipe' => $objek?->getTable(),
            'objek_id' => $objek?->getKey(),
            'keterangan' => $keterangan,
            'ip_address' => request()->ip(),
        ]);
    }
}