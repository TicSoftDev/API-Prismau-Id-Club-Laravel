<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensualidades extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fecha',
        'valor',
        'estado',
    ];

    protected $appends = ['total_pagos', 'restante'];

    public function pago()
    {
        return $this->hasMany(Pagos::class, 'mensualidad_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalPagosAttribute()
    {
        return $this->pago->sum('monto');
    }

    public function getRestanteAttribute()
    {
        return $this->valor - $this->total_pagos;
    }
}
