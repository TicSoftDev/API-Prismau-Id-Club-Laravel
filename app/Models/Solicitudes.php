<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitudes extends Model
{
    use HasFactory;

    protected $fillable = [
        'Nombres',
        'Apellidos',
        'Identificacion',
        'Correo',
        'Telefono',
        'Empresa',
        'Ciudad',
        'Estado',
    ];
}
