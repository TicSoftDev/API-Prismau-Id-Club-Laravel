<?php

namespace App\services;

use App\Models\Contratos;

class ContratosService
{

    public function crearSolicitudContratoApp($request)
    {
        $validatedData = $request->validate([
            'Nombres' => 'required|string|max:255',
            'Apellidos' => 'required|string|max:255',
            'Identificacion' => 'required|string',
            'Correo' => 'required|email|max:255',
            'Telefono' => 'required|string|max:15',
            'Empresa' => 'nullable|string|max:255',
            'Ciudad' => 'required|string|max:255',
            'Estado' => 'required',
        ]);

        try {
            $solicitud = Contratos::create($validatedData);
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

    public function contratosApp()
    {
        $contratos = Contratos::all();
        return response()->json($contratos);
    }

    public function contContratosApp()
    {
        $contratos = Contratos::count();
        return response()->json($contratos);
    }
}
