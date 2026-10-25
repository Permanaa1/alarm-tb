<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pmo extends Model
{
    protected $table = 'pmo';

    protected $fillable = ['nama', 'no_hp', 'hubungan'];

    public function pasien(): HasMany
    {
        return $this->hasMany(PasienProgram::class, 'pmo_id');
    }
}