<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preguntas extends Model
{
    use HasFactory;

    protected $fillable = ['encuesta_id', 'Pregunta'];

    public function encuesta()
    {
        return $this->belongsTo(Encuestas::class);
    }

    public function respuestas()
    {
        return $this->hasMany(Respuestas::class, 'pregunta_id');
    }
}
