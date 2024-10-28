<?php

namespace App\services;

use App\Models\Mensualidades;
use App\Models\User;

class MensualidadesService
{

    public function getCantidadDeudas($id)
    {
        return Mensualidades::where('user_id', $id)
            ->where('estado', false)
            ->where('fecha', '<=', now()->format('Y-m-d'))
            ->count();
    }

    public function getDeudasPendientes($id)
    {
        return Mensualidades::where('user_id', $id)
            ->where('estado', false)
            ->orderBy('fecha', 'asc')
            ->get();
    }

    public function actualizarValorMensualidadesSocio($documento, $valor)
    {
        $socio = User::where('Documento', $documento)->first();
        $res = $socio->mensualidades()->where('estado', false)->update(['valor' => $valor]);
        if (!$res) {
            return response()->json([
                'status' => false,
                'message' => 'No hay mensualidades disponibles',
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Mensualidades actualizadas',
        ]);
    }
}
