<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Familiar extends Model
{
    use HasFactory;

    protected $fillable = [
        'imagen',
        'user_id',
        'personal_id',
        'Nombre',
        'Apellidos',
        'Correo',
        'Telefono',
        'FechaNacimiento',
        'LugarNacimiento',
        'TipoDocumento',
        'Documento',
        'Sexo',
        'DireccionResidencia',
        'CiudadResidencia',
        'EstadoCivil',
        'Cargo',
        'Parentesco',
        'Estado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class);
    }
}
