<?php

namespace App\services;

use App\Models\Adherente;

class AdherenteService
{

    public function cambiarEstado($id, $estado)
    {

        $adherente = Adherente::find($id);
        $adherente->estado = $estado;
        return $adherente->save();
    }
}
