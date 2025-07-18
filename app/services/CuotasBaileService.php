<?php

namespace App\services;

use App\Models\CuotasBaile;
use App\Models\User;

class CuotasBaileService
{

    public function actualizarValorCuotasBaileSocio($documento, $valor)
    {
        $socio = User::where('Documento', $documento)->first();
        $res = $socio->cuotas()->where('estado', false)->update(['valor' => $valor]);
        if (!$res) {
            return response()->json([
                'status' => false,
                'message' => 'No hay cuotas de baile disponibles',
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Cuotas de baile actualizadas',
        ]);
    }

    public function getDeudasPendientes($id)
    {
        return CuotasBaile::where('user_id', $id)->where('estado', false)->get();
    }
}
