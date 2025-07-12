<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuotasBaile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'descripcion',
        'valor',
        'estado',
    ];

    protected $appends = ['total_pagos', 'restante'];

    public function pago()
    {
        return $this->hasMany(PagosCuotasBaile::class);
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
