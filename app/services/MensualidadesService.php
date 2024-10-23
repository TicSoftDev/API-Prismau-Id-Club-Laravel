<?php

namespace App\services;

use App\Models\Mensualidades;

class MensualidadesService
{

    public function getCantidadDeudas($id)
    {
        return Mensualidades::where('user_id', $id)
            ->where('estado', false)
            ->where('fecha', '<=', now()->format('Y-m-d'))
            ->count();
    }
}
