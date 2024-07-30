<?php

namespace App\Http\Controllers;

use App\Models\Encuestas;
use App\Models\Preguntas;
use App\Models\RespuestasUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EncuestasController extends Controller
{

    public function crearEncuesta(Request $request)
    {
        try {
            $validated = $request->validate([
                'Titulo' => 'required|string',
                'Descripcion' => 'required|string',
                'Estado' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }

        $encuesta = Encuestas::create($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'encuesta' => $encuesta
        ], 201);
    }

    public function encuestas()
    {
        $encuestas = Encuestas::withCount('preguntas')->get();
        return response()->json($encuestas);
    }

    public function encuestasDisponibles($id)
    {
        User::findOrFail($id);
        $respondidas = RespuestasUsuario::where('user_id', $id)
            ->pluck('pregunta_id')
            ->unique();

        $encuestasRespondidas = Preguntas::whereIn('id', $respondidas)
            ->pluck('encuesta_id')
            ->unique();

        $encuestasNoRespondidas = Encuestas::whereNotIn('id', $encuestasRespondidas)->get();

        return response()->json($encuestasNoRespondidas);
    }

    public function contEncuestas()
    {
        return Encuestas::all()->count();
    }

    public function getEncuesta(string $id)
    {
        $encuesta = Encuestas::with(['preguntas.respuestas'])->find($id);
        return response()->json($encuesta);
    }

    public function actualizarEncuesta(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'Titulo' => 'required|string',
                'Descripcion' => 'required|string',
                'Estado' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        $encuesta = Encuestas::findOrFail($id);
        $encuesta->update($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'encuesta' => $encuesta
        ], 201);
    }

    public function borrarEncuesta(string $id)
    {
        $encuesta = Encuestas::findOrFail($id);
        $encuesta->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'encuesta' => $encuesta
        ], 200);
    }
}
