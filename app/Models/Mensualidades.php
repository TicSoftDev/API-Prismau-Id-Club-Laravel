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

    public function pago()
    {
        return $this->hasOne(Pagos::class, 'mensualidad_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
