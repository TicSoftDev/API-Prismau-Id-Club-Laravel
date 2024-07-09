<?php

namespace App\Http\Controllers;

use App\Models\Solicitudes;
use Illuminate\Http\Request;

class SolicitudesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function solicitudes()
    {
        $solicitudes = Solicitudes::all();
        return response()->json($solicitudes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function contSolicitudes()
    {
        $solicitudes = Solicitudes::count();
        return response()->json($solicitudes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function crearSolicitud(Request $request)
    {
        $validatedData = $request->validate([
            'Nombres' => 'required|string|max:255',
            'Apellidos' => 'required|string|max:255',
            'Identificacion' => 'required|string|unique:solicitudes,Identificacion',
            'Correo' => 'required|email|max:255',
            'Telefono' => 'required|string|max:15',
            'Empresa' => 'nullable|string|max:255',
            'Ciudad' => 'required|string|max:255',
            'Estado' => 'required',
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


    /**
     * Display the specified resource.
     */
    public function show(Solicitudes $solicitudes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Solicitudes $solicitudes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Solicitudes $solicitudes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Solicitudes $solicitudes)
    {
        //
    }
}
