<?php

namespace App\Http\Controllers;

use App\Models\Respuestas;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RespuestasController extends Controller
{

    public function crearRespuesta(Request $request)
    {
        try {
            $validated = $request->validate([
                'pregunta_id' => 'required',
                'Respuesta' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }

        $Respuesta = Respuestas::create($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'Respuesta' => $Respuesta
        ], 201);
    }

    public function Respuestas($id)
    {
        return Respuestas::where('pregunta_id', $id)->get();
    }

    public function actualizarRespuesta(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'pregunta_id' => 'required',
                'Respuesta' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        $Respuesta = Respuestas::findOrFail($id);
        $Respuesta->update($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'Respuesta' => $Respuesta
        ], 201);
    }

    public function borrarRespuesta(string $id)
    {
        $Respuesta = Respuestas::findOrFail($id);
        $Respuesta->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'Respuesta' => $Respuesta
        ], 200);
    }
}
