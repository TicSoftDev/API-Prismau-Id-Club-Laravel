<?php

namespace App\Http\Controllers;

use App\Models\RespuestasUsuario;
use Illuminate\Http\Request;

class RespuestasUsuarioController extends Controller
{
    public function guardarRespuestasUsuarios(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'respuestas' => 'required|array',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
            'respuestas.*.respuesta_id' => 'required|exists:respuestas,id',
        ]);

        foreach ($validated['respuestas'] as $respuestaData) {
            RespuestasUsuario::create([
                'user_id' => $validated['user_id'],
                'pregunta_id' => $respuestaData['pregunta_id'],
                'respuesta_id' => $respuestaData['respuesta_id'],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Respuestas guardadas correctamente'
        ], 201);
    }
}
