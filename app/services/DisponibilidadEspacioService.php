<?php

namespace App\services;

use App\Models\DisponibilidadEspacio;

class DisponibilidadEspacioService
{

    public function consultarDisponibilidad($espacioId, $dia, $horaInicio, $horaFin)
    {
        return DisponibilidadEspacio::where('espacio_id', $espacioId)
            ->where('Dia', $dia)
            ->where('Inicio', '<=', $horaInicio)
            ->where('Fin', '>=', $horaFin)
            ->exists();
    }
}
