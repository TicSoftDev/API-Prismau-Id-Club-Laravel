<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitado extends Model
{
    use HasFactory;

    protected $fillable = [
        'personal_id',
        'Nombre',
        'Apellidos',
        'Telefono',
        'TipoDocumento',
        'Documento',
        'Status',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class);
    }
}
