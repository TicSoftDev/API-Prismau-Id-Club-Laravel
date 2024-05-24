<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitado extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'Nombre',
        'Apellidos',
        'Telefono',
        'TipoDocumento',
        'Documento',
        'Status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
