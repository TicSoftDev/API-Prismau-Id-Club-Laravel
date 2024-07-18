<?php

namespace App\Http\Controllers;

use App\Models\Solicitudes;
use Illuminate\Http\Request;

class SolicitudesController extends Controller
{

    public function crearSolicitud(Request $request)
    {
        $validatedData = $request->validate([
            'Tipo' => 'required|string',
            'Descripcion' => 'required|string',
            'user_id' => 'required|integer|exists:users,id', 
            'Estado' => 'required'
        ]);

        try {
            $solicitud = Solicitudes::create($validatedData);
            return response()->json([
                "status" => true,
                "message" => "hecho",
                'solicitud' => $solicitud
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                'message' => 'Error al crear la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function solicitudes()
    {
        $solicitudes = Solicitudes::all();
        return response()->json($solicitudes);
    }

    public function contSolicitudes()
    {
        $solicitudes = Solicitudes::count();
        return response()->json($solicitudes);
    }
}
