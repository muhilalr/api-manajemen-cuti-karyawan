<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuotaCuti extends Model
{
    protected $fillable = ['user_id', 'tahun', 'total_kuota', 'kuota_digunakan'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingQuotaAttribute(): int
    {
        return $this->total_kuota - $this->kuota_digunakan;
    }
}
